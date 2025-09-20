<?php

namespace Modules\Contracts\Http\Controllers;

use Modules\Investors\DTOs\InvestorShare;
use App\Http\Controllers\Controller;
use Modules\Accounts\Entities\BankAccount;
use Modules\Customers\Entities\Customer;
use Modules\Guarantors\Entities\Guarantor;
use Modules\Lookups\Entities\InstallmentStatus;
use Modules\Lookups\Entities\InstallmentType;
use Modules\Lookups\Entities\Claimant;
use Modules\Lookups\Entities\ClaimPayer;
use Modules\Lookups\Entities\ClaimStatus;
use App\Models\LedgerEntry;
use Modules\Lookups\Entities\ProductType;
use Modules\Accounts\Entities\Safe;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\TransactionType;
use App\Services\InstallmentsMonthlyService;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractInstallment;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Contracts\Support\ContractStatusNames;
use Modules\Contracts\Support\InvestorShareValidationException;
use Modules\Contracts\Support\InvestorShareValidator;
use Modules\Contracts\Services\ContractStatusRefresher;
use Modules\Contracts\Services\InvestorTransactionLogger;
use Modules\Contracts\Http\Requests\StoreContractInvestorsRequest;
use Modules\Investors\Entities\Investor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ContractController extends Controller
{
    private const EPS = 0.0001;

    public function __construct(
        private InvestorTransactionLogger $investorTransactionLogger,
        private InvestorShareValidator $investorShareValidator,
        private ContractStatusRefresher $contractStatusRefresher
    )
    {
    }

    private const DIR_CONTRACT_MAIN        = 'contracts/contracts';
    private const DIR_CONTRACT_CUSTOMERS   = 'contracts/customers';
    private const DIR_CONTRACT_GUARANTORS  = 'contracts/guarantors';

    public function index(Request $request)
    {
        $perPage     = 20;
        $currentPage = max((int) $request->get('page', 1), 1);

        $pivotTable = (new Contract)->investors()->getTable();

        // الاستعلام الأساسي للعقود
        $contractsBaseQuery = Contract::query();

        // فلترة حسب العميل
        if ($request->filled('customer')) {
            $name = trim($request->customer);
            $contractsBaseQuery->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$name}%"));
        }

        // فلترة حسب المستثمر
        if ($request->filled('investor_id')) {
            $investorId = $request->investor_id;
            if ($investorId === '_none') {
                $contractsBaseQuery->doesntHave('investors');
            } else {
                $contractsBaseQuery->whereHas('investors', fn($q) => $q->where('investors.id', $investorId)
                    ->where($pivotTable . '.share_percentage', '<=', 100));
            }
        } elseif ($request->filled('investor')) {
            $name = trim($request->investor);
            $contractsBaseQuery->whereHas('investors', fn($q) => $q->where('investors.name', 'like', "%{$name}%")
                ->where($pivotTable . '.share_percentage', '<=', 100));
        }

        // فلترة حسب رقم العقد
        if ($request->filled('contract_number')) {
            $number = trim($request->contract_number);
            $contractsBaseQuery->where('contract_number', 'like', "%{$number}%");
        }

        // فلترة حسب حالة العقد
        if ($request->filled('status')) {
            $contractsBaseQuery->where('contract_status_id', $request->status);
        }

        // فلترة حسب التواريخ
        if ($request->filled('from')) {
            $contractsBaseQuery->whereDate('start_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $contractsBaseQuery->whereDate('start_date', '<=', $request->to);
        }

        $contractsOrderedQuery = (clone $contractsBaseQuery)->latest();

        $updatedIds = [];
        $maxUpdates = $perPage * 5;
        $attempts   = 0;

        // نحدّث حالات العقود للصفحة الحالية فقط مع حد أقصى للتكرار
        // لضمان عدم المرور على كامل السجلّات دفعة واحدة.

        while ($attempts < 5) {
            $attempts++;

            $pageIds = (clone $contractsOrderedQuery)
                ->select('contracts.id')
                ->forPage($currentPage, $perPage)
                ->pluck('contracts.id')
                ->map(fn($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($pageIds)) {
                break;
            }

            $newIds = array_values(array_diff($pageIds, $updatedIds));

            if (empty($newIds)) {
                break;
            }

            $this->contractStatusRefresher->refresh($newIds);

            $updatedIds = array_values(array_unique(array_merge($updatedIds, $newIds)));

            if (count($updatedIds) >= $maxUpdates) {
                break;
            }
        }

        $contractRelations = ['customer', 'guarantor', 'contractStatus', 'productType', 'investors'];

        $contracts = $contractsOrderedQuery
            ->with($contractRelations)
            ->paginate($perPage);

        $finalPageIds = $contracts->getCollection()
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $remainingIds = array_values(array_diff($finalPageIds, $updatedIds));

        if (!empty($remainingIds)) {
            $this->contractStatusRefresher->refresh($remainingIds);

            $updatedIds = array_values(array_unique(array_merge($updatedIds, $remainingIds)));
        }

        $contracts->setCollection(
            $contracts->getCollection()->map(function (Contract $contract) use ($contractRelations) {
                $fresh = $contract->fresh($contractRelations);

                if ($fresh instanceof Contract) {
                    return $fresh;
                }

                return $contract->loadMissing($contractRelations);
            })
        );

        $investors = Investor::orderBy('name')->get(['id', 'name']);
        $contractStatuses = ContractStatus::orderBy('name')->get(['id', 'name']);

        return view('contracts::index', compact(
            'contracts',
            'contractStatuses',
            'investors'
        ));
    }


    public function refreshStatuses(): RedirectResponse
    {
        $this->contractStatusRefresher->refresh();

        return back()->with(
            'success',
            __('contracts::contracts.All Contract Statuses Refreshed')
        );
    }

    public function dashboard(Request $request, InstallmentsMonthlyService $installmentsSvc)
    {
        $contractStatuses = ContractStatus::orderBy('name')->get(['id', 'name']);
        $investors        = Investor::orderBy('name')->get(['id', 'name']);

        $investorIdForMonthly = ($request->filled('investor_id') && $request->investor_id !== '_none')
            ? (int) $request->investor_id
            : null;

        $installmentsMonthly = $this->buildInstallmentsMonthly($request, $installmentsSvc, $investorIdForMonthly);
        $dashboardStats      = $this->buildContractDashboardStats();
        $statusDistribution  = $this->buildContractStatusDistribution();

        $selectedInvestor = $investorIdForMonthly
            ? $investors->firstWhere('id', $investorIdForMonthly)
            : null;

        $currencySymbol = 'ر.س';

        return view('contracts::dashboard', [
            'contractStatuses'   => $contractStatuses,
            'investors'          => $investors,
            'installmentsMonthly'=> $installmentsMonthly,
            'dashboardStats'     => $dashboardStats,
            'selectedInvestor'   => $selectedInvestor,
            'currencySymbol'     => $currencySymbol,
            'contractStatusMetrics'    => $statusDistribution['statuses'] ?? [],
            'contractStatusChartLabels'=> $statusDistribution['chart']['labels'] ?? [],
            'contractStatusChartData'  => $statusDistribution['chart']['data'] ?? [],
            'contractStatusTotal'      => $statusDistribution['total'] ?? 0,
            'raisedContractsCount'     => $statusDistribution['raised'] ?? 0,
            'requiredContractsCount'   => $statusDistribution['required'] ?? 0,
        ]);
    }


    public function create()
    {
        return view('contracts::create', [
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
                    $entries = [];
                    foreach ($investors as $row) {
                        $entries[] = [
                            'investor_id' => (int) ($row['id'] ?? 0),
                            'amount'      => (float) ($row['share_value'] ?? 0),
                        ];
                    }

                    $this->investorTransactionLogger->log($contract, $entries, 'إضافة عقد');
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

            $entries = [];
            foreach ($pivotData as $investorId => $row) {
                $entries[] = [
                    'investor_id' => (int) $investorId,
                    'amount'      => (float) ($row['share_value'] ?? 0),
                ];
            }

            $this->investorTransactionLogger->log($contract, $entries, 'إضافة عقد');

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
        $html = view('contracts::partials.investors_table', compact('contract'))->render();

        return response()->json([
            'success' => true,
            'html'    => $html
        ]);
    }

    public function show(Contract $contract)
    {
        $this->contractStatusRefresher->refreshContract($contract);

        $contract->load([
            'customer',
            'guarantor',
            'contractStatus',
            'productType',     // ✅ العلاقة أصبحت productType
            'installmentType',
            'investors',
            'installments.installmentStatus',
            'claims' => fn ($query) => $query
                ->orderByDesc('claim_date')
                ->orderByDesc('id'),
            'claims.claimant:id,name',
            'claims.claimStatus:id,name',
            'claims.payments.claimPayer:id,name',
        ]);

        $investors = Investor::all();

        $banks = BankAccount::orderBy('name')->get(['id', 'name']);
        $safes = Safe::orderBy('name')->get(['id', 'name']);

        $claimants = Claimant::orderBy('name')->get(['id', 'name']);
        $claimStatuses = ClaimStatus::orderBy('name')->get(['id', 'name']);
        $claimPayers = ClaimPayer::orderBy('name')->get(['id', 'name']);
        $changeStatusOptions = $claimStatuses
            ->filter(fn ($status) => in_array($status->name, ['مقبول', 'مرفوض'], true))
            ->values();
        $paidWithDiscountClaimStatusId = optional($claimStatuses
            ->first(fn ($status) => in_array($status->name, ['مدفوع بخصم', 'مسدد بخصم'], true))
        )->id;

        return view('contracts::show', compact(
            'contract',
            'investors',
            'banks',
            'safes',
            'claimants',
            'claimStatuses',
            'claimPayers',
            'changeStatusOptions',
            'paidWithDiscountClaimStatusId'
        ));
    }

    public function updateImages(Request $request, Contract $contract)
    {
        $request->validate([
            'contract_image'           => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'contract_customer_image'  => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
            'contract_guarantor_image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:4096'],
        ]);

        if (
            !$request->hasFile('contract_image') &&
            !$request->hasFile('contract_customer_image') &&
            !$request->hasFile('contract_guarantor_image')
        ) {
            return back()
                ->withErrors(['contract_image' => __('Please select at least one image to upload.')])
                ->withInput();
        }

        $updates = [];

        if ($path = $this->putImage($request, 'contract_image', self::DIR_CONTRACT_MAIN, $contract->contract_image)) {
            $updates['contract_image'] = $path;
        }

        if ($path = $this->putImage($request, 'contract_customer_image', self::DIR_CONTRACT_CUSTOMERS, $contract->contract_customer_image)) {
            $updates['contract_customer_image'] = $path;
        }

        if ($path = $this->putImage($request, 'contract_guarantor_image', self::DIR_CONTRACT_GUARANTORS, $contract->contract_guarantor_image)) {
            $updates['contract_guarantor_image'] = $path;
        }

        if (!empty($updates)) {
            $contract->update($updates);
        }

        return redirect()
            ->route('contracts.show', $contract)
            ->with('success', __('Contract images updated successfully.'));
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
            if ($id = $this->resolveStatusIdByName(ContractStatusNames::NO_INVESTORS)) {
                $data['contract_status_id'] = $id;
            }
            return;
        }

        if ($sumRounded < (100 - self::EPS)) {
            if ($id = $this->resolveStatusIdByName(ContractStatusNames::PENDING)) {
                $data['contract_status_id'] = $id;
            }
            return;
        }

        if ($id = $this->resolveStatusIdByName(ContractStatusNames::NEW)) {
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
        try {
            $sum = $this->investorShareValidator->validate($investors);
        } catch (InvestorShareValidationException $e) {
            throw $this->convertInvestorShareValidationException($e);
        }

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

    private function convertInvestorShareValidationException(InvestorShareValidationException $e): ValidationException
    {
        $index = $e->index();
        $fieldName = $e->field();
        $field = match ($fieldName) {
            null, 'share_percentage', '' => 'share_percentage',
            'pct' => 'share_percentage',
            default => $fieldName,
        };

        if ($index !== null) {
            return ValidationException::withMessages([
                "investors.$index.$field" => $e->getMessage(),
            ]);
        }

        return ValidationException::withMessages([
            'investors' => $e->getMessage(),
        ]);
    }

    private function buildInstallmentsMonthly(
        Request $request,
        InstallmentsMonthlyService $installmentsSvc,
        ?int $investorIdForMonthly = null
    ): array {
        $m = $request->integer('m') ?: null;
        $y = $request->integer('y') ?: null;
        $exclude = ['مؤجل', 'معتذر'];

        try {
            if ($investorIdForMonthly) {
                if (method_exists($installmentsSvc, 'buildForInvestor')) {
                    $investorModel = Investor::find($investorIdForMonthly);

                    return (array) $installmentsSvc->buildForInvestor(
                        $investorModel ?: $investorIdForMonthly,
                        $m,
                        $y,
                        $exclude
                    );
                }

                return (array) $installmentsSvc->build($m, $y, $exclude, $investorIdForMonthly);
            }

            return (array) $installmentsSvc->build($m, $y, $exclude);
        } catch (\ArgumentCountError) {
            return (array) $installmentsSvc->build($m, $y, $exclude);
        }
    }

    private function buildContractDashboardStats(): array
    {
        $namesEnded   = ['منتهي', 'سداد مبكر'];
        $namesPending = ['معلق'];

        $statusIdCol = null;
        foreach (['status_id', 'contract_status_id', 'state_id'] as $col) {
            if (Schema::hasColumn('contracts', $col)) {
                $statusIdCol = $col;
                break;
            }
        }

        $statusTextCol = null;
        foreach (['status', 'state'] as $col) {
            if (Schema::hasColumn('contracts', $col)) {
                $statusTextCol = $col;
                break;
            }
        }

        $endedIds = $pendingIds = [];
        if (class_exists(ContractStatus::class)) {
            $endedIds   = ContractStatus::whereIn('name', $namesEnded)->pluck('id')->all();
            $pendingIds = ContractStatus::whereIn('name', $namesPending)->pluck('id')->all();
        }

        $contractsTotalAll      = (int) Contract::query()->count();
        $contractsEndedAll      = 0;
        $contractsPendingAll    = 0;
        $contractsNoInvestorAll = (int) Contract::query()->doesntHave('investors')->count();

        if ($statusIdCol && !empty($endedIds)) {
            $contractsEndedAll = (int) Contract::query()->whereIn($statusIdCol, $endedIds)->count();
        } elseif ($statusTextCol) {
            $contractsEndedAll = (int) Contract::query()->whereIn($statusTextCol, $namesEnded)->count();
        }

        if ($statusIdCol && !empty($pendingIds)) {
            $contractsPendingAll = (int) Contract::query()->whereIn($statusIdCol, $pendingIds)->count();
        } elseif ($statusTextCol) {
            $contractsPendingAll = (int) Contract::query()->whereIn($statusTextCol, $namesPending)->count();
        }

        $contractsActiveAll = $contractsTotalAll;
        if ($statusIdCol) {
            $excludeIds = array_filter(array_merge($endedIds, $pendingIds));
            if (!empty($excludeIds)) {
                $contractsActiveAll = (int) Contract::query()->whereNotIn($statusIdCol, $excludeIds)->count();
            }
        } elseif ($statusTextCol) {
            $excludeNames = array_filter(array_merge($namesEnded, $namesPending));
            if (!empty($excludeNames)) {
                $contractsActiveAll = (int) Contract::query()->whereNotIn($statusTextCol, $excludeNames)->count();
            }
        }

        $pct = static function (int $total, int $value): float {
            if ($total <= 0) {
                return 0.0;
            }

            return round(($value / $total) * 100, 1);
        };

        return [
            'names' => [
                'ended'   => $namesEnded,
                'pending' => $namesPending,
            ],
            'labels' => [
                'ended'   => count($namesEnded) ? implode('، ', $namesEnded) : '—',
                'pending' => count($namesPending) ? implode('، ', $namesPending) : '—',
            ],
            'counts' => [
                'total'      => $contractsTotalAll,
                'active'     => $contractsActiveAll,
                'pending'    => $contractsPendingAll,
                'noInvestor' => $contractsNoInvestorAll,
                'ended'      => $contractsEndedAll,
            ],
            'percentages' => [
                'active'     => $pct($contractsTotalAll, $contractsActiveAll),
                'pending'    => $pct($contractsTotalAll, $contractsPendingAll),
                'noInvestor' => $pct($contractsTotalAll, $contractsNoInvestorAll),
                'ended'      => $pct($contractsTotalAll, $contractsEndedAll),
            ],
        ];
    }

    private function buildContractStatusDistribution(): array
    {
        $contractsTotal = Contract::count();

        $statusCounts = Contract::select('contract_status_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('contract_status_id')
            ->get();

        $statusNames = ContractStatus::pluck('name', 'id');

        $statuses = $statusCounts->map(function ($row) use ($statusNames, $contractsTotal) {
            $name = $statusNames[$row->contract_status_id] ?? 'غير محدد';
            $cnt  = (int) $row->cnt;
            $pct  = $contractsTotal > 0 ? round(($cnt / $contractsTotal) * 100, 2) : 0.0;

            return [
                'id'    => (int) $row->contract_status_id,
                'name'  => $name,
                'count' => $cnt,
                'pct'   => $pct,
            ];
        })->sortByDesc('count')->values();

        $chartLabels = $statuses->pluck('name')->values()->all();
        $chartData   = $statuses->pluck('count')->values()->all();

        $normalize = static function ($value): string {
            return mb_strtolower(trim((string) $value), 'UTF-8');
        };

        $raisedNames    = array_map($normalize, ['مرفوع فيه', 'مرفوع', 'raised', 'raised status']);
        $requiredNames  = array_map($normalize, ['مطلوب', 'required']);

        $raisedCount = 0;
        $requiredCount = 0;

        foreach ($statuses as $status) {
            $name = $normalize($status['name'] ?? '');

            if (in_array($name, $raisedNames, true)) {
                $raisedCount += (int) ($status['count'] ?? 0);
            }

            if (in_array($name, $requiredNames, true)) {
                $requiredCount += (int) ($status['count'] ?? 0);
            }
        }

        return [
            'total'    => $contractsTotal,
            'statuses' => $statuses->toArray(),
            'chart'    => [
                'labels' => $chartLabels,
                'data'   => $chartData,
            ],
            'raised'   => $raisedCount,
            'required' => $requiredCount,
        ];
    }
}
