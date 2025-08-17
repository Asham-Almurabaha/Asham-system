<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\Investor;
use App\Models\BankAccount;
use App\Models\Safe;
use App\Models\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LedgerController extends Controller
{
    /**
     * IDs فئات الربط في category_transaction_status
     * 1 = المستثمرين ، 4 = المكتب (حسب الإعدادات اللي عندك)
     */
    private int $CAT_INVESTORS = 1;
    private int $CAT_OFFICE    = 4;

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
                    $q->whereNotNull('bank_account_id');
                } elseif ($request->account_type === 'safe') {
                    $q->whereNotNull('safe_id');
                }
            })
            // المدة بالتاريخ الفعلي للقيّد
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->from))
            ->when($request->filled('to'),   fn ($q) => $q->whereDate('entry_date', '<=', $request->to));

        // مجاميع
        $totIn  = (clone $base)->where('direction', 'in')->sum('amount');
        $totOut = (clone $base)->where('direction', 'out')->sum('amount');
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
            'transaction_date' => ['required', 'date'],
            'notes'            => ['nullable', 'string'],
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

        if ($data['party_category'] === 'investors' && empty($data['investor_id'])) {
            return back()->withErrors(['investor_id' => 'اختيار المستثمر مطلوب لفئة المستثمرين'])->withInput();
        }
        if (empty($data['bank_account_id']) && empty($data['safe_id'])) {
            return back()->withErrors(['bank_account_id' => 'اختر حساب بنكي أو خزنة واحدة على الأقل'])->withInput();
        }

        // تأكيد أن الحالة مرتبطة بالفئة
        $categoryId = $data['party_category'] === 'investors' ? $this->CAT_INVESTORS : $this->CAT_OFFICE;
        $allowed = DB::table('category_transaction_status')
            ->where('category_id', $categoryId)
            ->where('transaction_status_id', $data['status_id'])
            ->exists();

        if (!$allowed) {
            return back()->withErrors(['status_id' => 'هذه الحالة غير مرتبطة بالفئة المختارة'])->withInput();
        }

        $status = TransactionStatus::findOrFail($data['status_id']);
        $typeId = (int) $status->transaction_type_id;

        // حالة التحويل الداخلي لا تُسجَّل من هذا النموذج
        if ($typeId === 3) {
            return back()->withErrors(['status_id' => 'استخدم نموذج "تحويل داخلي" لهذه الحالة.'])->withInput();
        }

        $direction = $typeId === 1 ? 'in' : 'out';

        DB::transaction(function () use ($data, $typeId, $direction) {
            LedgerEntry::create([
                'entry_date'            => $data['transaction_date'],
                'investor_id'           => $data['party_category'] === 'investors' ? $data['investor_id'] : null,
                'is_office'             => $data['party_category'] === 'office',
                'transaction_status_id' => $data['status_id'],
                'transaction_type_id'   => $typeId,
                'bank_account_id'       => $data['bank_account_id'] ?? null,
                'safe_id'               => $data['safe_id'] ?? null,
                'amount'                => round((float) $data['amount'], 2),
                'direction'             => $direction,
                'notes'                 => $data['notes'] ?? null,
            ]);

            // TODO: تحديث أرصدة bank_accounts / safes لو عندك أعمدة رصيد.
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
            'transaction_date'     => ['required', 'date'],
            'notes'                => ['nullable', 'string'],
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
                'status' => 'لم يتم إعداد حالة "تحويل بين حسابات" للمكتب. أضِفها في transaction_statuses وربطها بـ category_transaction_status.'
            ])->withInput();
        }

        DB::transaction(function () use ($data, $transferStatusId) {
            // خروج من الحساب المصدر
            LedgerEntry::create([
                'entry_date'            => $data['transaction_date'],
                'investor_id'           => null,
                'is_office'             => true,
                'transaction_status_id' => $transferStatusId,
                'transaction_type_id'   => 3, // تحويل
                'bank_account_id'       => $data['from_type'] === 'bank' ? ($data['from_bank_account_id'] ?? null) : null,
                'safe_id'               => $data['from_type'] === 'safe' ? ($data['from_safe_id'] ?? null) : null,
                'amount'                => round((float) $data['amount'], 2),
                'direction'             => 'out',
                'notes'                 => $data['notes'] ?? 'تحويل داخلي بين حسابات المكتب (خروج)',
            ]);

            // دخول للحساب الوجهة
            LedgerEntry::create([
                'entry_date'            => $data['transaction_date'],
                'investor_id'           => null,
                'is_office'             => true,
                'transaction_status_id' => $transferStatusId,
                'transaction_type_id'   => 3, // تحويل
                'bank_account_id'       => $data['to_type'] === 'bank' ? ($data['to_bank_account_id'] ?? null) : null,
                'safe_id'               => $data['to_type'] === 'safe' ? ($data['to_safe_id'] ?? null) : null,
                'amount'                => round((float) $data['amount'], 2),
                'direction'             => 'in',
                'notes'                 => $data['notes'] ?? 'تحويل داخلي بين حسابات المكتب (دخول)',
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
        'transaction_date' => ['required', 'date'],
        'notes'            => ['nullable', 'string'],
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

    // تحقق إضافي مخصص
    $errors = [];

    if ($data['party_category'] === 'investors' && empty($data['investor_id'])) {
        $errors['investor_id'] = 'اختيار المستثمر مطلوب لفئة المستثمرين';
    }

    // لازم يكون في قيمة في البنك أو الخزنة
    if ($bank <= 0 && $safe <= 0) {
        $errors['bank_share'] = 'أدخل قيمة في البنك أو الخزنة على الأقل';
        $errors['safe_share'] = 'أدخل قيمة في البنك أو الخزنة على الأقل';
    }

    // مجموع التوزيع لازم يساوي الإجمالي
    if (round($bank + $safe, 2) !== $total) {
        $errors['amount'] = 'يجب أن يساوي مجموع (بنك + خزنة) إجمالي المبلغ';
    }

    // لو في جزء بنك/خزنة لازم اختيار الحساب
    if ($bank > 0 && empty($data['bank_account_id'])) {
        $errors['bank_account_id'] = 'اختر الحساب البنكي لهذا الجزء';
    }
    if ($safe > 0 && empty($data['safe_id'])) {
        $errors['safe_id'] = 'اختر الخزنة لهذا الجزء';
    }

    // تأكيد أن الحالة مسموح بها للفئة
    $categoryId = $data['party_category'] === 'investors' ? $this->CAT_INVESTORS : $this->CAT_OFFICE;
    $allowed = DB::table('category_transaction_status')
        ->where('category_id', $categoryId)
        ->where('transaction_status_id', $data['status_id'])
        ->exists();

    if (!$allowed) {
        $errors['status_id'] = 'هذه الحالة غير مرتبطة بالفئة المختارة';
    }

    if (!empty($errors)) {
        return back()->withErrors($errors)->withInput();
    }

    // اتجاه الحركة من نوع الحالة
    $status    = TransactionStatus::findOrFail($data['status_id']);
    $typeId    = (int) $status->transaction_type_id;
    $direction = $typeId === 1 ? 'in' : 'out'; // 1=إيداع/داخل، 2=سحب/خارج

    DB::transaction(function () use ($data, $bank, $safe, $typeId, $direction) {

    // ملاحظات ذكية موحّدة
    $statusName = optional(TransactionStatus::find($data['status_id']))->name ?? 'عملية';
    $partyLabel = $data['party_category'] === 'office'
        ? 'المكتب'
        : ('المستثمر: ' . (optional(Investor::find($data['investor_id']))->name ?? ('#'.$data['investor_id'])));
    $dirLabel   = $direction === 'in' ? 'داخل' : 'خارج';

    $bankName = $data['bank_account_id']
        ? optional(BankAccount::find($data['bank_account_id']))->name
        : null;
    $safeName = $data['safe_id']
        ? optional(Safe::find($data['safe_id']))->name
        : null;

    $baseNote = trim((string)($data['notes'] ?? ''));

    $noteBank = trim(($baseNote ? $baseNote.' — ' : '')
        . "{$partyLabel} | {$statusName} | {$dirLabel} | بنك: {$bankName} | مبلغ: " . number_format($bank, 2));

    $noteSafe = trim(($baseNote ? $baseNote.' — ' : '')
        . "{$partyLabel} | {$statusName} | {$dirLabel} | خزنة: {$safeName} | مبلغ: " . number_format($safe, 2));

    // جزء البنك
    if ($bank > 0) {
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

    /** حالة تحويل بين حسابات للمكتب */
    private function resolveTransferStatusId(): ?int
    {
        return DB::table('transaction_statuses as ts')
            ->join('category_transaction_status as cts', 'cts.transaction_status_id', '=', 'ts.id')
            ->where('ts.transaction_type_id', 3)          // تحويل
            ->where('cts.category_id', $this->CAT_OFFICE) // فئة المكتب
            ->value('ts.id');
    }
}
