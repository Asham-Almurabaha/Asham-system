<?php

namespace Modules\Investors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ResetsImportSessions;
use Modules\Investors\Entities\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Investors\Exports\InvestorsFailuresFixExport;
use Modules\Investors\Exports\InvestorsSkippedExport;
use Modules\Investors\Exports\InvestorsTemplateExport;
use Modules\Investors\Imports\InvestorsImport;

class InvestorImportController extends Controller
{
    use ResetsImportSessions;

    public function create(Request $request)
    {
        $this->resetImportSession('investors_import', [
            'summary',
            'failures_simple',
            'errors_simple',
            'skipped_simple',
            'pending_updates',
        ], $request, ['investors_import_just_done', 'investors_import_action']);

        return view('investors::import');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new InvestorsImport();

        try {
            Excel::import($import, $request->file('file'));

            // فشل التحقق (قبل model())
            $failedValidation = method_exists($import, 'getFailedValidationCount')
                ? $import->getFailedValidationCount() : 0;

            // قائمة الإخفاقات (لعرض الجدول والـ export)
            $failuresRaw   = $import->failures();
            $failuresCount = is_countable($failuresRaw) ? count($failuresRaw)
                : (method_exists($failuresRaw, 'count') ? (int)$failuresRaw->count() : 0);

            // عدد الصفوف الإجمالي التي تم التعامل معها (وصلت model + فشلت validation)
            $rowsTotal   = $import->getRowCount() + $failedValidation;

            // المتخطّى النهائي: import->getSkippedCount() يحتوي على ما تم تخطيه داخل model + إخفاقات التحقق (حسب تعديلنا في الـ Import)
            $skippedReal = $import->getSkippedCount();

            $inserted  = $import->getInsertedCount();
            $updated   = $import->getUpdatedCount(); // تغييرات فعلية بعد التأكيد اليدوي
            $unchanged = $import->getUnchangedCount();
            $pending   = method_exists($import, 'getPendingCount') ? $import->getPendingCount() : 0;
            $pendingUpdates = method_exists($import, 'getPendingUpdates') ? $import->getPendingUpdates() : [];
            $changed   = $inserted + $updated;

            $summary = [
                'rows'       => $rowsTotal,
                'inserted'   => $inserted,
                'updated'    => $updated,
                'pending'    => $pending,
                'unchanged'  => $unchanged,
                'skipped'    => $skippedReal,
                'changed'    => $changed,
            ];

            session()->put('investors_import.summary', $summary);
            $this->storePendingUpdates(is_array($pendingUpdates) ? $pendingUpdates : []);

            // تبسيط الإخفاقات لملف التصحيح
            $iter = $failuresRaw instanceof Collection ? $failuresRaw : collect($failuresRaw);
            $failuresSimple = $iter->map(function ($f) {
                if (is_object($f) && method_exists($f, 'row')) {
                    $attr = $f->attribute();
                    return [
                        'row'       => (int) $f->row(),
                        'attribute' => is_array($attr) ? implode(',', $attr) : (string) $attr,
                        'messages'  => implode(' | ', (array) $f->errors()),
                        'values'    => $f->values(),
                    ];
                }
                if (is_array($f)) {
                    $attr = $f['attribute'] ?? '';
                    $errs = $f['errors'] ?? [];
                    return [
                        'row'       => (int) ($f['row'] ?? 0),
                        'attribute' => is_array($attr) ? implode(',', $attr) : (string) $attr,
                        'messages'  => implode(' | ', (array) $errs),
                        'values'    => $f['values'] ?? [],
                    ];
                }
                return [
                    'row'       => 0,
                    'attribute' => '',
                    'messages'  => 'Unknown failure format',
                    'values'    => [],
                ];
            })->all();

            // المتخطّى المبسّط للتصدير (جاي من InvestorsImport::skipped())
            $skippedSimple = $import->skipped();

            // ==== استخدم "flash" بدل put —> تظهر مرة بعد الـ redirect وتختفي مع أول Refresh ====
            session()->flash('investors_import.failures_simple', $failuresSimple);
            session()->flash('investors_import.skipped_simple',  $skippedSimple);

            $savedMessage = $inserted > 0
                ? 'تم حفظ '.$inserted.' مستثمر جديد.'
                : 'لا توجد إضافات جديدة.';

            if ($pending > 0) {
                $savedMessage .= ' توجد '.$pending.' تعديلات بانتظار التأكيد.';
            }

            return redirect()
                ->route('investors.import.form')
                ->with('success', $savedMessage)
                ->with('summary', $summary)
                ->with('failures', $failuresRaw)
                ->with('failures_simple', $failuresSimple)
                ->with('errors_simple', collect($import->errors() ?? [])->map(fn($e) =>
                    is_object($e) && method_exists($e, 'getMessage') ? (string)$e->getMessage() : (string)$e
                )->all())
                ->with('investors_import_just_done', true);

        } catch (\Throwable $e) {
            return redirect()
                ->route('investors.import.form')
                ->withErrors(['file' => 'تعذّر الاستيراد: ' . $e->getMessage()])
                ->with('investors_import_action', true);
        }
    }

