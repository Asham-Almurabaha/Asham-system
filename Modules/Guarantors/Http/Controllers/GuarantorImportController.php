<?php

namespace Modules\Guarantors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ResetsImportSessions;
use Modules\Guarantors\Entities\Guarantor;
use Modules\Guarantors\Exports\GuarantorsFailuresFixExport;
use Modules\Guarantors\Exports\GuarantorsSkippedExport;
use Modules\Guarantors\Exports\GuarantorsTemplateExport;
use Modules\Guarantors\Imports\GuarantorsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class GuarantorImportController extends Controller
{
    use ResetsImportSessions;

    public function create(Request $request)
    {
        $this->resetImportSession('guarantors_import', [
            'summary',
            'failures_simple',
            'errors_simple',
            'skipped_simple',
            'pending_updates',
        ], $request, ['guarantors_import_just_done', 'guarantors_import_action']);

        return view('guarantors::import');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new GuarantorsImport();

        try {
            Excel::import($import, $request->file('file'));

            $failedValidation = method_exists($import, 'getFailedValidationCount')
                ? $import->getFailedValidationCount() : 0;

            $failuresRaw   = $import->failures();
            $failuresCount = is_countable($failuresRaw) ? count($failuresRaw)
                : (method_exists($failuresRaw, 'count') ? (int)$failuresRaw->count() : 0);

            $rowsTotal   = $import->getRowCount() + $failedValidation;
            $skippedReal = $import->getSkippedCount();

            $inserted  = $import->getInsertedCount();
            $updated   = $import->getUpdatedCount(); // تكملة الناقص فقط
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

            session()->put('guarantors_import.summary', $summary);
            $this->storePendingUpdates(is_array($pendingUpdates) ? $pendingUpdates : []);

            // تبسيط الإخفاقات
            $iter = $failuresRaw instanceof Collection ? $failuresRaw : collect($failuresRaw);
            $failuresSimple = $iter->map(function ($f) {
                if (is_object($f) && method_exists($f, 'row')) {
                    $attr = $f->attribute();
                    return [
                        'row'       => (int)$f->row(),
                        'attribute' => is_array($attr) ? implode(',', $attr) : (string)$attr,
                        'messages'  => implode(' | ', (array)$f->errors()),
                        'values'    => $f->values(),
                    ];
                }
                if (is_array($f)) {
                    $attr = $f['attribute'] ?? '';
                    $errs = $f['errors'] ?? [];
                    return [
                        'row'       => (int)($f['row'] ?? 0),
                        'attribute' => is_array($attr) ? implode(',', $attr) : (string)$attr,
                        'messages'  => implode(' | ', (array)$errs),
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

            $skippedSimple = $import->skipped();

            // flash: تظهر مرة بعد الـ redirect وتختفي مع أول Refresh
            session()->flash('guarantors_import.failures_simple', $failuresSimple);
            session()->flash('guarantors_import.skipped_simple',  $skippedSimple);

            $savedMessage = $inserted > 0
                ? 'تم حفظ '.$inserted.' كفيل جديد.'
                : 'لا توجد إضافات جديدة.';

            if ($pending > 0) {
                $savedMessage .= ' توجد '.$pending.' تعديلات بانتظار التأكيد.';
            }

            return redirect()
                ->route('guarantors.import.form')
                ->with('success', $savedMessage)
                ->with('summary', $summary)
                ->with('failures', $failuresRaw)
                ->with('failures_simple', $failuresSimple)
                ->with('errors_simple', collect($import->errors() ?? [])->map(fn($e) =>
                    is_object($e) && method_exists($e, 'getMessage') ? (string)$e->getMessage() : (string)$e
                )->all())
                ->with('guarantors_import_just_done', true);

        } catch (\Throwable $e) {
            return redirect()
                ->route('guarantors.import.form')
                ->withErrors(['file' => 'تعذّر الاستيراد: ' . $e->getMessage()])
                ->with('guarantors_import_action', true);
        }
    }

    public function template()
    {
        return Excel::download(new GuarantorsTemplateExport, 'guarantors_import_template.xlsx');
    }

    public function exportFailuresFix()
    {
        $failures = session('guarantors_import.failures_simple', []);
        $skipped  = session('guarantors_import.skipped_simple',  []);

        $noFailures = empty($failures) || (is_countable($failures) && count($failures) === 0);
        $noSkipped  = empty($skipped)  || (is_countable($skipped)  && count($skipped)  === 0);
        if ($noFailures && $noSkipped) {
            return redirect()->route('guarantors.import.form')
                ->with('info', 'لا توجد أخطاء أو صفوف متخطاة لتوليد الملف.')
                ->with('guarantors_import_action', true);
        }

        if ($failures instanceof Collection) $failures = $failures->all();
        if ($skipped  instanceof Collection) $skipped  = $skipped->all();

        if (class_exists(\Modules\Guarantors\Exports\GuarantorsIssuesExport::class)) {
            return Excel::download(
                new \Modules\Guarantors\Exports\GuarantorsIssuesExport(
                    is_array($failures) ? $failures : (array)$failures,
                    is_array($skipped)  ? $skipped  : (array)$skipped
                ),
                'guarantors_issues.xlsx'
            );
        }

        return Excel::download(new GuarantorsFailuresFixExport($failures), 'guarantors_to_fix.xlsx');
    }

    public function exportSkipped()
    {
        $skipped = session('guarantors_import.skipped_simple', []);

        if (empty($skipped) || (is_countable($skipped) && count($skipped) === 0)) {
            return redirect()->route('guarantors.import.form')
                ->with('info', 'لا توجد بيانات متخطاة لتوليد ملف.')
                ->with('guarantors_import_action', true);
        }

        return Excel::download(new GuarantorsSkippedExport($skipped), 'guarantors_skipped.xlsx');
    }

    public function confirmPending(Request $request, string $token)
    {
        $pending = $this->getPendingUpdatesFromSession();

        if (!isset($pending[$token])) {
            return redirect()->route('guarantors.import.form')
                ->with('info', 'هذا التعديل غير موجود أو تمت معالجته مسبقًا.')
                ->with('guarantors_import_action', true);
        }

        $entry = $pending[$token];
        unset($pending[$token]);

        $guarantorId = $entry['guarantor_id'] ?? null;
        $guarantor   = $guarantorId ? Guarantor::find($guarantorId) : null;

        if (!$guarantor) {
            $this->storePendingUpdates($pending);
            $this->syncPendingSummary($pending, false);

            return redirect()->route('guarantors.import.form')
                ->with('info', 'الكفيل غير موجود، أزيل التعديل من قائمة الانتظار.')
                ->with('guarantors_import_action', true);
        }

        $updates = $entry['updates'] ?? [];
        if (!is_array($updates) || empty($updates)) {
            $this->storePendingUpdates($pending);
            $this->syncPendingSummary($pending, false);

            return redirect()->route('guarantors.import.form')
                ->with('info', 'لا توجد قيم قابلة للتحديث لهذا التعديل.')
                ->with('guarantors_import_action', true);
        }

        $fillable = array_flip($guarantor->getFillable());
        $apply     = [];
        foreach ($updates as $field => $value) {
            if (isset($fillable[$field])) {
                $apply[$field] = $value;
            }
        }

        if (empty($apply)) {
            $this->storePendingUpdates($pending);
            $this->syncPendingSummary($pending, false);

            return redirect()->route('guarantors.import.form')
                ->with('info', 'الحقول المقترحة غير مسموح بتعديلها، تمت إزالتها من القائمة.')
                ->with('guarantors_import_action', true);
        }

        $guarantor->fill($apply);

        $applied = false;
        if ($guarantor->isDirty()) {
            $guarantor->save();
            $applied = $guarantor->wasChanged();
        }

        $this->storePendingUpdates($pending);
        $this->syncPendingSummary($pending, $applied);

        if ($applied) {
            return redirect()->route('guarantors.import.form')
                ->with('success', 'تم تأكيد تعديل الكفيل '.$guarantor->name.' بنجاح.')
                ->with('guarantors_import_action', true);
        }

        return redirect()->route('guarantors.import.form')
            ->with('info', 'لم يتم تطبيق أي تغييرات لأن البيانات متطابقة سلفًا.')
            ->with('guarantors_import_action', true);
    }

    public function ignorePending(Request $request, string $token)
    {
        $pending = $this->getPendingUpdatesFromSession();

        if (!isset($pending[$token])) {
            return redirect()->route('guarantors.import.form')
                ->with('info', 'هذا التعديل غير موجود أو تمت معالجته مسبقًا.')
                ->with('guarantors_import_action', true);
        }

        $entry = $pending[$token];
        unset($pending[$token]);

        $this->storePendingUpdates($pending);
        $this->syncPendingSummary($pending, false);

        $name = $entry['guarantor_name'] ?? 'الكفيل';

        return redirect()->route('guarantors.import.form')
            ->with('info', 'تم تجاهل التعديل للكفيل '.$name.'.')
            ->with('guarantors_import_action', true);
    }

    public function storePendingAsNew(Request $request, string $token)
    {
        $pending = $this->getPendingUpdatesFromSession();

        if (!isset($pending[$token])) {
            return redirect()->route('guarantors.import.form')
                ->with('info', 'هذا التعديل غير موجود أو تمت معالجته مسبقًا.')
                ->with('guarantors_import_action', true);
        }

        $entry = $pending[$token];

        $payload = $entry['payload'] ?? [];
        if (!is_array($payload) || empty($payload)) {
            $payload = $entry['updates'] ?? [];

            $existingId = $entry['guarantor_id'] ?? null;
            $existing   = $existingId ? Guarantor::find($existingId) : null;

            if ($existing) {
                foreach ($existing->getFillable() as $field) {
                    if (!array_key_exists($field, $payload)) {
                        $payload[$field] = $existing->getAttribute($field);
                    }
                }
            }
        }

        if (!is_array($payload) || empty($payload)) {
            return redirect()->route('guarantors.import.form')
                ->with('info', 'لا توجد بيانات كافية لإنشاء سجل جديد من هذا التعديل.')
                ->with('guarantors_import_action', true);
        }

        $guarantorPrototype = new Guarantor();
        $fillableMap        = array_flip($guarantorPrototype->getFillable());
        $data               = [];

        foreach ($payload as $field => $value) {
            if (isset($fillableMap[$field])) {
                $data[$field] = $value;
            }
        }

        $nameValue = $data['name'] ?? null;
        if ($nameValue === null || trim((string) $nameValue) === '') {
            return redirect()->route('guarantors.import.form')
                ->with('info', 'الاسم مطلوب لإنشاء كفيل جديد من هذا التعديل.')
                ->with('guarantors_import_action', true);
        }

        try {
            $newGuarantor = Guarantor::create($data);
        } catch (\Throwable $e) {
            return redirect()->route('guarantors.import.form')
                ->with('error', 'تعذّر إنشاء كفيل جديد: '.$e->getMessage())
                ->with('guarantors_import_action', true);
        }

        unset($pending[$token]);
        $this->storePendingUpdates($pending);
        $this->syncPendingSummary($pending, false, true);

        return redirect()->route('guarantors.import.form')
            ->with('success', 'تم حفظ كفيل جديد باسم '.$newGuarantor->name.'.')
            ->with('guarantors_import_action', true);
    }

    private function getPendingUpdatesFromSession(): array
    {
        $pending = session('guarantors_import.pending_updates', []);

        if ($pending instanceof Collection) {
            return $pending->toArray();
        }

        return is_array($pending) ? $pending : [];
    }

    private function storePendingUpdates(array $pending): void
    {
        if (empty($pending)) {
            session()->forget('guarantors_import.pending_updates');
            return;
        }

        session(['guarantors_import.pending_updates' => $pending]);
    }

    private function syncPendingSummary(array $pending, bool $appliedUpdate, bool $inserted = false): void
    {
        $summary = session('guarantors_import.summary', []);

        $summary['pending'] = is_countable($pending) ? count($pending) : 0;

        if ($appliedUpdate) {
            $summary['updated'] = (int) ($summary['updated'] ?? 0) + 1;
            $summary['changed'] = (int) ($summary['changed'] ?? 0) + 1;
        }

        if ($inserted) {
            $summary['inserted'] = (int) ($summary['inserted'] ?? 0) + 1;
            $summary['changed']  = (int) ($summary['changed'] ?? 0) + 1;
        }

        session(['guarantors_import.summary' => $summary]);
    }
}
