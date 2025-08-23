<?php

namespace App\Http\Controllers;

use App\Exports\GuarantorsTemplateExport;
use App\Exports\GuarantorsFailuresFixExport;
use App\Imports\GuarantorsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class GuarantorImportController extends Controller
{
    public function create()
    {
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

            $failedValidation = max($failedValidation, $failuresCount);

            $rowsTotal   = $import->getRowCount() + $failedValidation;
            $skippedReal = $import->getSkippedCount() + $failedValidation;

            $inserted  = $import->getInsertedCount();
            $updated   = $import->getUpdatedCount();
            $unchanged = $import->getUnchangedCount();
            $changed   = $inserted + $updated;

            $summary = [
                'rows'      => $rowsTotal,
                'inserted'  => $inserted,
                'updated'   => $updated,
                'unchanged' => $unchanged,
                'skipped'   => $skippedReal,
                'changed'   => $changed,
            ];

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

            // تخزين للتحميل
            session()->forget([
                'guarantors_import.failures_simple',
                'guarantors_import.summary',
            ]);
            session()->put('guarantors_import.failures_simple', $failuresSimple);
            session()->put('guarantors_import.summary',        $summary);
            session()->save();

            return back()
                ->with('success', 'تم حفظ فعليًا: '.$changed.' (جديد: '.$inserted.'، تعديل: '.$updated.')')
                ->with('summary', $summary)
                ->with('failures', $failuresRaw)
                ->with('failures_simple', $failuresSimple)
                ->with('errors_simple', collect($import->errors() ?? [])->map(fn($e) =>
                    is_object($e) && method_exists($e, 'getMessage') ? (string)$e->getMessage() : (string)$e
                )->all());

        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'تعذّر الاستيراد: ' . $e->getMessage()]);
        }
    }

    public function template()
    {
        return Excel::download(new GuarantorsTemplateExport, 'guarantors_import_template.xlsx');
    }

    public function exportFailuresFix()
    {
        $failures = session('guarantors_import.failures_simple', []);

        if (empty($failures) || (is_countable($failures) && count($failures) === 0)) {
            return redirect()->route('guarantors.import.form')
                ->with('info', 'لا توجد أخطاء لتوليد ملف التصحيح.');
        }
        if ($failures instanceof Collection) $failures = $failures->all();

        return Excel::download(new GuarantorsFailuresFixExport($failures), 'guarantors_to_fix.xlsx');
    }
}
