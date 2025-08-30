<?php

namespace App\Http\Controllers;

use App\DTOs\InvestorShare;
use App\Http\Requests\StoreContractInvestorsRequest;
use App\Models\BankAccount;
use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\ContractStatus;
use App\Models\Customer;
use App\Models\Guarantor;
use App\Models\InstallmentStatus;
use App\Models\InstallmentType;
use App\Models\Investor;
use App\Models\InvestorTransaction;
use App\Models\LedgerEntry;
use App\Models\ProductType;
use App\Models\Safe;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Services\InstallmentsMonthlyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function index(Request $request, InstallmentsMonthlyService $installmentsSvc)
{
    $pivotTable = (new Contract)->investors()->getTable();

    // الاستعلام الأساسي للعقود
    $contractsQuery = Contract::query()
        ->with(['customer', 'guarantor', 'contractStatus', 'productType', 'investors']);

    // فلترة حسب العميل
    if ($request->filled('customer')) {
        $name = trim($request->customer);
        $contractsQuery->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$name}%"));
    }

    // فلترة حسب المستثمر
    if ($request->filled('investor_id')) {
        $investorId = $request->investor_id;
        if ($investorId === '_none') {
            $contractsQuery->doesntHave('investors');
        } else {
            $contractsQuery->whereHas('investors', fn($q) => $q->where('investors.id', $investorId)
                ->where($pivotTable . '.share_percentage', '<=', 100));
        }
    } elseif ($request->filled('investor')) {
        $name = trim($request->investor);
        $contractsQuery->whereHas('investors', fn($q) => $q->where('investors.name', 'like', "%{$name}%")
            ->where($pivotTable . '.share_percentage', '<=', 100));
    }

    // فلترة حسب حالة العقد
    if ($request->filled('status')) {
        $contractsQuery->where('contract_status_id', $request->status);
    }

    // فلترة حسب التواريخ
    if ($request->filled('from')) {
        $contractsQuery->whereDate('start_date', '>=', $request->from);
    }
    if ($request->filled('to')) {
        $contractsQuery->whereDate('start_date', '<=', $request->to);
    }

    $contracts = $contractsQuery->latest()->paginate(10);

    // ===== تحديث حالات الأقساط لكل عقد باستخدام نفس لوجيك show() =====
    foreach ($contracts as $contract) {
        $this->updateInstallmentsStatuses($contract);
    }

    $investors = Investor::orderBy('name')->get(['id', 'name']);
    $contractStatuses = ContractStatus::orderBy('name')->get(['id', 'name']);

    // ===== ملخص الأقساط =====
    $m = $request->integer('m') ?: null;
    $y = $request->integer('y') ?: null;
    $exclude = ['مؤجل','معتذر'];
    $investorIdForMonthly = ($request->filled('investor_id') && $request->investor_id !== '_none')
        ? (int)$request->investor_id
        : null;

    try {
        if ($investorIdForMonthly) {
            if (method_exists($installmentsSvc, 'buildForInvestor')) {
                $investorModel = Investor::find($investorIdForMonthly);
                $installmentsMonthly = $installmentsSvc->buildForInvestor($investorModel ?: $investorIdForMonthly, $m, $y, $exclude);
            } else {
                $installmentsMonthly = $installmentsSvc->build($m, $y, $exclude, $investorIdForMonthly);
            }
        } else {
            $installmentsMonthly = $installmentsSvc->build($m, $y, $exclude);
        }
    } catch (\ArgumentCountError $e) {
        $installmentsMonthly = $installmentsSvc->build($m, $y, $exclude);
    }

    return view('contracts.index', compact(
        'contracts',
        'contractStatuses',
        'investors',
        'installmentsMonthly'
    ));
}


    public function create()
    {
        return view('contracts.create', [
            'contract'         => new Contract(),
            'customers'        => Customer::all(),
            'guarantors'       => Guarantor::all(),
            'productTypes'     => ProductType::all(), // ✅
            'installmentTypes' => InstallmentType::all(),
            'investors'        => Investor::all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateContract($request, false);
        $this->backfillCalculatedFields($data, $request);

        // ✅ نتاكد أن المفتاح المستخدم هو product_type_id فقط
        if (!empty($data['contract_type_id']) && empty($data['product_type_id'])) {
            $data['product_type_id'] = (int) $data['contract_type_id'];
        }
        unset($data['contract_type_id']);

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

                // === إعدادات الأقساط ===
                $totalValue       = (float) ($data['total_value'] ?? 0);
                $installmentValue = (float) ($request->installment_value ?? 0);

                $baseDate = $request->first_installment_date
                    ? Carbon::parse($request->first_installment_date)
                    : Carbon::parse($data['start_date'] ?? now());

                $installmentTypeName = optional(
                    InstallmentType::find($data['installment_type_id'] ?? null)
                )->name;

                $computeDueDate = function (Carbon $base, int $i) use ($installmentTypeName) {
                    $type = mb_strtolower(trim((string) $installmentTypeName));
                    $step = max(0, $i - 1);
                    if (str_contains($type, 'يوم') || str_contains($type, 'daily'))  return $base->copy()->addDays($step);
                    if (str_contains($type, 'أسبوع') || str_contains($type, 'week')) return $base->copy()->addWeeks($step);
                    if (str_contains($type, 'سنة') || str_contains($type, 'year'))   return $base->copy()->addYears($step);
                    /* شهر */                                                        return $base->copy()->addMonthsNoOverflow($step);
                };

                if ($installmentValue > 0.0) {
                    $installmentsCount = (int) floor($totalValue / $installmentValue);
                    $remaining         = round($totalValue - ($installmentsCount * $installmentValue), 2);

                    for ($i = 1; $i <= $installmentsCount; $i++) {
                        ContractInstallment::create([
                            'contract_id'           => $contract->id,
                            'installment_number'    => $i,
                            'due_date'              => $computeDueDate($baseDate, $i),
                            'due_amount'            => $installmentValue,
                            'payment_amount'        => 0,
                            'installment_status_id' => $statuses['لم يحل'] ?? null,
                        ]);
                    }

                    if ($remaining > 0) {
                        ContractInstallment::create([
                            'contract_id'           => $contract->id,
                            'installment_number'    => $installmentsCount + 1,
                            'due_date'              => $computeDueDate($baseDate, $installmentsCount + 1),
                            'due_amount'            => $remaining,
                            'payment_amount'        => 0,
                            'installment_status_id' => $statuses['لم يحل'] ?? null,
                        ]);
                    }
                } elseif ($totalValue > 0.0) {
                    ContractInstallment::create([
                        'contract_id'           => $contract->id,
                        'installment_number'    => 1,
                        'due_date'              => $baseDate,
                        'due_amount'            => $totalValue,
                        'payment_amount'        => 0,
                        'installment_status_id' => $statuses['لم يحل'] ?? null,
                    ]);
                }

                // ربط المستثمرين + لوج ترانزاكشن
                $this->syncInvestorsAndRecalcStatus($contract, $investors);
                if (!empty($investors)) {
                    $this->logInvestorTransaction($contract, $investors, 'إضافة عقد');
                }

                // === قيد فرق البيع (مكتب) + تسجيل في product_transactions ===
                $salePrice     = (float) ($data['sale_price'] ?? 0);
                $purchasePrice = (float) ($data['purchase_price'] ?? 0);
                $diff          = round($salePrice - $purchasePrice, 2);

                if ($diff > 0) {
                    $statusRow = TransactionStatus::whereIn('name', ['فرق البيع', 'ربح فرق البيع'])
                        ->first(['id', 'transaction_type_id']);

                    if ($statusRow) {
                        $typeId =
                            ($statusRow->transaction_type_id ?? null)
                            ?: TransactionType::whereIn('name', ['ربح فرق البيع','فرق البيع','أرباح','تحصيل'])->value('id')
                            ?: TransactionType::query()->orderBy('id')->value('id');

                        if ($typeId) {
                            $saleDiffEntry = LedgerEntry::create([
                                'entry_date'            => now()->toDateString(),
                                'investor_id'           => null,
                                'is_office'             => true,
                                'transaction_status_id' => $statusRow->id,
                                'transaction_type_id'   => $typeId,
                                'bank_account_id'       => null,
                                'safe_id'               => null,
                                'contract_id'           => $contract->id,
                                'installment_id'        => null,
                                'amount'                => $diff,
                                'ref'                   => 'CT-'.$contract->id,
                                'notes'                 => "قيد فرق البيع للعقد #{$contract->contract_number}",
                            ]);

                            // === products_count + نوع البضاعة من product_types عبر product_type_id ===
                            try {
                                if (Schema::hasTable('product_transactions') &&
                                    Schema::hasColumn('product_transactions', 'ledger_entry_id')) {

                                    $qty = (int) (
                                        $data['products_count']
                                        ?? $request->input('products_count')
                                        ?? 0
                                    );

                                    $productTypeId = (int) (
                                        $data['product_type_id']
                                        ?? $request->input('product_type_id')
                                        ?? 0
                                    );

                                    if ($productTypeId > 0 && Schema::hasTable('product_types')) {
                                        $exists = DB::table('product_types')->where('id', $productTypeId)->exists();
                                        if (!$exists) { $productTypeId = 0; }
                                    }

                                    $payload = [
                                        'ledger_entry_id' => $saleDiffEntry->id,
                                        'created_at'      => now(),
                                        'updated_at'      => now(),
                                    ];

                                    if (Schema::hasColumn('product_transactions', 'quantity')) {
                                        $payload['quantity'] = max(0, $qty);
                                    }

                                    // ✅ نخزّن نوع البضاعة كـ product_type_id (أو goods_type_id)
                                    if (Schema::hasColumn('product_transactions', 'product_type_id')) {
                                        $payload['product_type_id'] = $productTypeId > 0 ? $productTypeId : null;
                                    } elseif (Schema::hasColumn('product_transactions', 'goods_type_id')) {
                                        $payload['goods_type_id'] = $productTypeId > 0 ? $productTypeId : null;
                                    } else {
                                        foreach (['type','goods_type','product_type','note','description'] as $col) {
                                            if (Schema::hasColumn('product_transactions', $col)) {
                                                $payload[$col] = $productTypeId > 0 ? ('type#'.$productTypeId) : 'غير محدد';
                                                break;
                                            }
                                        }
                                    }

                                    // حالة "إضافة عقد"
                                    if (Schema::hasColumn('product_transactions', 'transaction_status_id')) {
                                        $addContractStatusId = TransactionStatus::where('name', 'إضافة عقد')->value('id');
                                        if ($addContractStatusId) $payload['transaction_status_id'] = $addContractStatusId;
                                    } else {
                                        foreach (['status','action','note','description'] as $col) {
                                            if (Schema::hasColumn('product_transactions', $col)) {
                                                $payload[$col] = 'إضافة عقد';
                                                break;
                                            }
                                        }
                                    }

                                    if (Schema::hasColumn('product_transactions', 'contract_id')) {
                                        $payload['contract_id'] = $contract->id;
                                    }

                                    DB::table('product_transactions')->insert($payload);
                                }
                            } catch (\Throwable $ignore) {
                                // اختلافات السكيمة لا تكسر العملية
                            }
                        }
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

        /** @var array<int, InvestorShare> $incoming */
        $incoming = array_map(
            fn (array $row) => InvestorShare::fromArray($row),
            $incomingRaw
        );

        $contract = Contract::with('investors')->findOrFail($contractId);
        $contractValue = (float) $contract->contract_value;

        /** @var array<int,int> $existingIds */
        $existingIds = $contract->investors->pluck('id')->map(fn($id)=>(int)$id)->all();
        /** @var array<int,int> $incomingIds */
        $incomingIds = array_map(fn(InvestorShare $s) => $s->id, $incoming);

        $intersection = array_values(array_intersect($incomingIds, $existingIds));
        if (!empty($intersection)) {
            return response()->json([
                'success' => false,
                'errors'  => ['general' => ['بعض المستثمرين مختارين بالفعل على هذا العقد ولا يمكن إضافتهم مرة أخرى.']]
            ], 422);
        }

        $currentPct = (float) $contract->investors()->sum('contract_investor.share_percentage');
        $newPct     = array_reduce($incoming, fn($c, InvestorShare $s) => $c + $s->sharePercentage, 0.0);
        $afterAdd   = $currentPct + $newPct;

        if ($afterAdd > 100 + self::EPS) {
            return response()->json([
                'success' => false,
                'errors'  => ['general' => ["مجموع نسب المستثمرين لا يجوز أن يتجاوز 100%. المجموع بعد الإضافة: " . round($afterAdd, 2) . "%"]]
            ], 422);
        }

        if (abs($afterAdd - 100) > self::EPS) {
            $remaining = max(0, 100 - $afterAdd);
            return response()->json([
                'success' => false,
                'errors'  => ['general' => ["لا يمكن الحفظ إلا إذا أصبح المجموع 100%. المتبقي الآن: " . round($remaining, 2) . "%"]]
            ], 422);
        }

        $pivotData = [];
        foreach ($incoming as $s) {
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
            $contract->investors()->sync($pivotData, false);

            $this->logInvestorTransaction(
                $contract->fresh('investors'),
                collect($pivotData)
                    ->map(fn($v, $id) => ['id' => (int) $id, 'share_value' => (float) $v['share_value']])
                    ->values()
                    ->all(),
                'إضافة عقد'
            );

            $pivotTable = 'contract_investor';
            $dbSum      = (float) $contract->investors()->sum("$pivotTable.share_percentage");
            $rows       = $contract->investors()->pluck('investors.id')->map(fn($id)=>['id'=>(int)$id])->all();

            $tmp = [];
            $this->applyAutoStatusBySum($tmp, $dbSum, $rows);
            if (!empty($tmp['contract_status_id']) && $tmp['contract_status_id'] != $contract->contract_status_id) {
                $contract->update(['contract_status_id' => $tmp['contract_status_id']]);
            }
        });

        $contract->load('investors');
        $html = view('contracts.partials.investors_table', compact('contract'))->render();

        return response()->json([
            'success' => true,
            'html'    => $html
        ]);
    }

    private function logInvestorTransaction(Contract $contract, array $investors, string $statusName = 'إضافة عقد'): void
    {
        $status = TransactionStatus::where('name', $statusName)
            ->first(['id', 'transaction_type_id']);

        if (!$status) {
            throw new \RuntimeException("الحالة '{$statusName}' غير موجودة.");
        }

        $typeId = $this->getTransactionTypeIdForStatusName($statusName, $status->transaction_type_id ?? null);
        if (!$typeId) {
            throw new \RuntimeException("تعذّر تحديد نوع الحركة للحالة '{$statusName}'.");
        }

        $direction = $this->directionFromTypeId($typeId);
        if (!in_array($direction, ['in','out'], true)) {
            $typeName = TransactionType::whereKey($typeId)->value('name') ?? ('#'.$typeId);
            throw new \RuntimeException("تعذّر استنتاج الاتجاه من اسم النوع '{$typeName}'. عدّل اسم النوع ليشمل (ايداع/سحب) أو أضف مرادفات في الدالة.");
        }

        DB::transaction(function () use ($contract, $investors, $status, $typeId, $direction, $statusName) {
            foreach ($investors as $inv) {
                $investorId = is_array($inv) ? ($inv['id'] ?? null) : ($inv->id ?? null);
                $amount     = (float) (is_array($inv) ? ($inv['share_value'] ?? 0) : ($inv->share_value ?? 0));
                if (!$investorId || $amount <= 0) continue;

                $trx = InvestorTransaction::create([
                    'investor_id'      => $investorId,
                    'contract_id'      => $contract->id,
                    'status_id'        => $status->id,
                    'amount'           => $amount,
                    'transaction_date' => now(),
                    'notes'            => "عملية {$statusName} للعقد رقم {$contract->contract_number}",
                ]);

                LedgerEntry::create([
                    'entry_date'             => now()->toDateString(),
                    'investor_id'            => $investorId,
                    'is_office'              => false,
                    'transaction_status_id'  => $status->id,
                    'transaction_type_id'    => $typeId,
                    'bank_account_id'        => null,
                    'safe_id'                => null,
                    'contract_id'            => $contract->id,
                    'installment_id'         => null,
                    'amount'                 => $amount,
                    'direction'              => $direction,
                    'ref'                    => 'IT-'.$trx->id,
                    'notes'                  => "قيد {$statusName} للعقد #{$contract->contract_number} (مستثمر #{$investorId})",
                ]);
            }
        });
    }

    private function getTransactionTypeIdForStatusName(string $statusName, ?int $statusTypeId = null): ?int
    {
        if ($statusTypeId) return (int)$statusTypeId;

        $typeId = TransactionType::where('name', $statusName)->value('id');
        if ($typeId) return (int)$typeId;

        $map = [
            'إضافة عقد'   => ['استثمار عقد', 'حركة مستثمر', 'عقد جديد'],
            'توزيع أرباح' => ['أرباح', 'حركة مستثمر'],
            'سداد أصل'    => ['سداد أصل', 'تحصيل'],
            'سداد قسط'    => ['تحصيل قسط', 'تحصيل'],
        ];
        foreach (($map[$statusName] ?? []) as $altName) {
            $typeId = TransactionType::where('name', $altName)->value('id');
            if ($typeId) return (int)$typeId;
        }

        return null;
    }

    private function directionFromTypeId(int $typeId): ?string
    {
        $typeName = TransactionType::whereKey($typeId)->value('name');
        return $this->directionFromTypeName($typeName);
    }

    private function directionFromTypeName(?string $typeName): ?string
    {
        $name = $this->arNormalize($typeName);
        if ($name === '') return null;

        $exact = [
            'ايداع' => 'in', 'إيداع' => 'in', 'توريد' => 'in', 'تحصيل' => 'in',
            'سحب'   => 'out', 'صرف'  => 'out', 'توزيع' => 'out', 'استرداد' => 'out',
            'deposit' => 'in', 'withdraw' => 'out',
        ];
        if (isset($exact[$typeName])) {
            return $exact[$typeName];
        }

        if (mb_strpos($name, 'ايداع')!==false || mb_strpos($name, 'توريد')!==false || mb_strpos($name, 'تحصيل')!==false) return 'in';
        if (mb_strpos($name, 'سحب')  !==false || mb_strpos($name, 'صرف')  !==false || mb_strpos($name, 'توزيع')  !==false || mb_strpos($name, 'استرداد')!==false) return 'out';
        if (mb_strpos($name, 'deposit')!==false) return 'in';
        if (mb_strpos($name, 'withdraw')!==false) return 'out';

        return null;
    }

    private function arNormalize(?string $text): string
    {
        if ($text === null) return '';
        $text = trim($text);
        $text = mb_strtolower($text, 'UTF-8');

        $map = [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
            'ة' => 'ه', 'ى' => 'ي',
            'ؤ' => 'و', 'ئ' => 'ي',
        ];
        return strtr($text, $map);
    }

    public function show(Contract $contract)
    {
        $this->updateInstallmentsStatuses($contract);

        $contract->load([
            'customer',
            'guarantor',
            'contractStatus',
            'productType',     // ✅ العلاقة أصبحت productType
            'installmentType',
            'investors',
            'installments.installmentStatus'
        ]);

        $investors = Investor::all();

        $banks = BankAccount::all();
        $safes = Safe::all();


        
        return view('contracts.show', compact('contract', 'investors','banks','safes'));
    }

    private function validateContract(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'customer_id'            => ['required','exists:customers,id'],
            'guarantor_id'           => ['nullable','exists:guarantors,id'],

            // ✅ التحقق من product_types عبر الحقل product_type_id
            'product_type_id'        => ['required','exists:product_types,id'],

            'products_count'         => ['required','integer','min:0'],
            'purchase_price'         => ['required','numeric','min:0'],
            'sale_price'             => ['required','numeric','min:0'],
            'contract_value'         => ['nullable','numeric','min:0'],
            'investor_profit'        => ['required','numeric','min:0'],
            'total_value'            => ['nullable','numeric','min:0'],
            'discount_amount'        => ['nullable','numeric','min:0'],
            'installment_type_id'    => ['required','exists:installment_types,id'],
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
