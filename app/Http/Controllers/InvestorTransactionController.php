<?php

namespace App\Http\Controllers;

use App\Models\InvestorTransaction;
use App\Models\Investor;
use App\Models\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestorTransactionController extends Controller
{
    /**
     * ترجّع ID فئة المستثمرين من جدول categories
     * مع بدائل اسمية + fallback لو مفيش.
     */
    private function getInvestorCategoryId(): int
    {
        $id = (int) DB::table('categories')
            ->whereIn('name', ['مستثمرين'])
            ->value('id');

        // عدّل 4 لو ID مختلف عندك
        return $id ?: 4;
    }

    /**
     * الحالات المرتبطة بفئة المستثمرين فقط.
     */
    private function getInvestorStatuses(int $categoryId)
    {
        return TransactionStatus::whereIn('id', function ($q) use ($categoryId) {
                $q->from('category_transaction_status')
                  ->select('transaction_status_id')
                  ->where('category_id', $categoryId);
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * قائمة العمليات (محصورة على حالات فئة المستثمرين).
     * يدعم فلاتر: investor, status, from, to
     */
    public function index(Request $request)
    {
        $investorCategoryId = $this->getInvestorCategoryId();

        // الحالات المسموح بها لهذه الفئة
        $statuses = $this->getInvestorStatuses($investorCategoryId);
        $allowedStatusIds = $statuses->pluck('id')->all();

        // لو مفيش حالات مسموح بها، رجّع جدول فاضي مع الدروب داون
        $transactionsQuery = InvestorTransaction::with(['investor', 'status'])
            ->when(!empty($allowedStatusIds), fn ($q) => $q->whereIn('status_id', $allowedStatusIds))
            ->when(empty($allowedStatusIds), fn ($q) => $q->whereRaw('1=0')) // يرجّع صفر صفوف
            ->when($request->filled('investor'), fn ($q) => $q->where('investor_id', $request->investor))
            ->when($request->filled('status') && in_array((int)$request->status, $allowedStatusIds, true),
                fn ($q) => $q->where('status_id', (int)$request->status)
            )
            ->when($request->filled('from'), fn ($q) => $q->whereDate('transaction_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('transaction_date', '<=', $request->to))
            ->latest();

        $transactions = $transactionsQuery->paginate(15)->appends($request->query());

        $investors = Investor::orderBy('name')->get();

        return view('investor_transactions.index', compact(
            'transactions',
            'investors',
            'statuses',
            'investorCategoryId'
        ));
    }

    /**
     * نموذج إضافة عملية جديدة.
     */
    public function create()
    {
        $investorCategoryId = $this->getInvestorCategoryId();
        $statuses  = $this->getInvestorStatuses($investorCategoryId);
        $investors = Investor::orderBy('name')->get();

        return view('investor_transactions.create', compact('investors', 'statuses', 'investorCategoryId'));
    }

    /**
     * حفظ العملية مع التحقق من أن الحالة ضمن المسموح لفئة المستثمرين.
     */
    public function store(Request $request)
    {
        $investorCategoryId = $this->getInvestorCategoryId();

        $allowedStatusIds = DB::table('category_transaction_status')
            ->where('category_id', $investorCategoryId)
            ->pluck('transaction_status_id')
            ->toArray();

        $request->validate([
            'investor_id'      => ['required', 'exists:investors,id'],
            'status_id'        => ['required', 'integer', 'in:'.implode(',', $allowedStatusIds)],
            'amount'           => ['required', 'numeric'],
            'transaction_date' => ['required', 'date'],
            'notes'            => ['nullable', 'string'],
        ], [
            'status_id.in' => 'الحالة المختارة غير مسموح بها لفئة المستثمرين.',
        ]);

        InvestorTransaction::create([
            'investor_id'      => (int) $request->investor_id,
            'status_id'        => (int) $request->status_id,
            'amount'           => (float) $request->amount,
            'transaction_date' => $request->transaction_date,
            'notes'            => $request->notes,
        ]);

        return redirect()
            ->route('investor-transactions.index')
            ->with('success', 'تمت إضافة العملية بنجاح');
    }
}
