<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\ContractStatus;
use App\Models\ContractType;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\InstallmentStatus;
use App\Models\InstallmentType;
use App\Models\Investor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ContractController extends Controller
{
    private const EPS = 0.0001;

    private const STATUS_NAME_NO_INVESTORS = 'بدون مستثمر';
    private const STATUS_NAME_PENDING      = 'معلق';
    private const STATUS_NAME_NEW          = 'جديد';

    private const DIR_CONTRACT_MAIN        = 'contracts/contracts';
    private const DIR_CONTRACT_CUSTOMERS   = 'contracts/customers';
    private const DIR_CONTRACT_GUARANTORS  = 'contracts/guarantors';

    public function index(Request $request)
    {
        $contracts = Contract::with(['customer','guarantor','contractStatus','contractType','investors'])
            ->when($request->customer, function ($q) use ($request) {
                $q->whereHas('customer', function ($qq) use ($request) {
                    $qq->where('name', 'like', '%' . $request->customer . '%');
                });
            })
            ->when($request->type,   fn($q) => $q->where('contract_type_id', $request->type))
            ->when($request->status, fn($q) => $q->where('contract_status_id', $request->status))
            ->when($request->from,   fn($q) => $q->whereDate('start_date', '>=', $request->from))
            ->when($request->to,     fn($q) => $q->whereDate('start_date', '<=', $request->to))
            ->latest()
            ->paginate(10);

        

        $contractTypes    = ContractType::all();
        $contractStatuses = ContractStatus::all();

        return view('contracts.index', compact('contracts','contractTypes','contractStatuses'));
    }

    public function create()
    {
        return view('contracts.create', [
            'contract'         => new Contract(),
            'customers'        => Customer::all(),
            'guarantors'       => Guarantor::all(),
            'contractTypes'    => ContractType::all(),
            'installmentTypes' => InstallmentType::all(),
            'investors'        => Investor::all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateContract($request, false);
        $this->backfillCalculatedFields($data, $request);

        $data['contract_number'] = date('Ymd') . rand(10, 99);

        $investors = $this->normalizeInvestors($request->input('investors', []));

        $data['contract_image']           = $this->putImage($request, 'contract_image',           self::DIR_CONTRACT_MAIN);
        $data['contract_customer_image']  = $this->putImage($request, 'contract_customer_image',  self::DIR_CONTRACT_CUSTOMERS);
        $data['contract_guarantor_image'] = $this->putImage($request, 'contract_guarantor_image', self::DIR_CONTRACT_GUARANTORS);

        try {
            DB::transaction(function () use ($data, $investors, $request) {
                unset($data['contract_status_id']);

                $contract = Contract::create($data);

                $statuses = InstallmentStatus::pluck('id', 'name');

                // 🔹 إجمالي مبلغ العقد (الاعتماد على total_value)
                $totalValue       = $data['total_value'];
                $installmentValue = $request->installment_value;

                // عدد الأقساط الكاملة (بدون الباقي)
                $installmentsCount = floor($totalValue / $installmentValue);

                // المبلغ المتبقي
                $remaining = round($totalValue - ($installmentsCount * $installmentValue), 2);

                // إنشاء الأقساط الكاملة
                for ($i = 1; $i <= $installmentsCount; $i++) {
                    $dueDate = Carbon::parse($request->first_installment_date)->addMonths($i - 1);

                    ContractInstallment::create([
                        'contract_id'           => $contract->id,
                        'installment_number'    => $i,
                        'due_date'              => $dueDate,
                        'due_amount'            => $installmentValue,
                        'payment_amount'        => 0,
                        'installment_status_id' => $statuses['لم يحل'] ?? null
                    ]);
                }

                // 🔹 إضافة آخر قسط لو فيه مبلغ متبقي
                if ($remaining > 0) {
                    $dueDate = Carbon::parse($request->first_installment_date)->addMonths($installmentsCount);

                    ContractInstallment::create([
                        'contract_id'           => $contract->id,
                        'installment_number'    => $installmentsCount + 1,
                        'due_date'              => $dueDate,
                        'due_amount'            => $remaining,
                        'payment_amount'        => 0,
                        'installment_status_id' => $statuses['لم يحل'] ?? null
                    ]);
                }

                $this->syncInvestorsAndRecalcStatus($contract, $investors);
            });

        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withInput()
                ->withErrors(['general' => 'خطأ أثناء إنشاء العقد: ' . $e->getMessage()]);
        }

        return redirect()->route('contracts.index')->with('success', 'تم إنشاء العقد بنجاح.');
    }


    public function show(Contract $contract)
    {
        $contract->load([
            'customer',
            'guarantor',
            'contractStatus',
            'contractType',
            'installmentType',
            'investors',
            'installments.installmentStatus'
        ]);
        
            $this->updateInstallmentsStatuses($contract);

        return view('contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        $contract->load('investors');

        return view('contracts.edit', [
            'contract'         => $contract,
            'customers'        => Customer::all(),
            'guarantors'       => Guarantor::all(),
            'contractTypes'    => ContractType::all(),
            'installmentTypes' => InstallmentType::all(),
            'investors'        => Investor::all(),
        ]);
    }

    public function update(Request $request, Contract $contract)
    {
        $data = $this->validateContract($request, true);
        $this->backfillCalculatedFields($data, $request);

        unset($data['contract_status_id']);

        $investors = $this->normalizeInvestors($request->input('investors', []));

        if ($img = $this->putImage($request, 'contract_image', self::DIR_CONTRACT_MAIN, $contract->contract_image)) {
            $data['contract_image'] = $img;
        }
        if ($img = $this->putImage($request, 'contract_customer_image', self::DIR_CONTRACT_CUSTOMERS, $contract->contract_customer_image)) {
            $data['contract_customer_image'] = $img;
        }
        if ($img = $this->putImage($request, 'contract_guarantor_image', self::DIR_CONTRACT_GUARANTORS, $contract->contract_guarantor_image)) {
            $data['contract_guarantor_image'] = $img;
        }

        try {
            DB::transaction(function () use ($contract, $data, $investors) {
                $contract->update($data);
                $this->syncInvestorsAndRecalcStatus($contract->fresh(), $investors);
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->withErrors(['general' => 'تعذّر تحديث العقد. حاول مرة أخرى.']);
        }

        return redirect()->route('contracts.index')->with('success', 'تم تحديث العقد بنجاح.');
    }

    public function destroy(Contract $contract)
    {
        if (!empty($contract->contract_image)) {
            Storage::disk('public')->delete($contract->contract_image);
        }
        if (!empty($contract->contract_customer_image)) {
            Storage::disk('public')->delete($contract->contract_customer_image);
        }
        if (!empty($contract->contract_guarantor_image)) {
            Storage::disk('public')->delete($contract->contract_guarantor_image);
        }

        $contract->delete();
        return redirect()->route('contracts.index')->with('success', 'تم حذف العقد بنجاح.');
    }

    private function validateContract(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'customer_id'            => ['required','exists:customers,id'],
            'guarantor_id'           => ['nullable','exists:guarantors,id'],
            'contract_type_id'       => ['required','exists:contract_types,id'],
            'products_count'         => ['required','integer','min:0'],
            'purchase_price'         => ['required','numeric','min:0'],
            'sale_price'             => ['required','numeric','min:0'],
            'contract_value'         => ['nullable','numeric','min:0'],
            'investor_profit'        => ['required','numeric','min:0'],
            'total_value'            => ['nullable','numeric','min:0'],
            'installment_type_id'    => ['required','exists:installment_types,id'],
            'installment_value'      => ['required','numeric','min:0'],
            'installments_count'     => ['required','integer','min:1'],
            'start_date'             => ['required','date'],
            'first_installment_date' => ['nullable','date'],
            'contract_image'           => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'contract_customer_image'  => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'contract_guarantor_image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'investors'                    => [$isUpdate ? 'sometimes' : 'nullable','array'],
            'investors.*.id'               => ['nullable','exists:investors,id'],
            'investors.*.share_percentage' => ['nullable','numeric','min:0','max:100'],
            'investors.*.share_value'      => ['nullable','numeric','min:0'],
        ];

        if ($isUpdate) {
            foreach ($rules as $key => $rule) {
                if (!str_starts_with($key, 'investors')) {
                    $rules[$key] = array_merge(['sometimes'], (array) $rule);
                }
            }
        }

        return $request->validate($rules);
    }

    private function backfillCalculatedFields(array &$data, Request $request): void
    {
        $sale = (float) ($data['sale_price'] ?? $request->input('sale_price', 0));
        if (!isset($data['contract_value']) || $data['contract_value'] === '' || $data['contract_value'] === null) {
            $data['contract_value'] = $sale;
        }

        $profit = (float) ($data['investor_profit'] ?? $request->input('investor_profit', 0));
        if (!isset($data['total_value']) || $data['total_value'] === '' || $data['total_value'] === null) {
            $data['total_value'] = (float) $data['contract_value'] + $profit;
        }

        if (!empty($data['start_date'])) {
            $data['start_date'] = Carbon::parse($data['start_date'])->format('Y-m-d');
        }
        if (!empty($data['first_installment_date'])) {
            $data['first_installment_date'] = Carbon::parse($data['first_installment_date'])->format('Y-m-d');
        }
    }

    private function normalizeInvestors(array $investors): array
    {
        if (empty($investors)) return [];

        $clean = [];
        foreach ($investors as $row) {
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            if ($id <= 0) continue;

            $pct = isset($row['share_percentage']) && $row['share_percentage'] !== ''
                ? (float) $row['share_percentage'] : 0.0;

            $val = isset($row['share_value']) && $row['share_value'] !== ''
                ? (float) $row['share_value'] : null;

            $clean[$id] = [
                'id'               => $id,
                'share_percentage' => $pct,
                'share_value'      => $val,
            ];
        }

        return array_values($clean);
    }

    private function validateAndSumInvestorsPercentages(array $investors): float
    {
        if (empty($investors)) return 0.0;

        $sum = 0.0;
        foreach ($investors as $i => $inv) {
            $p = isset($inv['share_percentage']) ? (float) $inv['share_percentage'] : 0.0;

            if ($p < 0 || $p > 100) {
                throw ValidationException::withMessages([
                    "investors.$i.share_percentage" => 'نسبة المشاركة يجب أن تكون بين 0 و 100.'
                ]);
            }

            $sum += $p;
        }

        if ($sum > 100.0001) {
            throw ValidationException::withMessages([
                "investors" => "مجموع نسب المستثمرين لا يجوز أن يتجاوز 100%. المجموع الحالي: {$sum}%"
            ]);
        }

        return round($sum, 4);
    }

    private function preparePivotData(array $investors, float $contractValue): array
    {
        $now = now();
        $pivot = [];

        foreach ($investors as $inv) {
            $id         = (int) ($inv['id'] ?? 0);
            $percentage = (float) ($inv['share_percentage'] ?? 0);

            $value = (isset($inv['share_value']) && $inv['share_value'] !== null && $inv['share_value'] !== '')
                ? (float) $inv['share_value']
                : round(($contractValue * $percentage) / 100, 2);

            $pivot[$id] = [
                'share_percentage' => $percentage,
                'share_value'      => $value,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        return $pivot;
    }

    private function resolveStatusIdByName(string $name): ?int
    {
        static $cache = [];

        $key = mb_strtolower(trim($name));
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $id = ContractStatus::query()
            ->where('name', $name)
            ->value('id');

        $cache[$key] = $id ?: null;
        return $cache[$key];
    }

    private function applyAutoStatusBySum(array &$data, float $sum, array $investors = []): void
    {
        $hasRealInvestors = false;
        foreach ($investors as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id > 0) { $hasRealInvestors = true; break; }
        }

        $sumRounded = round($sum, 2);

        if (!$hasRealInvestors || $sumRounded <= self::EPS) {
            if ($id = $this->resolveStatusIdByName(self::STATUS_NAME_NO_INVESTORS)) {
                $data['contract_status_id'] = $id;
            }
            return;
        }

        if ($sumRounded < (100 - self::EPS)) {
            if ($id = $this->resolveStatusIdByName(self::STATUS_NAME_PENDING)) {
                $data['contract_status_id'] = $id;
            }
            return;
        }

        if ($id = $this->resolveStatusIdByName(self::STATUS_NAME_NEW)) {
            $data['contract_status_id'] = $id;
        }
    }

    private function putImage(Request $request, string $field, string $dir, ?string $old = null): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }
        $path = $request->file($field)->store($dir, 'public');
        if ($old) Storage::disk('public')->delete($old);
        return $path;
    }

    private function syncInvestorsAndRecalcStatus(Contract $contract, array $investors): void
    {
        $sum = $this->validateAndSumInvestorsPercentages($investors);

        if ($sum > self::EPS && !empty($investors)) {
            $pivot = $this->preparePivotData($investors, $contract->contract_value);
            $contract->investors()->sync($pivot);
        } else {
            $contract->investors()->detach();
        }

        $pivotTable = 'contract_investor';
        $dbSum   = (float) $contract->investors()->sum("$pivotTable.share_percentage");
        $count   = (int)   $contract->investors()->count();
        $rows    = $count > 0
            ? $contract->investors()->pluck('investors.id')->map(fn($id) => ['id' => (int)$id])->all()
            : [];

        $tmp = [];
        $this->applyAutoStatusBySum($tmp, $dbSum, $rows);

        if (!empty($tmp['contract_status_id']) && $tmp['contract_status_id'] != $contract->contract_status_id) {
            $contract->update(['contract_status_id' => $tmp['contract_status_id']]);
        }
    }

    private function updateInstallmentsStatuses(Contract $contract)
    {
        $excludedContractStatuses = ['منتهي', 'سداد مبكر', 'مطلوب'];

        // استبعاد العقود اللي حالتها من الحالات دي
        if (in_array($contract->contractStatus->name ?? '', $excludedContractStatuses)) {
            return;
        }

        $today    = now();
        $statuses = InstallmentStatus::pluck('id', 'name');

        $lateCount     = 0; // عدد المتأخر
        $maatherCount  = 0; // عدد المعتذر
        $allPaid       = true;
        $anyLate       = false;
        $allNotDueYet  = true; // كل الأقساط لم يحل

        // تحديث حالات الأقساط
        foreach ($contract->installments as $installment) {
            $statusName = $installment->installmentStatus->name ?? null;

            // استبعاد الأقساط المدفوعة من التحديث
            if (in_array($statusName, ['مدفوع كامل', 'مدفوع مبكر', 'مدفوع متأخر', 'مدفوع جزئي', 'مؤجل'])) {
                $allNotDueYet = false;
                continue;
            }

            $dueDate   = Carbon::parse($installment->due_date);
            $paid      = $installment->payment_amount ?? 0;
            $dueAmount = $installment->due_amount ?? 0;

            // لو القسط مش مدفوع بالكامل
            if ($paid < $dueAmount) {
                $allPaid = false;

                if ($dueDate->between($today->copy()->subDays(7), $today->copy()->addDays(7))) {
                    $installment->installment_status_id = $statuses['مستحق'] ?? null;
                    $allNotDueYet = false;
                }
                elseif ($dueDate->greaterThan($today->copy()->addDays(7))) {
                    $installment->installment_status_id = $statuses['لم يحل'] ?? null;
                }
                elseif ($dueDate->lessThan($today->copy()->subDays(7))) {
                    $installment->installment_status_id = $statuses['متأخر'] ?? null;
                    $lateCount++;
                    $anyLate = true;
                    $allNotDueYet = false;
                }
            }

            // عدّ الأقساط المعتذر
            if ($statusName === 'معتذر') {
                $maatherCount++;
                $allNotDueYet = false;
            }

            $installment->save();
        }

        // تحديث حالة العقد حسب الأولوية
        if ($allPaid) {
            $contract->contract_status_id = ContractStatus::where('name', 'منتهي')->value('id');
        }
        elseif ($allNotDueYet) {
            $contract->contract_status_id = ContractStatus::where('name', 'جديد')->value('id');
        }
        elseif ($maatherCount > 2) {
            $contract->contract_status_id = ContractStatus::where('name', 'غير منتظم')->value('id');
        }
        elseif ($lateCount >= 3) {
            $contract->contract_status_id = ContractStatus::where('name', 'متعثر')->value('id');
        }
        elseif ($anyLate) {
            $contract->contract_status_id = ContractStatus::where('name', 'متأخر')->value('id');
        }
        else {
            $contract->contract_status_id = ContractStatus::where('name', 'منتظم')->value('id');
        }

        $contract->save();
    }




}