    public function template()
    {
        return Excel::download(new InvestorsTemplateExport, 'investors_import_template.xlsx');
    }

    /**
     * نفس الزر يصدّر ملفًا واحدًا:
     * - لو Class InvestorsIssuesExport موجود → شيتين (Failures + Skipped)
     * - غير كده → يرجع لشيت Failures فقط (نفس السلوك القديم)
     */
    public function exportFailuresFix()
    {
        $failures = session('investors_import.failures_simple', []);
        $skipped  = session('investors_import.skipped_simple',  []);

        // لو الاتنين فاضيين
        $noFailures = empty($failures) || (is_countable($failures) && count($failures) === 0);
        $noSkipped  = empty($skipped)  || (is_countable($skipped)  && count($skipped)  === 0);
        if ($noFailures && $noSkipped) {
            return redirect()->route('investors.import.form')
                ->with('info', 'لا توجد أخطاء أو صفوف متخطاة لتوليد الملف.')
                ->with('investors_import_action', true);
        }

        if ($failures instanceof Collection) $failures = $failures->all();
        if ($skipped  instanceof Collection) $skipped  = $skipped->all();

        // لو عندك كلاس متعدد الشيتات
        if (class_exists(\Modules\Investors\Exports\InvestorsIssuesExport::class)) {
            return Excel::download(
                new \Modules\Investors\Exports\InvestorsIssuesExport(
                    is_array($failures) ? $failures : (array)$failures,
                    is_array($skipped)  ? $skipped  : (array)$skipped
                ),
                'investors_issues.xlsx'
            );
        }

        // fallback: إخراج الأخطاء فقط
        return Excel::download(new InvestorsFailuresFixExport($failures), 'investors_to_fix.xlsx');
    }

    // (اختياري) لو حبيت تسيب مسار مستقل للمتخطّى فقط
    public function exportSkipped()
    {
        $skipped = session('investors_import.skipped_simple', []);

        if (empty($skipped) || (is_countable($skipped) && count($skipped) === 0)) {
            return redirect()->route('investors.import.form')
                ->with('info', 'لا توجد بيانات متخطاة لتوليد ملف.')
                ->with('investors_import_action', true);
        }

        return Excel::download(new InvestorsSkippedExport($skipped), 'investors_skipped.xlsx');
    }

    public function confirmPending(Request $request, string $token)
    {
        $pending = $this->getPendingUpdatesFromSession();

        if (!isset($pending[$token])) {
            return redirect()->route('investors.import.form')
                ->with('info', 'هذا التعديل غير موجود أو تمت معالجته مسبقًا.')
                ->with('investors_import_action', true);
        }

        $entry = $pending[$token];
        unset($pending[$token]);

        $investorId = $entry['investor_id'] ?? null;
        $investor   = $investorId ? Investor::find($investorId) : null;

        if (!$investor) {
            $this->storePendingUpdates($pending);
            $this->syncPendingSummary($pending, false);

            return redirect()->route('investors.import.form')
                ->with('info', 'المستثمر غير موجود، أزيل التعديل من قائمة الانتظار.')
                ->with('investors_import_action', true);
        }

        $updates = $entry['updates'] ?? [];
        if (!is_array($updates) || empty($updates)) {
            $this->storePendingUpdates($pending);
            $this->syncPendingSummary($pending, false);

            return redirect()->route('investors.import.form')
                ->with('info', 'لا توجد قيم قابلة للتحديث لهذا التعديل.')
                ->with('investors_import_action', true);
        }

        $fillable = array_flip($investor->getFillable());
        $apply     = [];
        foreach ($updates as $field => $value) {
            if (isset($fillable[$field])) {
                $apply[$field] = $value;
            }
        }

        if (empty($apply)) {
            $this->storePendingUpdates($pending);
            $this->syncPendingSummary($pending, false);

            return redirect()->route('investors.import.form')
                ->with('info', 'الحقول المقترحة غير مسموح بتعديلها، تمت إزالتها من القائمة.')
                ->with('investors_import_action', true);
        }

        $investor->fill($apply);

        $applied = false;
        if ($investor->isDirty()) {
            $investor->save();
            $applied = $investor->wasChanged();
        }

        $this->storePendingUpdates($pending);
        $this->syncPendingSummary($pending, $applied);

        if ($applied) {
            return redirect()->route('investors.import.form')
                ->with('success', 'تم تأكيد تعديل المستثمر '.$investor->name.' بنجاح.')
                ->with('investors_import_action', true);
        }

        return redirect()->route('investors.import.form')
            ->with('info', 'لم يتم تطبيق أي تغييرات لأن البيانات متطابقة سلفًا.')
            ->with('investors_import_action', true);
    }

