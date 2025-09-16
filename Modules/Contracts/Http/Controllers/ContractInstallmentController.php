<?php

namespace Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Lookups\Entities\InstallmentStatus;
use App\Models\LedgerEntry;
use App\Models\OfficeTransaction;
use Modules\Lookups\Entities\TransactionStatus;
use Modules\Lookups\Entities\TransactionType;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractInstallment;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Contracts\Services\InstallmentStatusService;
use Modules\Contracts\Services\InvestorTransactionLogger;

class ContractInstallmentController extends Controller
{
    public function __construct(private InvestorTransactionLogger $investorTransactionLogger)
    {
    }

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
            'installment_status_id' => InstallmentStatus::where('name', 'لم يحل')->value('id'), // الحالة الافتراضية
        ]);

        return redirect()->back()->with('success', '✅ تم إضافة القسط بنجاح.');
    }
    
    /**
     * تسجيل سداد قسط
     */
    public function payInstallment(Request $request)
    {
        $validated = $request->validate([
            'contract_id'      => 'required|exists:contracts,id',
            'payment_amount'   => 'required|numeric|min:0.01',
            'payment_date'     => 'required|date',
            // ⬇️ مصادر التحصيل (اختياري)
            'bank_account_id'  => 'nullable|integer|exists:bank_accounts,id',
            'safe_id'          => 'nullable|integer|exists:safes,id',
        ]);

        $bankId = $request->input('bank_account_id');
        $safeId = $request->input('safe_id');

        $this->validatePaymentSource($bankId, $safeId);

        DB::transaction(function () use ($validated, $bankId, $safeId) {
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
                        $paymentDate,
                        $bankId,   // ⬅️ يمرر الحساب
                        $safeId
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
     * تسجيل السداد المبكر
     */
    public function earlySettle(Request $request, Contract $contract)
    {
        $data = $request->validate([
            'discount_amount' => ['required', 'numeric', 'min:0'],
            // ⬇️ مصادر التحصيل (اختياري)
            'bank_account_id' => 'nullable|integer|exists:bank_accounts,id',
            'safe_id'         => 'nullable|integer|exists:safes,id',
        ]);

        $bankId = $request->input('bank_account_id');
        $safeId = $request->input('safe_id');

        $this->validatePaymentSource($bankId, $safeId);

        try {
            DB::transaction(function () use ($contract, $data, $bankId, $safeId) {
                // أقساط العقد (قفل للتناسق)
                $installments = ContractInstallment::where('contract_id', $contract->id)
                    ->orderBy('installment_number')
                    ->lockForUpdate()
                    ->get();

                // إجمالي المتبقي نقدًا على الأقساط
                $totalOutstanding = round($installments->sum(function ($i) {
                    return max(0, round((float)$i->due_amount - (float)$i->payment_amount, 2));
                }), 2);

                // لو مفيش متبقي مفيش حاجة تتعمل
                if ($totalOutstanding <= 0) {
                    // حدّث حالة العقد فقط لو حابب (اختياري)
                    $earlyContractStatusId = ContractStatus::whereIn('name', ['سداد مبكر','مدفوع مبكر'])
                        ->orderByRaw("FIELD(name,'سداد مبكر','مدفوع مبكر')")
                        ->value('id');

                    $contract->discount_amount = 0;
                    if ($earlyContractStatusId) {
                        $contract->contract_status_id = $earlyContractStatusId;
                    }
                    $contract->save();

                    return;
                }

                // طبّق الخصم بحد أقصى المتبقي
                $discount = min(round((float)$data['discount_amount'], 2), $totalOutstanding);

                // المبلغ النقدي الفعلي اللي هيتسدد
                $toPay = round($totalOutstanding - $discount, 2);

                // حالة "مدفوع" للأقساط (بنفضّل "مدفوع كامل" إن وُجد)
                $paidStatusId = InstallmentStatus::whereIn('name', ['مدفوع كامل','مدفوع مبكر','مدفوع','مسدد'])
                    ->orderByRaw("FIELD(name,'مدفوع كامل','مدفوع مبكر','مدفوع','مسدد')")
                    ->value('id');

                $paymentDate = now()->toDateString();

                // وزّع السداد النقدي على الأقساط
                if ($toPay > 0) {
                    foreach ($installments as $inst) {
                        if ($toPay <= 0) break;

                        $remain = max(0, round((float)$inst->due_amount - (float)$inst->payment_amount, 2));
                        if ($remain <= 0) continue;

                        $pay = min($toPay, $remain);

                        // حدّث القسط
                        $inst->payment_amount = round((float)$inst->payment_amount + $pay, 2);
                        if ($pay > 0) {
                            $inst->payment_date = $paymentDate;
                        }
                        if ($paidStatusId && round($inst->payment_amount, 2) >= round($inst->due_amount, 2)) {
                            $inst->installment_status_id = $paidStatusId;
                        }
                        $inst->save();

                        // سجّل توزيع الدفعة حسب السيناريو + تمرير الحساب
                        if ($pay > 0) {
                            $this->logInvestorInstallmentTransactions(
                                $contract->id,
                                $inst->id,
                                $pay,
                                'سداد قسط',
                                $paymentDate,
                                $bankId,   // ⬅️ حساب البنك إن وُجد
                                $safeId    // ⬅️ أو الخزنة
                            );
                        }

                        $toPay = round($toPay - $pay, 2);
                    }
                }

                // علّم كل الأقساط كـ "مدفوعة" لأن الخصم بيكمل تسوية المتبقي
                if ($paidStatusId) {
                    ContractInstallment::where('contract_id', $contract->id)
                        ->update(['installment_status_id' => $paidStatusId]);
                }

                // حدّث العقد: خصم + حالة سداد مبكر
                $earlyContractStatusId = ContractStatus::whereIn('name', ['سداد مبكر','مدفوع مبكر'])
                    ->orderByRaw("FIELD(name,'سداد مبكر','مدفوع مبكر')")
                    ->value('id');

                $contract->discount_amount = $discount; // الـ booted على الموديل هيعيد حساب total_value لو شغّال
                if ($earlyContractStatusId) {
                    $contract->contract_status_id = $earlyContractStatusId;
                }
                $contract->save();
            });

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'تعذّر إتمام السداد المبكر: '.$e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * تحديث حالة القسط — يشتغل فقط لو نسب المستثمرين = 100%
     */
    public function updateStatus(ContractInstallment $installment)
    {
        InstallmentStatusService::recalculate($installment);
    }

    private function validatePaymentSource(?int $bankId, ?int $safeId): void
    {
        if (empty($bankId) || empty($safeId)) {
            return;
        }

        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? null;

        $messages = [
            'payInstallment'                     => 'لا يمكن اختيار بنك وخزنة معًا لنفس السداد.',
            'earlySettle'                        => 'لا يمكن اختيار بنك وخزنة معًا للسداد المبكر.',
            'logInvestorInstallmentTransactions' => 'لا يمكن اختيار بنك وخزنة معًا في نفس العملية.',
        ];

        $message = $messages[$caller] ?? 'لا يمكن اختيار بنك وخزنة معًا.';

        if ($caller === 'logInvestorInstallmentTransactions') {
            throw new \InvalidArgumentException($message);
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $message,
        ], 422));
    }

    private function logInvestorInstallmentTransactions(
        $contractId,
        $installmentId,
        $amount,
        $statusName,        // مثال: "سداد قسط"
        $transactionDate,
        ?int $bankAccountId = null,   // ✅ حساب بنكي (اختياري)
        ?int $safeId        = null    // ✅ خزنة (اختياري)
    ){
        $amount = round((float) $amount, 2);
        if ($amount <= 0) return;

        $this->validatePaymentSource($bankAccountId, $safeId);

        $accountCols = [
            'bank_account_id' => $bankAccountId ?: null,
            'safe_id'         => $safeId ?: null,
        ];

        $accountNote = '';
        if ($bankAccountId) {
            $bankName   = optional(\App\Models\BankAccount::find($bankAccountId))->name;
            $accountNote = $bankName ? " | بنك: {$bankName}" : " | بنك";
        } elseif ($safeId) {
            $safeName   = optional(\App\Models\Safe::find($safeId))->name;
            $accountNote = $safeName ? " | خزنة: {$safeName}" : " | خزنة";
        }

        $contract = Contract::with('investors')->find($contractId);
        if (!$contract || $contract->investors->isEmpty()) return;

        $officeStatusId = TransactionStatus::where('name', 'ربح المكتب')->value('id');
        if (!$officeStatusId) throw new \Exception("🚫 لم يتم العثور على حالة 'ربح المكتب'");

        $installment       = ContractInstallment::find($installmentId);
        $installmentNumber = $installment ? $installment->installment_number : null;
        $now               = $transactionDate
            ? ($transactionDate instanceof Carbon ? $transactionDate->copy() : Carbon::parse($transactionDate))
            : Carbon::now();
        $entryDate         = $now->toDateString();

        $resolveTypeId = function (string $main, array $alts = []) {
            $id = TransactionType::where('name', $main)->value('id');
            if ($id) return (int) $id;
            foreach ($alts as $alt) {
                $id = TransactionType::where('name', $alt)->value('id');
                if ($id) return (int) $id;
            }
            return (int) TransactionType::query()->orderBy('id')->value('id');
        };

        $officeTypeId = $resolveTypeId('ربح المكتب', ['أرباح','تحصيل','وارد']);

        $investorMeta = [];
        $totalOfficeProfit = 0.0;

        foreach ($contract->investors as $inv) {
            $sharePct = (float) ($inv->pivot->share_percentage ?? 0);
            if ($sharePct <= 0) continue;

            $investorTotalProfit   = max(0, (float) $contract->investor_profit * ($sharePct / 100));
            $officeSharePercentage = (float) ($inv->office_share_percentage ?? 0);

            $officeProfit = $officeSharePercentage > 0
                ? round($investorTotalProfit * ($officeSharePercentage / 100), 2)
                : 0.0;

            $investorMeta[$inv->id] = [
                'office_profit' => $officeProfit,
                'share_pct'     => $sharePct,
                'name'          => $inv->name,
            ];
            $totalOfficeProfit = round($totalOfficeProfit + $officeProfit, 2);
        }
        if (empty($investorMeta)) return;

        $collectedOfficeByInvestor = OfficeTransaction::where('contract_id', $contract->id)
            ->where('status_id', $officeStatusId)
            ->selectRaw('investor_id, COALESCE(SUM(amount),0) as total')
            ->groupBy('investor_id')
            ->pluck('total', 'investor_id')
            ->toArray();

        $collectedOfficeProfit = round(array_sum($collectedOfficeByInvestor), 2);
        $remainingOfficeProfit = max(0, round($totalOfficeProfit - $collectedOfficeProfit, 2));

        $usePerInvestorDeduct = ($collectedOfficeProfit > 0) && ($remainingOfficeProfit > 0);

        if ($usePerInvestorDeduct) {
            $weights = [];
            $sumW = 0.0;
            foreach ($investorMeta as $id => $m) {
                $w = (float) $m['share_pct'];
                if ($w > 0) { $weights[$id] = $w; $sumW += $w; }
            }
            if ($sumW <= 0) return;

            $allocatedSum = 0.0;
            $ids  = array_keys($weights);
            $last = end($ids);

            $investorEntries = [];

            foreach ($weights as $invId => $w) {
                $alloc = ($invId === $last)
                    ? round($amount - $allocatedSum, 2)
                    : round($amount * $w / $sumW, 2);

                if ($allocatedSum + $alloc > $amount) {
                    $alloc = round($amount - $allocatedSum, 2);
                }
                $allocatedSum = round($allocatedSum + $alloc, 2);
                if ($alloc <= 0) continue;

                $alreadyCollected = (float) ($collectedOfficeByInvestor[$invId] ?? 0);
                $invOfficeTarget  = (float) ($investorMeta[$invId]['office_profit'] ?? 0);
                $officeRemForInv  = max(0, round($invOfficeTarget - $alreadyCollected, 2));

                $officeTake   = min($alloc, $officeRemForInv);
                $investorTake = round($alloc - $officeTake, 2);

                if ($officeTake > 0) {
                    $officeTrx = OfficeTransaction::create([
                        'investor_id'      => $invId,
                        'contract_id'      => $contract->id,
                        'installment_id'   => $installmentId,
                        'status_id'        => $officeStatusId,
                        'amount'           => $officeTake,
                        'transaction_date' => $now,
                        'notes'            => "تحصيل ربح المكتب من {$investorMeta[$invId]['name']}"
                            . ($installmentNumber ? " - قسط رقم {$installmentNumber}" : '')
                            . " - العقد رقم {$contract->contract_number}",
                    ]);

                    if ($officeTypeId) {
                        LedgerEntry::create(array_merge([
                            'entry_date'            => $entryDate,
                            'investor_id'           => null,
                            'is_office'             => true,
                            'transaction_status_id' => $officeStatusId,
                            'transaction_type_id'   => $officeTypeId,
                            'contract_id'           => $contract->id,
                            'installment_id'        => $installmentId,
                            'amount'                => $officeTake,
                            'ref'                   => 'OT-'.$officeTrx->id,
                            'notes'                 => "قيد ربح المكتب — عقد #{$contract->contract_number}"
                                . ($installmentNumber ? " — قسط #{$installmentNumber}" : '')
                                . $accountNote,
                        ], $accountCols));
                    }
                }

                if ($investorTake > 0) {
                    $invName = $investorMeta[$invId]['name'] ?? ('#'.$invId);
                    $investorEntries[] = [
                        'investor_id'       => $invId,
                        'amount'            => $investorTake,
                        'transaction_notes' => ($installmentNumber
                                ? "سداد قسط رقم {$installmentNumber} بعد خصم المتبقّي من ربح المكتب"
                                : "سداد بعد خصم المتبقّي من ربح المكتب")
                            . " - العقد رقم {$contract->contract_number}",
                        'ledger_notes'      => "قيد سداد قسط للمستثمر {$invName} — عقد #{$contract->contract_number}"
                            . ($installmentNumber ? " — قسط #{$installmentNumber}" : '')
                            . $accountNote,
                    ];
                }
            }

            if (!empty($investorEntries)) {
                $this->investorTransactionLogger->log($contract, $investorEntries, $statusName, [
                    'transaction_date'    => $now,
                    'installment_id'      => $installmentId,
                    'bank_account_id'     => $bankAccountId,
                    'safe_id'             => $safeId,
                    'allow_type_fallback' => true,
                    'fallback_direction'  => 'in',
                ]);
            }
            return;
        }

        if ($remainingOfficeProfit > 0) {
            $payOffice = min($amount, $remainingOfficeProfit);
            if ($payOffice > 0) {
                $weights = [];
                $sumW    = 0.0;

                foreach ($investorMeta as $invId => $m) {
                    $already = (float) ($collectedOfficeByInvestor[$invId] ?? 0);
                    $rem     = max(0, round(($m['office_profit'] ?? 0) - $already, 2));
                    if ($rem > 0) { $weights[$invId] = $rem; $sumW += $rem; }
                }

                if ($sumW > 0) {
                    $allocatedSum = 0.0;
                    $ids  = array_keys($weights);
                    $last = end($ids);

                    foreach ($weights as $invId => $w) {
                        $alloc = ($invId === $last)
                            ? round($payOffice - $allocatedSum, 2)
                            : round($payOffice * $w / $sumW, 2);

                        if ($allocatedSum + $alloc > $payOffice) {
                            $alloc = round($payOffice - $allocatedSum, 2);
                        }

                        if ($alloc > 0) {
                            $officeTrx = OfficeTransaction::create([
                                'investor_id'      => $invId,
                                'contract_id'      => $contract->id,
                                'installment_id'   => $installmentId,
                                'status_id'        => $officeStatusId,
                                'amount'           => $alloc,
                                'transaction_date' => $now,
                                'notes'            => "تحصيل ربح المكتب من {$investorMeta[$invId]['name']}"
                                    . ($installmentNumber ? " - قسط رقم {$installmentNumber}" : '')
                                    . " - العقد رقم {$contract->contract_number}",
                            ]);

                            if ($officeTypeId) {
                                LedgerEntry::create(array_merge([
                                    'entry_date'            => $entryDate,
                                    'investor_id'           => null,
                                    'is_office'             => true,
                                    'transaction_status_id' => $officeStatusId,
                                    'transaction_type_id'   => $officeTypeId,
                                    'contract_id'           => $contract->id,
                                    'installment_id'        => $installmentId,
                                    'amount'                => $alloc,
                                    'ref'                   => 'OT-'.$officeTrx->id,
                                    'notes'                 => "قيد ربح المكتب — عقد #{$contract->contract_number}"
                                        . ($installmentNumber ? " — قسط #{$installmentNumber}" : '')
                                        . $accountNote,
                                ], $accountCols));
                            }

                            $allocatedSum = round($allocatedSum + $alloc, 2);
                        }
                    }
                }

                $amount = round($amount - $payOffice, 2);
                if ($amount <= 0) return;
            }
        }

        $weights = [];
        $sumW = 0.0;
        foreach ($investorMeta as $invId => $m) {
            $w = (float) $m['share_pct'];
            if ($w > 0) { $weights[$invId] = $w; $sumW += $w; }
        }
        if ($sumW <= 0) return;

        $allocatedSum = 0.0;
        $ids  = array_keys($weights);
        $last = end($ids);

        $investorEntries = [];

        foreach ($weights as $invId => $w) {
            $alloc = ($invId === $last)
                ? round($amount - $allocatedSum, 2)
                : round($amount * $w / $sumW, 2);

            if ($allocatedSum + $alloc > $amount) {
                $alloc = round($amount - $allocatedSum, 2);
            }

            if ($alloc > 0) {
                $invName = $investorMeta[$invId]['name'] ?? ('#'.$invId);
                $investorEntries[] = [
                    'investor_id'       => $invId,
                    'amount'            => $alloc,
                    'transaction_notes' => ($installmentNumber
                            ? "سداد قسط رقم {$installmentNumber} بعد سداد كامل ربح المكتب"
                            : "سداد بعد سداد كامل ربح المكتب")
                        . " - العقد رقم {$contract->contract_number}",
                    'ledger_notes'      => "قيد سداد قسط للمستثمر {$invName} — عقد #{$contract->contract_number}"
                        . ($installmentNumber ? " — قسط #{$installmentNumber}" : '')
                        . $accountNote,
                ];

                $allocatedSum = round($allocatedSum + $alloc, 2);
            }
        }

        if (!empty($investorEntries)) {
            $this->investorTransactionLogger->log($contract, $investorEntries, $statusName, [
                'transaction_date'    => $now,
                'installment_id'      => $installmentId,
                'bank_account_id'     => $bankAccountId,
                'safe_id'             => $safeId,
                'allow_type_fallback' => true,
                'fallback_direction'  => 'in',
            ]);
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
}
