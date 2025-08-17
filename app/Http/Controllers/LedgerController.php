<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\Investor;
use App\Models\BankAccount;
use App\Models\Safe;
use App\Models\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LedgerController extends Controller
{
    /**
     * IDs فئات الربط في category_transaction_status
     * 1 = المستثمرين ، 4 = المكتب (حسب إعداداتك)
     */
    private int $CAT_INVESTORS = 1;
    private int $CAT_OFFICE    = 4;

    /** أنواع الحركة */
    private const TYPE_IN       = 1; // إيداع
    private const TYPE_OUT      = 2; // سحب
    private const TYPE_TRANSFER = 3; // تحويل

    /** اتجاهات الحركة */
    private const DIR_IN  = 'in';
    private const DIR_OUT = 'out';

    // =======================
    // Index
    // =======================
    public function index(Request $request)
    {
        $base = LedgerEntry::query()
            ->with(['investor', 'bankAccount', 'safe', 'status', 'type'])
            // فئة الجهة (مستثمر/مكتب)
            ->when($request->filled('party_category'), function ($q) use ($request) {
                if ($request->party_category === 'investors') {
                    $q->where('is_office', false);
                } elseif ($request->party_category === 'office') {
                    $q->where('is_office', true);
                }
            })
            // المستثمر
            ->when($request->filled('investor_id'), fn ($q) => $q->where('investor_id', $request->investor_id))
            // الحالة
            ->when($request->filled('status_id'), fn ($q) => $q->where('transaction_status_id', $request->status_id))
            // نوع الحساب
            ->when($request->filled('account_type'), function ($q) use ($request) {
                if ($request->account_type === 'bank') {
                    $q->whereNotNull('bank_account_id')->whereNull('safe_id');
                } elseif ($request->account_type === 'safe') {
                    $q->whereNotNull('safe_id')->whereNull('bank_account_id');
                }
            })
            // المدة بالتاريخ الفعلي للقيّد (شاملة اليومين)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->from))
            ->when($request->filled('to'),   fn ($q) => $q->whereDate('entry_date', '<=', $request->to));

        // مجاميع
        $totIn  = (clone $base)->where('direction', self::DIR_IN)->sum('amount');
        $totOut = (clone $base)->where('direction', self::DIR_OUT)->sum('amount');
        $net    = (float) $totIn - (float) $totOut;

        // بيانات الجدول
        $entries = (clone $base)
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        // بيانات الفلاتر
        $investors          = Investor::orderBy('name')->get();
        $statusesInvestors  = $this->statusesForCategory($this->CAT_INVESTORS);
        $statusesOffice     = $this->statusesForCategory($this->CAT_OFFICE);

        return view('ledger.index', [
            'entries'           => $entries,
            'totIn'             => $totIn,
            'totOut'            => $totOut,
            'net'               => $net,
            'investors'         => $investors,
            'statusesInvestors' => $statusesInvestors,
            'statusesOffice'    => $statusesOffice,
            'filters' => [
                'party_category' => $request->party_category,
                'investor_id'    => $request->investor_id,
                'status_id'      => $request->status_id,
                'account_type'   => $request->account_type,
                'from'           => $request->from,
                'to'             => $request->to,
            ],
        ]);
    }

    // =======================
    // Create (قيد عادي)
    // =======================
    public function create()
    {
        $investors = Investor::orderBy('name')->get();
        $banks     = BankAccount::orderBy('name')->get();
        $safes     = Safe::orderBy('name')->get();

        $statusesByCategory = [
            'investors' => $this->statusesForCategory($this->CAT_INVESTORS),
            'office'    => $this->statusesForCategory($this->CAT_OFFICE),
        ];

        return view('ledger.create', compact('investors', 'banks', 'safes', 'statusesByCategory'));
    }

    // =======================
    // Store (قيد عادي: بنك *أو* خزنة)
    // =======================
    public function store(Request $request)
    {
        $data = $request->validate([
            'party_category'   => ['required', 'in:investors,office'],
            'investor_id'      => ['nullable', 'integer', 'exists:investors,id'],
            'status_id'        => ['required', 'integer', 'exists:transaction_statuses,id'],
            'bank_account_id'  => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'safe_id'          => ['nullable', 'integer', 'exists:safes,id'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ], [], [
            'party_category'   => 'الفئة',
            'investor_id'      => 'المستثمر',
            'status_id'        => 'الحالة',
            'bank_account_id'  => 'الحساب البنكي',
            'safe_id'          => 'الخزنة',
            'amount'           => 'المبلغ',
            'transaction_date' => 'تاريخ العملية',
            'notes'            => 'ملاحظات',
        ]);

        // مستثمر مطلوب إذا الفئة مستثمرين
        if ($data['party_category'] === 'investors' && empty($data['investor_id'])) {
            return back()->withErrors(['investor_id' => 'اختيار المستثمر مطلوب لفئة المستثمرين'])->withInput();
        }

        // لازم واحد فقط: بنك *أو* خزنة
        $hasBank = !empty($data['bank_account_id']);
        $hasSafe = !empty($data['safe_id']);
        if (!$hasBank && !$hasSafe) {
            return back()->withErrors(['bank_account_id' => 'اختر حساب بنكي أو خزنة واحدة'])->withInput();
        }
        if ($hasBank && $hasSafe) {
            return back()->withErrors([
                'bank_account_id' => 'لا يمكن اختيار بنك وخزنة في نفس القيد. استخدم "قيد مُجزّأ" لو أردت التوزيع.',
                'safe_id'         => 'لا يمكن اختيار بنك وخزنة في نفس القيد.',
            ])->withInput();
        }

        // تأكيد أن الحالة مرتبطة بالفئة
        $categoryId = $data['party_category'] === 'investors' ? $this->CAT_INVESTORS : $this->CAT_OFFICE;
        if (!$this->statusAllowedForCategory($categoryId, (int)$data['status_id'])) {
            return back()->withErrors(['status_id' => 'هذه الحالة غير مرتبطة بالفئة المختارة'])->withInput();
        }

        $status  = TransactionStatus::findOrFail($data['status_id']);
        $typeId  = (int) $status->transaction_type_id;

        // حالة التحويل الداخلي لا تُسجَّل من هذا النموذج
        if ($typeId === self::TYPE_TRANSFER) {
            return back()->withErrors(['status_id' => 'استخدم نموذج "تحويل داخلي" لهذه الحالة.'])->withInput();
        }

        $direction = $this->directionFromType($typeId);
        $amount    = round((float) $data['amount'], 2);

        DB::transaction(function () use ($data, $typeId, $direction, $amount) {
            // ملاحظة ذكية
            $note = $this->buildSmartNote(
                partyCategory: $data['party_category'],
                investorId: $data['investor_id'] ?? null,
                statusId: $data['status_id'],
                direction: $direction,
                accountLabel: $data['bank_account_id']
                    ? ('بنك: ' . optional(BankAccount::find($data['bank_account_id']))->name)
                    : ('خزنة: ' . optional(Safe::find($data['safe_id']))->name),
                amount: $amount,
                baseNote: $data['notes'] ?? null
            );

            LedgerEntry::create([
                'entry_date'            => $data['transaction_date'],
                'investor_id'           => $data['party_category'] === 'investors' ? $data['investor_id'] : null,
                'is_office'             => $data['party_category'] === 'office',
                'transaction_status_id' => $data['status_id'],
                'transaction_type_id'   => $typeId,
                'bank_account_id'       => $data['bank_account_id'] ?? null,
                'safe_id'               => $data['safe_id'] ?? null,
                'amount'                => $amount,
                'direction'             => $direction,
                'notes'                 => $note,
            ]);

            // TODO: تحديث أرصدة bank_accounts / safes (إن كان لديك أعمدة رصيد).
        });

        return redirect()->route('ledger.index')->with('success', 'تم إضافة القيد بنجاح');
    }

    // =======================
    // تحويل داخلي (مكتب فقط)
    // =======================
    public function transferCreate()
    {
        $banks = BankAccount::orderBy('name')->get();
        $safes = Safe::orderBy('name')->get();

        return view('ledger.transfer', compact('banks', 'safes'));
    }

    public function transferStore(Request $request)
    {
        $data = $request->validate([
            'from_type'            => ['required', 'in:bank,safe'],
            'from_bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'from_safe_id'         => ['nullable', 'integer', 'exists:safes,id'],

            'to_type'              => ['required', 'in:bank,safe'],
            'to_bank_account_id'   => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'to_safe_id'           => ['nullable', 'integer', 'exists:safes,id'],

            'amount'               => ['required', 'numeric', 'min:0.01'],
            'transaction_date'     => ['required', 'date_format:Y-m-d'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ]);

        // تحقق الحقول حسب النوع
        if ($data['from_type'] === 'bank' && empty($data['from_bank_account_id'])) {
            return back()->withErrors(['from_bank_account_id' => 'اختر الحساب البنكي المحوَّل منه'])->withInput();
        }
        if ($data['from_type'] === 'safe' && empty($data['from_safe_id'])) {
            return back()->withErrors(['from_safe_id' => 'اختر الخزنة المحوَّل منها'])->withInput();
        }
        if ($data['to_type'] === 'bank' && empty($data['to_bank_account_id'])) {
            return back()->withErrors(['to_bank_account_id' => 'اختر الحساب البنكي المحوَّل إليه'])->withInput();
        }
        if ($data['to_type'] === 'safe' && empty($data['to_safe_id'])) {
            return back()->withErrors(['to_safe_id' => 'اختر الخزنة المحوَّل إليها'])->withInput();
        }

        // منع التحويل لنفس الحساب
        $sameBank = $data['from_type'] === 'bank' && $data['to_type'] === 'bank'
            && (int) $data['from_bank_account_id'] === (int) $data['to_bank_account_id'];
        $sameSafe = $data['from_type'] === 'safe' && $data['to_type'] === 'safe'
            && (int) $data['from_safe_id'] === (int) $data['to_safe_id'];
        if ($sameBank || $sameSafe) {
            return back()->withErrors(['to_type' => 'لا يمكن التحويل لنفس الحساب'])->withInput();
        }

        // حالة "تحويل بين حسابات" للمكتب
        $transferStatusId = $this->resolveTransferStatusId();
        if (!$transferStatusId) {
            return back()->withErrors([
                'status' => 'لم يتم إعداد حالة "تحويل بين حسابات" للمكتب. أضِفها في transaction_statuses واربطها بـ category_transaction_status.'
            ])->withInput();
        }

        $amount = round((float)$data['amount'], 2);

        DB::transaction(function () use ($data, $transferStatusId, $amount) {
            // ملاحظات ذكية
            $fromLabel = $data['from_type'] === 'bank'
                ? 'بنك: ' . optional(BankAccount::find($data['from_bank_account_id']))->name
                : 'خزنة: ' . optional(Safe::find($data['from_safe_id']))->name;

            $toLabel = $data['to_type'] === 'bank'
                ? 'بنك: ' . optional(BankAccount::find($data['to_bank_account_id']))->name
                : 'خزنة: ' . optional(Safe::find($data['to_safe_id']))->name;

            $noteOut = $this->buildSmartNote(
                partyCategory: 'office',
                investorId: null,
                statusId: $transferStatusId,
                direction: self::DIR_OUT,
                accountLabel: $fromLabel,
                amount: $amount,
                baseNote: $data['notes'] ?? 'تحويل داخلي بين حسابات المكتب (خروج)'
            );

            $noteIn = $this->buildSmartNote(
                partyCategory: 'office',
                investorId: null,
                statusId: $transferStatusId,
                direction: self::DIR_IN,
                accountLabel: $toLabel,
                amount: $amount,
                baseNote: $data['notes'] ?? 'تحويل داخلي بين حسابات المكتب (دخول)'
            );

            // خروج من الحساب المصدر
            LedgerEntry::create([
                'entry_date'            => $data['transaction_date'],
                'investor_id'           => null,
                'is_office'             => true,
                'transaction_status_id' => $transferStatusId,
                'transaction_type_id'   => self::TYPE_TRANSFER,
                'bank_account_id'       => $data['from_type'] === 'bank' ? ($data['from_bank_account_id'] ?? null) : null,
                'safe_id'               => $data['from_type'] === 'safe' ? ($data['from_safe_id'] ?? null) : null,
                'amount'                => $amount,
                'direction'             => self::DIR_OUT,
                'notes'                 => $noteOut,
            ]);

            // دخول للحساب الوجهة
            LedgerEntry::create([
                'entry_date'            => $data['transaction_date'],
                'investor_id'           => null,
                'is_office'             => true,
                'transaction_status_id' => $transferStatusId,
                'transaction_type_id'   => self::TYPE_TRANSFER,
                'bank_account_id'       => $data['to_type'] === 'bank' ? ($data['to_bank_account_id'] ?? null) : null,
                'safe_id'               => $data['to_type'] === 'safe' ? ($data['to_safe_id'] ?? null) : null,
                'amount'                => $amount,
                'direction'             => self::DIR_IN,
                'notes'                 => $noteIn,
            ]);

            // TODO: تحديث أرصدة الحسابات/الخزن لو عندك.
        });

        return redirect()->route('ledger.index')->with('success', 'تم تنفيذ التحويل الداخلي بنجاح');
    }

    // =======================
    // قيد مُجزّأ (جزء بنك + جزء خزنة)
    // =======================
    public function splitCreate()
    {
        $investors = Investor::orderBy('name')->get();
        $banks     = BankAccount::orderBy('name')->get();
        $safes     = Safe::orderBy('name')->get();

        $statusesByCategory = [
            'investors' => $this->statusesForCategory($this->CAT_INVESTORS),
            'office'    => $this->statusesForCategory($this->CAT_OFFICE),
        ];

        return view('ledger.split', compact('investors', 'banks', 'safes', 'statusesByCategory'));
    }

    public function splitStore(Request $request)
    {
        $data = $request->validate([
            'party_category'   => ['required', 'in:investors,office'],
            'investor_id'      => ['nullable', 'integer', 'exists:investors,id'],
            'status_id'        => ['required', 'integer', 'exists:transaction_statuses,id'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'bank_share'       => ['nullable', 'numeric', 'min:0'],
            'safe_share'       => ['nullable', 'numeric', 'min:0'],
            'bank_account_id'  => ['nullable', 'integer', 'exists:bank_accounts,id'],
            'safe_id'          => ['nullable', 'integer', 'exists:safes,id'],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ], [], [
            'party_category'   => 'الفئة',
            'investor_id'      => 'المستثمر',
            'status_id'        => 'الحالة',
            'amount'           => 'إجمالي المبلغ',
            'bank_share'       => 'المبلغ (بنك)',
            'safe_share'       => 'المبلغ (خزنة)',
            'bank_account_id'  => 'الحساب البنكي',
            'safe_id'          => 'الخزنة',
            'transaction_date' => 'تاريخ العملية',
            'notes'            => 'ملاحظات',
        ]);

        $bank  = round((float)($data['bank_share'] ?? 0), 2);
        $safe  = round((float)($data['safe_share'] ?? 0), 2);
        $total = round((float)$data['amount'], 2);

        // تحقق إضافي
        $errors = [];

        if ($data['party_category'] === 'investors' && empty($data['investor_id'])) {
            $errors['investor_id'] = 'اختيار المستثمر مطلوب لفئة المستثمرين';
        }

        if ($bank <= 0 && $safe <= 0) {
            $errors['bank_share'] = 'أدخل قيمة في البنك أو الخزنة على الأقل';
            $errors['safe_share'] = 'أدخل قيمة في البنك أو الخزنة على الأقل';
        }

        if (round($bank + $safe, 2) !== $total) {
            $errors['amount'] = 'يجب أن يساوي مجموع (بنك + خزنة) إجمالي المبلغ';
        }

        if ($bank > 0 && empty($data['bank_account_id'])) {
            $errors['bank_account_id'] = 'اختر الحساب البنكي لهذا الجزء';
        }
        if ($safe > 0 && empty($data['safe_id'])) {
            $errors['safe_id'] = 'اختر الخزنة لهذا الجزء';
        }

        // تأكيد أن الحالة مسموح بها للفئة
        $categoryId = $data['party_category'] === 'investors' ? $this->CAT_INVESTORS : $this->CAT_OFFICE;
        if (!$this->statusAllowedForCategory($categoryId, (int)$data['status_id'])) {
            $errors['status_id'] = 'هذه الحالة غير مرتبطة بالفئة المختارة';
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        $status    = TransactionStatus::findOrFail($data['status_id']);
        $typeId    = (int) $status->transaction_type_id;
        $direction = $this->directionFromType($typeId);

        DB::transaction(function () use ($data, $bank, $safe, $typeId, $direction) {
            $statusName = optional(TransactionStatus::find($data['status_id']))->name ?? 'عملية';
            $partyLabel = $data['party_category'] === 'office'
                ? 'المكتب'
                : ('المستثمر: ' . (optional(Investor::find($data['investor_id']))->name ?? ('#' . $data['investor_id'])));
            $dirLabel   = $direction === self::DIR_IN ? 'داخل' : 'خارج';

            // جزء البنك
            if ($bank > 0) {
                $bankName = optional(BankAccount::find($data['bank_account_id']))->name;
                $noteBank = $this->buildSmartNote(
                    partyCategory: $data['party_category'],
                    investorId: $data['investor_id'] ?? null,
                    statusId: $data['status_id'],
                    direction: $direction,
                    accountLabel: "بنك: {$bankName}",
                    amount: $bank,
                    baseNote: $data['notes'] ?? null
                );

                LedgerEntry::create([
                    'entry_date'            => $data['transaction_date'],
                    'investor_id'           => $data['party_category'] === 'investors' ? $data['investor_id'] : null,
                    'is_office'             => $data['party_category'] === 'office',
                    'transaction_status_id' => $data['status_id'],
                    'transaction_type_id'   => $typeId,
                    'bank_account_id'       => $data['bank_account_id'],
                    'safe_id'               => null,
                    'amount'                => $bank,
                    'direction'             => $direction,
                    'notes'                 => $noteBank,
                ]);
            }

            // جزء الخزنة
            if ($safe > 0) {
                $safeName = optional(Safe::find($data['safe_id']))->name;
                $noteSafe = $this->buildSmartNote(
                    partyCategory: $data['party_category'],
                    investorId: $data['investor_id'] ?? null,
                    statusId: $data['status_id'],
                    direction: $direction,
                    accountLabel: "خزنة: {$safeName}",
                    amount: $safe,
                    baseNote: $data['notes'] ?? null
                );

                LedgerEntry::create([
                    'entry_date'            => $data['transaction_date'],
                    'investor_id'           => $data['party_category'] === 'investors' ? $data['investor_id'] : null,
                    'is_office'             => $data['party_category'] === 'office',
                    'transaction_status_id' => $data['status_id'],
                    'transaction_type_id'   => $typeId,
                    'bank_account_id'       => null,
                    'safe_id'               => $data['safe_id'],
                    'amount'                => $safe,
                    'direction'             => $direction,
                    'notes'                 => $noteSafe,
                ]);
            }
        });

        return redirect()->route('ledger.index')->with('success', 'تم حفظ القيد المُجزّأ بنجاح');
    }

    // =======================
    // Helpers
    // =======================
    /** جلب الحالات المرتبطة بفئة محددة */
    private function statusesForCategory(int $categoryId)
    {
        return TransactionStatus::whereIn('id', function ($q) use ($categoryId) {
                $q->select('transaction_status_id')
                  ->from('category_transaction_status')
                  ->where('category_id', $categoryId);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'transaction_type_id']);
    }

    /** التأكد من ربط الحالة بالفئة */
    private function statusAllowedForCategory(int $categoryId, int $statusId): bool
    {
        return DB::table('category_transaction_status')
            ->where('category_id', $categoryId)
            ->where('transaction_status_id', $statusId)
            ->exists();
    }

    /** اتجاه الحركة من نوعها */
    private function directionFromType(int $typeId): string
    {
        return $typeId === self::TYPE_IN ? self::DIR_IN : self::DIR_OUT;
    }

    /** توليد ملاحظة ذكية موحّدة */
    private function buildSmartNote(
        string $partyCategory,
        ?int $investorId,
        int $statusId,
        string $direction,
        string $accountLabel,
        float $amount,
        ?string $baseNote = null
    ): string {
        $statusName = optional(TransactionStatus::find($statusId))->name ?? 'عملية';
        $partyLabel = $partyCategory === 'office'
            ? 'المكتب'
            : ('المستثمر: ' . (optional(Investor::find($investorId))->name ?? ('#' . $investorId)));
        $dirLabel   = $direction === self::DIR_IN ? 'داخل' : 'خارج';

        $prefix = $baseNote ? (trim($baseNote) . ' — ') : '';
        return trim($prefix . "{$partyLabel} | {$statusName} | {$dirLabel} | {$accountLabel} | مبلغ: " . number_format($amount, 2));
    }

    /** حالة تحويل بين حسابات للمكتب */
    private function resolveTransferStatusId(): ?int
    {
        return DB::table('transaction_statuses as ts')
            ->join('category_transaction_status as cts', 'cts.transaction_status_id', '=', 'ts.id')
            ->where('ts.transaction_type_id', self::TYPE_TRANSFER) // تحويل
            ->where('cts.category_id', $this->CAT_OFFICE)          // فئة المكتب
            ->value('ts.id');
    }
}