    public function ignorePending(Request $request, string $token)
    {
        $pending = $this->getPendingUpdatesFromSession();

        if (!isset($pending[$token])) {
            return redirect()->route('investors.import.form')
                ->with('info', 'هذا التعديل غير موجود أو تمت معالجته مسبقًا.')
                ->with('investors_import_action', true);
        }

        $entry = $pending[$token];
        unset($pending[$token]);

        $this->storePendingUpdates($pending);
        $this->syncPendingSummary($pending, false);

        $name = $entry['investor_name'] ?? 'المستثمر';

        return redirect()->route('investors.import.form')
            ->with('info', 'تم تجاهل التعديل للمستثمر '.$name.'.')
            ->with('investors_import_action', true);
    }

    public function storePendingAsNew(Request $request, string $token)
    {
        $pending = $this->getPendingUpdatesFromSession();

        if (!isset($pending[$token])) {
            return redirect()->route('investors.import.form')
                ->with('info', 'هذا التعديل غير موجود أو تمت معالجته مسبقًا.')
                ->with('investors_import_action', true);
        }

        $entry = $pending[$token];

        $payload = $entry['payload'] ?? [];
        if (!is_array($payload) || empty($payload)) {
            $payload = $entry['updates'] ?? [];

            $existingId = $entry['investor_id'] ?? null;
            $existing   = $existingId ? Investor::find($existingId) : null;

            if ($existing) {
                foreach ($existing->getFillable() as $field) {
                    if (!array_key_exists($field, $payload)) {
                        $payload[$field] = $existing->getAttribute($field);
                    }
                }
            }
        }

        if (!is_array($payload) || empty($payload)) {
            return redirect()->route('investors.import.form')
                ->with('info', 'لا توجد بيانات كافية لإنشاء سجل جديد من هذا التعديل.')
                ->with('investors_import_action', true);
        }

        $investorPrototype = new Investor();
        $fillableMap       = array_flip($investorPrototype->getFillable());
        $data              = [];

        foreach ($payload as $field => $value) {
            if (isset($fillableMap[$field])) {
                $data[$field] = $value;
            }
        }

        $nameValue = $data['name'] ?? null;
        if ($nameValue === null || trim((string) $nameValue) === '') {
            return redirect()->route('investors.import.form')
                ->with('info', 'الاسم مطلوب لإنشاء مستثمر جديد من هذا التعديل.')
                ->with('investors_import_action', true);
        }

        try {
            $newInvestor = Investor::create($data);
        } catch (\Throwable $e) {
            return redirect()->route('investors.import.form')
                ->with('error', 'تعذّر إنشاء مستثمر جديد: '.$e->getMessage())
                ->with('investors_import_action', true);
        }

        unset($pending[$token]);
        $this->storePendingUpdates($pending);
        $this->syncPendingSummary($pending, false, true);

        return redirect()->route('investors.import.form')
            ->with('success', 'تم حفظ مستثمر جديد باسم '.$newInvestor->name.'.')
            ->with('investors_import_action', true);
    }

    private function getPendingUpdatesFromSession(): array
    {
        $pending = session('investors_import.pending_updates', []);

        if ($pending instanceof Collection) {
            return $pending->toArray();
        }

        return is_array($pending) ? $pending : [];
    }

    private function storePendingUpdates(array $pending): void
    {
        if (empty($pending)) {
            session()->forget('investors_import.pending_updates');
            return;
        }

        session(['investors_import.pending_updates' => $pending]);
    }

    private function syncPendingSummary(array $pending, bool $appliedUpdate, bool $inserted = false): void
    {
        $summary = session('investors_import.summary', []);

        $summary['pending'] = is_countable($pending) ? count($pending) : 0;

        if ($appliedUpdate) {
            $summary['updated'] = (int) ($summary['updated'] ?? 0) + 1;
            $summary['changed'] = (int) ($summary['changed'] ?? 0) + 1;
        }

        if ($inserted) {
            $summary['inserted'] = (int) ($summary['inserted'] ?? 0) + 1;
            $summary['changed']  = (int) ($summary['changed'] ?? 0) + 1;
        }

        session(['investors_import.summary' => $summary]);
    }
}
