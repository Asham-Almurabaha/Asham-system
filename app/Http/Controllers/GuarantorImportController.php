<?php

namespace App\Http\Controllers;

use App\Exports\GuarantorsFailuresFixExport;
use App\Exports\GuarantorsSkippedExport;
use App\Exports\GuarantorsTemplateExport;
use App\Imports\GuarantorsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class GuarantorImportController extends Controller
{
    public function create()
    {
        // بنسيب السيشن كما هو لعرض نتيجة آخر استيراد بعد الـ redirect
        return view('guarantors.import');
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
            $changed   = $inserted + $updated;

            $summary = [
                'rows'       => $rowsTotal,
                'inserted'   => $inserted,
                'updated'    => $updated,
                'unchanged'  => $unchanged,
                'skipped'    => $skippedReal,
                'changed'    => $changed,
            ];

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
            session()->flash('guarantors_import.summary',         $summary);

            return redirect()
                ->route('guarantors.import.form')
                ->with('success', 'تم حفظ فعليًا: '.$changed.' (جديد: '.$inserted.'، تعديل: '.$updated.')')
                ->with('summary', $summary)
                ->with('failures', $failuresRaw)
                ->with('failures_simple', $failuresSimple)
                ->with('errors_simple', collect($import->errors() ?? [])->map(fn($e) =>
                    is_object($e) && method_exists($e, 'getMessage') ? (string)$e->getMessage() : (string)$e
                )->all());

        } catch (\Throwable $e) {
            return redirect()
                ->route('guarantors.import.form')
                ->withErrors(['file' => 'تعذّر الاستيراد: ' . $e->getMessage()]);
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
                ->with('info', 'لا توجد أخطاء أو صفوف متخطاة لتوليد الملف.');
        }

        if ($failures instanceof Collection) $failures = $failures->all();
        if ($skipped  instanceof Collection) $skipped  = $skipped->all();

        if (class_exists(\App\Exports\GuarantorsIssuesExport::class)) {
            return Excel::download(
                new \App\Exports\GuarantorsIssuesExport(
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
                ->with('info', 'لا توجد بيانات متخطاة لتوليد ملف.');
        }

        return Excel::download(new GuarantorsSkippedExport($skipped), 'guarantors_skipped.xlsx');
    }
}
