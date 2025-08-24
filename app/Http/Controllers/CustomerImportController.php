<?php

namespace App\Http\Controllers;

use App\Exports\CustomersFailuresFixExport;
use App\Exports\CustomersSkippedExport;
use App\Exports\CustomersTemplateExport;
use App\Imports\CustomersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class CustomerImportController extends Controller
{
    public function create()
    {
        // ما نمسحش السيشن هنا علشان نعرض نتائج آخر استيراد بعد الـ redirect.
        return view('customers.import');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new CustomersImport();

        try {
            Excel::import($import, $request->file('file'));

            // فشل التحقق (قبل model())
            $failedValidation = method_exists($import, 'getFailedValidationCount')
                ? $import->getFailedValidationCount() : 0;

            // الإخفاقات الكاملة
            $failuresRaw   = $import->failures();
            $failuresCount = is_countable($failuresRaw) ? count($failuresRaw)
                : (method_exists($failuresRaw, 'count') ? (int)$failuresRaw->count() : 0);

            // إجمالي الصفوف (وصلت model + فشلت validation)
            $rowsTotal   = $import->getRowCount() + $failedValidation;

            // المتخطّى النهائي
            $skippedReal = $import->getSkippedCount();

            $inserted  = $import->getInsertedCount();
            $updated   = $import->getUpdatedCount(); // "تكملة الناقص فقط"
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

            // المتخطّى المبسّط (من الـ Import)
            $skippedSimple = $import->skipped();

            // نستخدم flash: تظهر مرة بعد الـ redirect وتختفي مع أول Refresh
            session()->flash('customers_import.failures_simple', $failuresSimple);
            session()->flash('customers_import.skipped_simple',  $skippedSimple);
            session()->flash('customers_import.summary',         $summary);

            return redirect()
                ->route('customers.import.form') // تأكّد من اسم الروت
                ->with('success', 'تم حفظ فعليًا: '.$changed.' (جديد: '.$inserted.'، تعديل: '.$updated.')')
                ->with('summary', $summary)
                ->with('failures', $failuresRaw)
                ->with('failures_simple', $failuresSimple)
                ->with('errors_simple', collect($import->errors() ?? [])->map(fn($e) =>
                    is_object($e) && method_exists($e, 'getMessage') ? (string)$e->getMessage() : (string)$e
                )->all());

        } catch (\Throwable $e) {
            return redirect()
                ->route('customers.import.form')
                ->withErrors(['file' => 'تعذّر الاستيراد: ' . $e->getMessage()]);
        }
    }

    public function template()
    {
        return Excel::download(new CustomersTemplateExport, 'customers_import_template.xlsx');
    }

    public function exportFailuresFix()
    {
        $failures = session('customers_import.failures_simple', []);
        $skipped  = session('customers_import.skipped_simple',  []);

        $noFailures = empty($failures) || (is_countable($failures) && count($failures) === 0);
        $noSkipped  = empty($skipped)  || (is_countable($skipped)  && count($skipped)  === 0);
        if ($noFailures && $noSkipped) {
            return redirect()->route('customers.import.form')
                ->with('info', 'لا توجد أخطاء أو صفوف متخطاة لتوليد الملف.');
        }

        if ($failures instanceof Collection) $failures = $failures->all();
        if ($skipped  instanceof Collection) $skipped  = $skipped->all();

        if (class_exists(\App\Exports\CustomersIssuesExport::class)) {
            // ملف بشيتين: Failures + Skipped
            return Excel::download(
                new \App\Exports\CustomersIssuesExport(
                    is_array($failures) ? $failures : (array)$failures,
                    is_array($skipped)  ? $skipped  : (array)$skipped
                ),
                'customers_issues.xlsx'
            );
        }

        // fallback: أخطاء فقط
        return Excel::download(new CustomersFailuresFixExport($failures), 'customers_to_fix.xlsx');
    }

    public function exportSkipped()
    {
        $skipped = session('customers_import.skipped_simple', []);

        if (empty($skipped) || (is_countable($skipped) && count($skipped) === 0)) {
            return redirect()->route('customers.import.form')
                ->with('info', 'لا توجد بيانات متخطاة لتوليد ملف.');
        }

        return Excel::download(new CustomersSkippedExport($skipped), 'customers_skipped.xlsx');
    }
}
