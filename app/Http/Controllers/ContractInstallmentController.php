<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\InstallmentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ContractInstallmentController extends Controller
{
    /**
     * عرض كل الأقساط لعقد معين
     */
    public function index($contractId)
    {
        $contract = Contract::with(['installments.installmentStatus'])->findOrFail($contractId);
        return view('installments.index', compact('contract'));
    }

    /**
     * حفظ قسط جديد
     */
    public function store(Request $request, $contractId)
    {
        $validated = $request->validate([
            'installment_number' => 'required|integer|min:1',
            'due_date'           => 'required|date',
            'due_amount'         => 'required|numeric|min:0.01',
        ]);

        $contract = Contract::findOrFail($contractId);

        ContractInstallment::create([
            'contract_id'           => $contract->id,
            'installment_number'    => $validated['installment_number'],
            'due_date'              => $validated['due_date'],
            'due_amount'            => $validated['due_amount'],
            'payment_amount'        => 0,
            'installment_status_id' => InstallmentStatus::where('name', 'معلق')->value('id'), // الحالة الافتراضية
        ]);

        return redirect()->back()->with('success', '✅ تم إضافة القسط بنجاح.');
    }

    /**
     * تعديل بيانات القسط (لا تغير المبلغ المستحق أو تاريخ الاستحقاق بعد تسجيل العقد)
     */
    public function update(Request $request, $installmentId)
    {
        $installment = ContractInstallment::findOrFail($installmentId);

        $validated = $request->validate([
            'due_date'    => 'required|date',
            'due_amount'  => 'required|numeric|min:0.01',
        ]);

        $installment->update($validated);

        return redirect()->back()->with('success', '✏️ تم تعديل بيانات القسط بنجاح.');
    }

    /**
     * تسجيل سداد قسط
     */
   public function payInstallment(Request $request)
{
    $validated = $request->validate([
        'contract_id'    => 'required|exists:contracts,id',
        'payment_amount' => 'required|numeric|min:0.01',
        'payment_date'   => 'required|date',
    ]);

    DB::transaction(function () use ($validated) {
        $remainingPayment = $validated['payment_amount'];
        $paymentDate      = $validated['payment_date'];

        $currentInstallment = ContractInstallment::where('contract_id', $validated['contract_id'])
            ->whereColumn('payment_amount', '<', 'due_amount')
            ->orderBy('installment_number')
            ->first();

        if (!$currentInstallment) {
            throw new \Exception('🚫 لا يوجد أقساط بحاجة إلى سداد.');
        }

        while ($remainingPayment > 0 && $currentInstallment) {
            $dueAmount    = $currentInstallment->due_amount;
            $alreadyPaid  = $currentInstallment->payment_amount;
            $remainingDue = $dueAmount - $alreadyPaid;

            $paymentForThisInstallment = min($remainingDue, $remainingPayment);

            // تجهيز الملاحظات السابقة بدون مسحها
            $currentNotes = trim($currentInstallment->notes ?? '');
            if (empty($currentNotes)) {
                $currentNotes = "تفاصيل الدفعات:";
            }

            // صيغة المبلغ
            $amountFormatted = rtrim(rtrim(number_format($paymentForThisInstallment, 2, '.', ''), '0'), '.');

            // إضافة تفاصيل الدفع مع حالة القسط قبل الدفع
            $previousStatus = $currentInstallment->installmentStatus->name ?? 'غير محدد';
            $currentNotes .= "\n- دفع مبلغ {$amountFormatted} بتاريخ {$paymentDate} (الحالة قبل الدفع: {$previousStatus})";

            // تحديث القسط
            $currentInstallment->update([
                'payment_amount' => $alreadyPaid + $paymentForThisInstallment,
                'payment_date'   => $paymentDate,
                'notes'          => $currentNotes,
            ]);

            // تحديث الحالة
            $this->updateStatus($currentInstallment);

            $remainingPayment -= $paymentForThisInstallment;

            $currentInstallment = ContractInstallment::where('contract_id', $validated['contract_id'])
                ->where('installment_number', '>', $currentInstallment->installment_number)
                ->whereColumn('payment_amount', '<', 'due_amount')
                ->orderBy('installment_number')
                ->first();
        }
    });

    return response()->json(['success' => true]);
}


 
    public function deferAjax($id)
{
    $installment = ContractInstallment::findOrFail($id);
    $statusId = InstallmentStatus::where('name', 'مؤجل')->value('id');

    // الاحتفاظ بالملاحظات السابقة
    $currentNotes = trim($installment->notes ?? '');
    if (!empty($currentNotes)) {
        $currentNotes .= "\n";
    } else {
        $currentNotes = "تفاصيل الدفعات:\n";
    }
    $currentNotes .= "- تم تأجيل القسط بتاريخ " . now()->format('Y-m-d');

    $installment->installment_status_id = $statusId;
    $installment->notes = $currentNotes;
    $installment->save();

    return response()->json([
        'success' => true,
        'status_name' => 'مؤجل',
        'badge_class' => 'warning',
        'notes' => $currentNotes
    ]);
}

public function excuseAjax($id)
{
    $installment = ContractInstallment::findOrFail($id);
    $statusId = InstallmentStatus::where('name', 'معتذر')->value('id');

    // الاحتفاظ بالملاحظات السابقة
    $currentNotes = trim($installment->notes ?? '');
    if (!empty($currentNotes)) {
        $currentNotes .= "\n";
    } else {
        $currentNotes = "تفاصيل الدفعات:\n";
    }
    $currentNotes .= "- أنا معتذر بتاريخ " . now()->format('Y-m-d');

    $installment->installment_status_id = $statusId;
    $installment->notes = $currentNotes;
    $installment->save();

    return response()->json([
        'success' => true,
        'status_name' => 'معتذر',
        'badge_class' => 'secondary',
        'notes' => $currentNotes
    ]);
}




    /**
     * حذف مبلغ سداد من القسط
     */
    public function removePayment($installmentId, $amount)
    {
        $installment = ContractInstallment::findOrFail($installmentId);

        DB::transaction(function () use ($installment, $amount) {
            $newTotalPaid = max(0, $installment->payment_amount - $amount);

            $installment->update([
                'payment_amount' => $newTotalPaid,
                'payment_date'   => $newTotalPaid > 0 ? $installment->payment_date : null,
            ]);

            $this->updateStatus($installment);
        });

        return redirect()->back()->with('success', '🗑 تم تعديل مبلغ السداد بنجاح.');
    }

    /**
     * تحديث حالة القسط
     */
   public function updateStatus(ContractInstallment $installment)
    {
        $paid     = $installment->payment_amount ?? 0;
        $total    = $installment->due_amount ?? 0;
        $dueDate  = Carbon::parse($installment->due_date);
        $payDate  = $installment->payment_date ? Carbon::parse($installment->payment_date) : null;

        $statusName = null;

        // 1️⃣ مدفوع بالكامل
        if ($paid >= $total && $total > 0 && $payDate) {
            $diffDays = $payDate->diffInDays($dueDate, false); // الفرق بالأيام (سالب لو بعد الاستحقاق)

            if ($diffDays >= -5 && $diffDays <= 5) {
                // خلال 5 أيام قبل أو بعد
                $statusName = 'مدفوع كامل';
            } elseif ($diffDays > 5) {
                // قبل الاستحقاق بأكثر من 5 أيام
                $statusName = 'مدفوع مبكر';
            } elseif ($diffDays < -5) {
                // بعد الاستحقاق بأكثر من 5 أيام
                $statusName = 'مدفوع متأخر';
            }
        }
        // 2️⃣ مدفوع جزئي
        elseif ($paid > 0 && $paid < $total) {
            $statusName = 'مدفوع جزئي';
        }

        // تحديث الحالة في الجدول
        if ($statusName) {
            $statusId = InstallmentStatus::where('name', $statusName)->value('id');
            $installment->update(['installment_status_id' => $statusId]);
        }
    }

}
