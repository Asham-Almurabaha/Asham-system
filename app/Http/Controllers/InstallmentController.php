<?php

namespace App\Http\Controllers;

use App\Models\ContractInstallment;
use App\Models\InstallmentPayment;
use App\Models\InstallmentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InstallmentController extends Controller
{
    /**
     * عرض تفاصيل قسط معين
     */
    public function show($id)
    {
        $installment = ContractInstallment::with(['contract', 'status', 'payments'])->findOrFail($id);
        return view('installments.show', compact('installment'));
    }

    /**
     * تسجيل سداد قسط (دون تعديل amount أو due_date)
     */
    public function payInstallment(Request $request, $installmentId)
    {
        $installment = ContractInstallment::findOrFail($installmentId);

        $validated = $request->validate([
            'paid_amount'  => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes'        => 'nullable|string',
        ]);

        DB::transaction(function () use ($installment, $validated) {
            // حفظ دفعة جديدة
            $payment = new InstallmentPayment([
                'amount'       => $installment->amount, // نفس المبلغ الأصلي
                'due_date'     => $installment->due_date, // نفس تاريخ الاستحقاق الأصلي
                'paid_amount'  => $validated['paid_amount'],
                'payment_date' => $validated['payment_date'],
                'notes'        => $validated['notes'] ?? null,
            ]);
            $installment->payments()->save($payment);

            // تحديث حالة القسط
            $this->updateInstallmentStatus($installment);
        });

        return redirect()->back()->with('success', '✅ تم تسجيل السداد وتحديث حالة القسط بنجاح.');
    }

    /**
     * حذف دفعة وإعادة تحديث حالة القسط
     */
    public function deletePayment($paymentId)
    {
        $payment = InstallmentPayment::findOrFail($paymentId);
        $installment = $payment->installment;

        DB::transaction(function () use ($payment, $installment) {
            $payment->delete();
            $this->updateInstallmentStatus($installment);
        });

        return redirect()->back()->with('success', '🗑 تم حذف الدفعة وتحديث حالة القسط.');
    }

    /**
     * تحديث حالة القسط بناءً على المدفوعات
     */
    private function updateInstallmentStatus(ContractInstallment $installment)
    {
        $paidAmount = $installment->payments()->sum('paid_amount');
        $dueAmount  = $installment->amount;
        $dueDate    = Carbon::parse($installment->due_date);
        $today      = Carbon::today();

        $statusIds = InstallmentStatus::pluck('id', 'name');

        if ($paidAmount >= $dueAmount) {
            if ($dueDate->isFuture()) {
                $status = 'مدفوع مبكر';
            } elseif ($dueDate->isToday()) {
                $status = 'مدفوع كامل';
            } else {
                $status = 'مدفوع متأخر';
            }
            $installment->update([
                'installment_status_id' => $statusIds[$status] ?? null,
                'payment_date' => $today,
                'paid_amount' => $paidAmount
            ]);
        }
        elseif ($paidAmount > 0 && $paidAmount < $dueAmount) {
            $installment->update([
                'installment_status_id' => $statusIds['مدفوع جزئي'] ?? null,
                'payment_date' => null,
                'paid_amount' => $paidAmount
            ]);
        }
        else {
            if ($dueDate->isPast()) {
                $installment->update([
                    'installment_status_id' => $statusIds['مؤجل'] ?? null,
                    'paid_amount' => $paidAmount
                ]);
            } else {
                $installment->update([
                    'installment_status_id' => $statusIds['معلق'] ?? null,
                    'paid_amount' => $paidAmount
                ]);
            }
        }
    }
}
