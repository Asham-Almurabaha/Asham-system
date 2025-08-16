<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\InstallmentStatus;
use App\Models\InvestorTransaction;
use App\Models\OfficeTransaction;
use App\Models\TransactionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * تعديل بيانات القسط
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
            $remainingPayment = (float) $validated['payment_amount'];
            $paymentDate      = $validated['payment_date'];

            // ✅ حمّل العقد مع المستثمرين عشان نعرف المجموع
            $contract = Contract::with('investors')->findOrFail($validated['contract_id']);
            $sumPct   = (float) $contract->investors->sum(fn($i) => (float) ($i->pivot->share_percentage ?? 0));
            $canApplyStatusesAndDistributions = (round($sumPct, 2) === 100.00);

            $currentInstallment = ContractInstallment::where('contract_id', $contract->id)
                ->whereColumn('payment_amount', '<', 'due_amount')
                ->orderBy('installment_number')
                ->first();

            if (!$currentInstallment) {
                throw new \Exception('🚫 لا يوجد أقساط بحاجة إلى سداد.');
            }

            while ($remainingPayment > 0 && $currentInstallment) {
                $dueAmount    = (float) $currentInstallment->due_amount;
                $alreadyPaid  = (float) $currentInstallment->payment_amount;
                $remainingDue = $dueAmount - $alreadyPaid;

                $paymentForThisInstallment = min($remainingDue, $remainingPayment);

                // تجهيز الملاحظات السابقة بدون مسحها
                $currentNotes = trim($currentInstallment->notes ?? '');
                if ($currentNotes === '') {
                    $currentNotes = "تفاصيل الدفعات:";
                }

                // صيغة المبلغ
                $amountFormatted = rtrim(rtrim(number_format($paymentForThisInstallment, 2, '.', ''), '0'), '.');

                // حالة القسط قبل الدفع (للتوثيق)
                $previousStatus = $currentInstallment->installmentStatus->name ?? 'غير محدد';
                $currentNotes  .= "\n- دفع مبلغ {$amountFormatted} بتاريخ {$paymentDate} (الحالة قبل الدفع: {$previousStatus})";

                // تحديث القسط (المبلغ والتاريخ والملاحظات)
                $currentInstallment->update([
                    'payment_amount' => $alreadyPaid + $paymentForThisInstallment,
                    'payment_date'   => $paymentDate,
                    'notes'          => $currentNotes,
                ]);

                // ✅ تحديث الحالة / وتوزيع المبلغ على المكتب/المستثمرين فقط لو النِّسَب = 100%
                if ($canApplyStatusesAndDistributions) {
                    $this->updateStatus($currentInstallment);

                    $this->logInvestorInstallmentTransactions(
                        $contract->id,
                        $currentInstallment->id,
                        $paymentForThisInstallment,
                        'سداد قسط',
                        $paymentDate
                    );
                }

                $remainingPayment -= $paymentForThisInstallment;

                $currentInstallment = ContractInstallment::where('contract_id', $contract->id)
                    ->where('installment_number', '>', $currentInstallment->installment_number)
                    ->whereColumn('payment_amount', '<', 'due_amount')
                    ->orderBy('installment_number')
                    ->first();
            }
        });

        return response()->json(['success' => true]);
    }

    
    /**
     * تحديث حالة القسط — يشتغل فقط لو نسب المستثمرين = 100%
     */
    public function updateStatus(ContractInstallment $installment)
    {
        // ✅ لا تعمل إلا لو نسب المستثمرين = 100%
        $installment->loadMissing('contract.investors', 'installmentStatus');

        $sumPct = 0.0;
        if ($installment->contract && $installment->contract->investors) {
            $sumPct = (float) $installment->contract->investors
                ->sum(fn($i) => (float) ($i->pivot->share_percentage ?? 0));
        }

        // نقارن على دقتين عشريتين لتفادي مشاكل الكسور
        if (round($sumPct, 2) !== 100.00) {
            // لو النسبة مش 100% ما نغيرش الحالة ونخرج
            return;
        }

        $paid    = (float) ($installment->payment_amount ?? 0);
        $total   = (float) ($installment->due_amount ?? 0);
        $dueDate = Carbon::parse($installment->due_date)->startOfDay();
        $payDate = $installment->payment_date
            ? Carbon::parse($installment->payment_date)->startOfDay()
            : null;
        $today   = now()->startOfDay();

        $currentStatusName = optional($installment->installmentStatus)->name;
        $statusName = null;

        // 1) مدفوع بالكامل
        if ($total > 0 && $paid >= $total) {
            $effectivePayDate = $payDate ?: $today;
            $diffDays = $effectivePayDate->diffInDays($dueDate, false); // سالب = قبل الاستحقاق

            if ($diffDays > 7) {
                $statusName = 'مدفوع مبكر';
            } elseif ($diffDays < -7) {
                $statusName = 'مدفوع متأخر';
            } else {
                $statusName = 'مدفوع كامل';
            }
        }
        // 2) مدفوع جزئي
        elseif ($paid > 0 && $paid < $total) {
            $statusName = 'مدفوع جزئي';
        }
        // 3) غير مدفوع
        else {
            // لو مؤجل/معتذر ومفيش دفع، بلاش نغير
            if (in_array($currentStatusName, ['مؤجل', 'معتذر'], true)) {
                return;
            }

            if ($today->lt($dueDate)) {
                $statusName = 'مطلوب';
            } else {
                $overdueDays = $dueDate->diffInDays($today);
                $statusName  = ($overdueDays > 15) ? 'متعثر' : 'متأخر';
            }
        }

        if ($statusName) {
            $statusId = InstallmentStatus::where('name', $statusName)->value('id');
            if ($statusId) {
                $installment->update(['installment_status_id' => $statusId]);
            }
        }
    }

    private function logInvestorInstallmentTransactions($contractId, $installmentId, $amount, $statusName, $transactionDate)
    {
        $contract = Contract::with('investors')->find($contractId);
        if (!$contract || $contract->investors->isEmpty()) {
            return;
        }

        // حالة المستثمرين من الباراميتر
        $statusId = TransactionStatus::where('name', $statusName)->value('id');
        if (!$statusId) {
            throw new \Exception("🚫 لم يتم العثور على حالة باسم: {$statusName}");
        }

        // حالة المكتب ثابتة "ربح المكتب"
        $officeStatusId = TransactionStatus::where('name', 'ربح المكتب')->value('id');
        if (!$officeStatusId) {
            throw new \Exception("🚫 لم يتم العثور على حالة 'ربح المكتب'");
        }

        $installment = ContractInstallment::find($installmentId);
        $installmentNumber = $installment ? $installment->installment_number : null;

        // 1️⃣ حساب ربح المكتب لكل مستثمر + إجمالي ربح المكتب
        $totalOfficeProfit = 0;
        $investorOfficeProfits = [];

        foreach ($contract->investors as $inv) {
            $sharePercentage = (float) ($inv->pivot->share_percentage ?? 0);
            $investorTotalProfit = ($sharePercentage > 0 && $contract->investor_profit > 0)
                ? ($contract->investor_profit * ($sharePercentage / 100))
                : 0;

            $officeSharePercentage = (float) ($inv->office_share_percentage ?? 0);
            $officeProfit = ($officeSharePercentage > 0)
                ? ($investorTotalProfit * ($officeSharePercentage / 100))
                : 0;

            $investorOfficeProfits[$inv->id] = [
                'office_profit' => $officeProfit,
                'total_profit'  => $investorTotalProfit
            ];

            $totalOfficeProfit += $officeProfit;
        }

        // 2️⃣ جلب المبالغ المحصلة مسبقاً للمكتب
        $collectedOfficeByInvestor = OfficeTransaction::where('contract_id', $contract->id)
            ->selectRaw('investor_id, SUM(amount) as total')
            ->groupBy('investor_id')
            ->pluck('total', 'investor_id')
            ->toArray();

        $collectedOfficeProfit = array_sum($collectedOfficeByInvestor);
        $remainingOfficeProfit = max(0, $totalOfficeProfit - $collectedOfficeProfit);

        // 3️⃣ لو لسه ربح المكتب ما اكتمل
        if ($remainingOfficeProfit > 0) {
            if ($amount <= $remainingOfficeProfit) {
                foreach ($contract->investors as $inv) {
                    $officeProfit = $investorOfficeProfits[$inv->id]['office_profit'];
                    if ($officeProfit <= 0) continue;

                    $alreadyCollected = (float) ($collectedOfficeByInvestor[$inv->id] ?? 0);
                    $remainingForThisInvestor = max(0, $officeProfit - $alreadyCollected);

                    if ($remainingForThisInvestor > 0) {
                        $investorShare = ($remainingOfficeProfit > 0)
                            ? ($remainingForThisInvestor / $remainingOfficeProfit)
                            : 0;

                        $amountForThisInvestorOffice = $amount * $investorShare;

                        OfficeTransaction::create([
                            'investor_id'      => $inv->id,
                            'contract_id'      => $contract->id,
                            'installment_id'   => $installmentId,
                            'status_id'        => $officeStatusId, // ربح المكتب
                            'amount'           => round($amountForThisInvestorOffice, 2),
                            'transaction_date' => $transactionDate,
                            'notes'            => "تحصيل ربح المكتب من {$inv->name} - قسط رقم {$installmentNumber} للعقد رقم {$contract->contract_number}"
                        ]);
                    }
                }
                return;
            }

            foreach ($contract->investors as $inv) {
                $officeProfit = $investorOfficeProfits[$inv->id]['office_profit'];
                if ($officeProfit <= 0) continue;

                $alreadyCollected = (float) ($collectedOfficeByInvestor[$inv->id] ?? 0);
                $remainingForThisInvestor = max(0, $officeProfit - $alreadyCollected);

                if ($remainingForThisInvestor > 0) {
                    OfficeTransaction::create([
                        'investor_id'      => $inv->id,
                        'contract_id'      => $contract->id,
                        'installment_id'   => $installmentId,
                        'status_id'        => $officeStatusId, // ربح المكتب
                        'amount'           => round($remainingForThisInvestor, 2),
                        'transaction_date' => $transactionDate,
                        'notes'            => "تحصيل باقي ربح المكتب من {$inv->name} - قسط رقم {$installmentNumber} للعقد رقم {$contract->contract_number}"
                    ]);
                }
            }

            $amount -= $remainingOfficeProfit;
        }

        // 4️⃣ توزيع الباقي على المستثمرين
        foreach ($contract->investors as $inv) {
            $sharePercentage = (float) ($inv->pivot->share_percentage ?? 0);
            $investorProfitFromThisPayment = ($sharePercentage > 0)
                ? ($amount * ($sharePercentage / 100))
                : 0;

            if ($investorProfitFromThisPayment > 0) {
                InvestorTransaction::create([
                    'investor_id'      => $inv->id,
                    'contract_id'      => $contract->id,
                    'installment_id'   => $installmentId,
                    'status_id'        => $statusId, // الحالة من الباراميتر
                    'amount'           => round($investorProfitFromThisPayment, 2),
                    'transaction_date' => $transactionDate,
                    'notes'            => "سداد قسط رقم {$installmentNumber} بعد سداد كامل ربح المكتب - العقد رقم {$contract->contract_number}"
                ]);
            }
        }
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
            $newTotalPaid = max(0, (float)$installment->payment_amount - (float)$amount);

            $installment->update([
                'payment_amount' => $newTotalPaid,
                'payment_date'   => $newTotalPaid > 0 ? $installment->payment_date : null,
            ]);

            // updateStatus نفسها فيها شرط 100% وبتخرج لو مش مكتمّلة
            $this->updateStatus($installment);
        });

        return redirect()->back()->with('success', '🗑 تم تعديل مبلغ السداد بنجاح.');
    }

}
