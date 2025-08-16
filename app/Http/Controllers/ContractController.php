<?php

namespace App\Http\Controllers;

use App\DTOs\InvestorShare;
use App\Http\Requests\StoreContractInvestorsRequest;
use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\ContractStatus;
use App\Models\ContractType;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\InstallmentStatus;
use App\Models\InstallmentType;
use App\Models\Investor;
use App\Models\InvestorTransaction;
use App\Models\OfficeTransaction;
use App\Models\TransactionStatus;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\ContractInstallmentController;

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

                // ✅ قيم الحساب الأساسية
                $totalValue       = (float) ($data['total_value'] ?? 0);
                $installmentValue = (float) ($request->installment_value ?? 0);

                $baseDate = $request->first_installment_date
                    ? Carbon::parse($request->first_installment_date)
                    : Carbon::parse($data['start_date'] ?? now());

                // ✅ جلب اسم نوع القسط (يومي/أسبوعي/شهري/سنوي)
                $installmentTypeName = optional(
                    InstallmentType::find($data['installment_type_id'] ?? null)
                )->name;

                /**
                 * احسب تاريخ الاستحقاق للقسط رقم $i
                 * القاعدة: القسط الأول = تاريخ أول قسط نفسه (0 فترة)،
                 * ثم 2 = +1 فترة، 3 = +2 فترات، وهكذا...
                 */
                $computeDueDate = function (Carbon $base, int $i) use ($installmentTypeName) {
                    $type = mb_strtolower(trim((string) $installmentTypeName));
                    $step = max(0, $i - 1); // 👈 هنا الفرق

                    if (str_contains($type, 'يوم') || str_contains($type, 'daily')) {
                        return $base->copy()->addDays($step);
                    } elseif (str_contains($type, 'أسبوع') || str_contains($type, 'اسبوع') || str_contains($type, 'week')) {
                        return $base->copy()->addWeeks($step);
                    } elseif (str_contains($type, 'سنة') || str_contains($type, 'سنوي') || str_contains($type, 'year')) {
                        return $base->copy()->addYears($step);
                    } elseif (str_contains($type, 'شهر') || str_contains($type, 'month')) {
                        return $base->copy()->addMonthsNoOverflow($step);
                    } else {
                        // افتراضي: شهري
                        return $base->copy()->addMonthsNoOverflow($step);
                    }
                };

                // ✅ توليد الأقساط حسب قيمة القسط ونوعه
                if ($installmentValue > 0.0) {
                    $installmentsCount = (int) floor($totalValue / $installmentValue);
                    $remaining         = round($totalValue - ($installmentsCount * $installmentValue), 2);

                    for ($i = 1; $i <= $installmentsCount; $i++) {
                        $dueDate = $computeDueDate($baseDate, $i); // 1 => baseDate, 2 => +1 فترة ...

                        ContractInstallment::create([
                            'contract_id'           => $contract->id,
                            'installment_number'    => $i,
                            'due_date'              => $dueDate,
                            'due_amount'            => $installmentValue,
                            'payment_amount'        => 0,
                            'installment_status_id' => $statuses['لم يحل'] ?? null,
                        ]);
                    }

                    if ($remaining > 0) {
                        // القسط الأخير للباقي = + (عدد الأقساط) فترات (لأن الأول = 0)
                        $dueDate = $computeDueDate($baseDate, $installmentsCount + 1);

                        ContractInstallment::create([
                            'contract_id'           => $contract->id,
                            'installment_number'    => $installmentsCount + 1,
                            'due_date'              => $dueDate,
                            'due_amount'            => $remaining,
                            'payment_amount'        => 0,
                            'installment_status_id' => $statuses['لم يحل'] ?? null,
                        ]);
                    }
                } elseif ($totalValue > 0.0) {
                    // قسط واحد بكل المبلغ في نفس تاريخ أول قسط
                    ContractInstallment::create([
                        'contract_id'           => $contract->id,
                        'installment_number'    => 1,
                        'due_date'              => $baseDate,
                        'due_amount'            => $totalValue,
                        'payment_amount'        => 0,
                        'installment_status_id' => $statuses['لم يحل'] ?? null,
                    ]);
                }

                // ✅ ربط المستثمرين + تحديث حالة العقد
                $this->syncInvestorsAndRecalcStatus($contract, $investors);

                // ✅ تسجيل عملية للمستثمرين لو فيه مستثمرين
                if (!empty($investors)) {
                    $this->logInvestorTransaction($contract, $investors, 'إضافة عقد');
                }

                // ✅ تسجيل "ربح فرق البيع" في معاملات المكتب
                $salePrice     = (float) ($data['sale_price'] ?? 0);
                $purchasePrice = (float) ($data['purchase_price'] ?? 0);
                $diff          = round($salePrice - $purchasePrice, 2);

                if ($diff > 0) {
                    $statusId = TransactionStatus::where('name', 'ربح فرق البيع')->value('id');

                    if ($statusId) {
                        OfficeTransaction::create([
                            'investor_id'      => null,
                            'contract_id'      => $contract->id,
                            'installment_id'   => null,
                            'status_id'        => $statusId,
                            'amount'           => $diff,
                            'transaction_date' => now(),
                            'notes'            => "ربح فرق البيع للعقد رقم {$contract->contract_number}",
                        ]);
                    }
                }
            });

        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withInput()
                ->withErrors(['general' => 'خطأ أثناء إنشاء العقد: ' . $e->getMessage()]);
        }

        return redirect()->route('contracts.index')->with('success', 'تم إنشاء العقد بنجاح.');
    }

    

    public function storeInvestors(StoreContractInvestorsRequest $request): JsonResponse
    {
        if (!$request->ajax()) {
            abort(404);
        }

        /** @var int $contractId */
        $contractId = (int) $request->validated('contract_id');
        /** @var array<int, array{id:int,share_percentage:float,share_value?:float|null}> $incomingRaw */
        $incomingRaw = $request->validated('investors');

        // حوّل الـ array لـ DTOs واضحة
        /** @var array<int, InvestorShare> $incoming */
        $incoming = array_map(
            fn (array $row) => InvestorShare::fromArray($row),
            $incomingRaw
        );

        $contract = Contract::with('investors')->findOrFail($contractId);
        $contractValue = (float) $contract->contract_value;

        // IDs الموجودين حالياً على العقد
        /** @var array<int,int> $existingIds */
        $existingIds = $contract->investors->pluck('id')->map(fn($id)=>(int)$id)->all();
        /** @var array<int,int> $incomingIds */
        $incomingIds = array_map(fn(InvestorShare $s) => $s->id, $incoming);

        // منع تمرير أي مستثمر موجود مسبقاً
        $intersection = array_values(array_intersect($incomingIds, $existingIds));
        if (!empty($intersection)) {
            return response()->json([
                'success' => false,
                'errors'  => ['general' => ['بعض المستثمرين مختارين بالفعل على هذا العقد ولا يمكن إضافتهم مرة أخرى.']]
            ], 422);
        }

        // حساب المجاميع
        $currentPct = (float) $contract->investors()->sum('contract_investor.share_percentage');
        $newPct     = array_reduce($incoming, fn($c, InvestorShare $s) => $c + $s->sharePercentage, 0.0);
        $afterAdd   = $currentPct + $newPct;

        // لا تتجاوز 100
        if ($afterAdd > 100 + self::EPS) {
            return response()->json([
                'success' => false,
                'errors'  => ['general' => ["مجموع نسب المستثمرين لا يجوز أن يتجاوز 100%. المجموع بعد الإضافة: " . round($afterAdd, 2) . "%"]]
            ], 422);
        }

        // لازم يبقى = 100% بعد الإضافة (متوافق مع منطق الفرونت)
        if (abs($afterAdd - 100) > self::EPS) {
            $remaining = max(0, 100 - $afterAdd);
            return response()->json([
                'success' => false,
                'errors'  => ['general' => ["لا يمكن الحفظ إلا إذا أصبح المجموع 100%. المتبقي الآن: " . round($remaining, 2) . "%"]]
            ], 422);
        }

        /**
         * @var array<int, array{
         *   share_percentage: float,
         *   share_value: float,
         *   created_at: \Illuminate\Support\Carbon,
         *   updated_at: \Illuminate\Support\Carbon
         * }> $pivotData
         */
        $pivotData = [];
        foreach ($incoming as $s) {
            // نحسب القيمة من السيرفر لضمان الاتساق
            $value = round(($contractValue * $s->sharePercentage) / 100, 2);
            if ($value <= 0) {
                return response()->json([
                    'success' => false,
                    'errors'  => ['general' => ['قيمة مشاركة المستثمر المحسوبة لا بد أن تكون أكبر من صفر.']]
                ], 422);
            }

            $pivotData[$s->id] = [
                'share_percentage' => (float) $s->sharePercentage,
                'share_value'      => (float) $value,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }

        DB::transaction(function () use ($contract, $pivotData) {
            // إضافة بدون نزع الموجودين
            $contract->investors()->sync($pivotData, false);

            // تسجيل معاملات "إضافة عقد" للمستثمرين المضافين
            $this->logInvestorTransaction(
                $contract->fresh('investors'),
                collect($pivotData)
                    ->map(fn($v, $id) => ['id' => (int) $id, 'share_value' => (float) $v['share_value']])
                    ->values()
                    ->all(),
                'إضافة عقد'
            );

            // تحديث حالة العقد حسب المجموع (سيصير "جديد" لو 100%)
            $pivotTable = 'contract_investor';
            $dbSum      = (float) $contract->investors()->sum("$pivotTable.share_percentage");
            $rows       = $contract->investors()->pluck('investors.id')->map(fn($id)=>['id'=>(int)$id])->all();

            $tmp = [];
            $this->applyAutoStatusBySum($tmp, $dbSum, $rows);
            if (!empty($tmp['contract_status_id']) && $tmp['contract_status_id'] != $contract->contract_status_id) {
                $contract->update(['contract_status_id' => $tmp['contract_status_id']]);
            }
        });

        // أعِد تحميل العقد وجدول المستثمرين
        $contract->load('investors');
        $html = view('contracts.partials.investors_table', compact('contract'))->render();

        return response()->json([
            'success' => true,
            'html'    => $html
        ]);
    }

    private function logInvestorTransaction(Contract $contract, array $investors, string $statusName = 'إضافة عقد'): void
    {
        $statusId = TransactionStatus::where('name', $statusName)->value('id');
        if (!$statusId) return;

        foreach ($investors as $inv) {
            InvestorTransaction::create([
                'investor_id'      => $inv['id'],
                'contract_id'      => $contract->id,
                'status_id'        => $statusId,
                'amount'           => (float)($inv['share_value'] ?? 0),
                'transaction_date' => now(),
                'notes'            => "عملية {$statusName} للعقد رقم {$contract->contract_number}",
            ]);
        }
    }

    public function show(Contract $contract)
    {
        $this->updateInstallmentsStatuses($contract);

        $contract->load([
            'customer',
            'guarantor',
            'contractStatus',
            'contractType',
            'installmentType',
            'investors',
            'installments.installmentStatus'
        ]);

        $investors = Investor::all();
        return view('contracts.show', compact('contract', 'investors'));
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

        // صور
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
            DB::transaction(function () use ($contract, $data, $request) {
                $contract->update($data);

                // ✅ ما نعدلش المستثمرين إلا لو بعتهم فعلًا
                if ($request->has('investors')) {
                    $investors = $this->normalizeInvestors($request->input('investors', []));
                    $this->syncInvestorsAndRecalcStatus($contract->fresh(), $investors);
                }
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
            'discount_amount'        => ['nullable','numeric','min:0'],
            'installment_type_id'    => ['required','exists:installment_types,id'],
            // ✅ لازم يكون > 0 علشان القسمة
            'installment_value'      => ['required','numeric','min:0.01'],
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

    private function updateInstallmentsStatuses(Contract $contract): void
    {
        // ✅ اشتغل بس لما مجموع نسب المستثمرين = 100%
        $contract->loadMissing('investors', 'installments.installmentStatus', 'contractStatus');

        $sumPct = (float) $contract->investors
            ->sum(fn($i) => (float) ($i->pivot->share_percentage ?? 0));

        if (round($sumPct, 2) !== 100.00) {
            return;
        }

        // عقود مُستثناة
        $excludedContractStatuses = ['منتهي', 'سداد مبكر', 'مطلوب'];
        if (in_array($contract->contractStatus->name ?? '', $excludedContractStatuses, true)) {
            return;
        }

        $today            = now();
        $statuses         = InstallmentStatus::pluck('id', 'name');
        $contractStatuses = ContractStatus::pluck('id', 'name');

        $lateCount     = 0;
        $maatherCount  = 0;
        $allPaid       = true;
        $anyLate       = false;
        $allNotDueYet  = true;

        foreach ($contract->installments as $installment) {
            $statusName = $installment->installmentStatus->name ?? null;

            if (!empty($installment->notes) && stripos($installment->notes, 'معتذر') !== false) {
                $maatherCount++;
            }

            if (in_array($statusName, ['مدفوع كامل', 'مدفوع مبكر', 'مدفوع متأخر', 'مدفوع جزئي', 'مؤجل', 'معتذر'], true)) {
                $allNotDueYet = false;
                continue;
            }

            $dueDate   = Carbon::parse($installment->due_date);
            $paid      = (float) ($installment->payment_amount ?? 0);
            $dueAmount = (float) ($installment->due_amount ?? 0);

            if ($paid < $dueAmount) {
                $allPaid = false;

                if ($dueDate->between($today->copy()->subDays(7), $today->copy()->addDays(7))) {
                    $installment->installment_status_id = $statuses['مستحق'] ?? $installment->installment_status_id;
                    $allNotDueYet = false;
                }
                elseif ($dueDate->greaterThan($today->copy()->addDays(7))) {
                    $installment->installment_status_id = $statuses['لم يحل'] ?? $installment->installment_status_id;
                }
                elseif ($dueDate->lessThan($today->copy()->subDays(7))) {
                    $installment->installment_status_id = $statuses['متأخر'] ?? $installment->installment_status_id;
                    $lateCount++;
                    $anyLate = true;
                    $allNotDueYet = false;
                }
            }

            $installment->save();
        }

        if ($allPaid) {
            $contract->contract_status_id = $contractStatuses['منتهي'] ?? $contract->contract_status_id;
        }
        elseif ($allNotDueYet) {
            $contract->contract_status_id = $contractStatuses['جديد'] ?? $contract->contract_status_id;
        }
        elseif ($maatherCount > 2) {
            $contract->contract_status_id = $contractStatuses['غير منتظم'] ?? $contract->contract_status_id;
        }
        elseif ($lateCount >= 3) {
            $contract->contract_status_id = $contractStatuses['متعثر'] ?? $contract->contract_status_id;
        }
        elseif ($anyLate) {
            $contract->contract_status_id = $contractStatuses['متأخر'] ?? $contract->contract_status_id;
        }
        else {
            $contract->contract_status_id = $contractStatuses['منتظم'] ?? $contract->contract_status_id;
        }

        $contract->save();
    }
}
