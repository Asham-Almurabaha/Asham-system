<?php

namespace App\Http\Controllers;

use App\Exports\CustomersTemplateExport;
use App\Exports\ImportFailuresExport;
use App\Exports\CustomersFailuresFixExport;
use App\Imports\CustomersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class CustomerImportController extends Controller
{
    public function create()
    {
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

            // صفوف فشلت قبل model()
            $failedValidation = method_exists($import, 'getFailedValidationCount')
                ? $import->getFailedValidationCount()
                : 0;

            // من SkipsFailures
            $failuresRaw   = $import->failures();
            $failuresCount = is_countable($failuresRaw) ? count($failuresRaw)
                : (method_exists($failuresRaw, 'count') ? (int)$failuresRaw->count() : 0);

            // تأكيد الدقة
            $failedValidation = max($failedValidation, $failuresCount);

            // إجمالي الصفوف = اللي وصل model() + اللي فشل validation
            $rowsTotal   = $import->getRowCount() + $failedValidation;
            // المتخطّى = skipped داخل model() + failedValidation
            $skippedReal = $import->getSkippedCount() + $failedValidation;

            // الأرقام النهائية
            $inserted  = $import->getInsertedCount();
            $updated   = $import->getUpdatedCount();   // تحديثات حقيقية فقط
            $unchanged = $import->getUnchangedCount();
            $changed   = $inserted + $updated;         // فعليًا المحفوظ

            $summary = [
                'rows'       => $rowsTotal,
                'inserted'   => $inserted,
                'updated'    => $updated,
                'unchanged'  => $unchanged,
                'skipped'    => $skippedReal,
                'changed'    => $changed,
            ];

            // تبسيط الفشل للتصدير
            $iter = $failuresRaw instanceof Collection ? $failuresRaw : collect($failuresRaw);
            $failuresSimple = $iter->map(function ($f) {
                if (is_object($f) && method_exists($f, 'row')) {
                    $attr = $f->attribute();
                    return [
                        'row'       => (int) $f->row(),
                        'attribute' => is_array($attr) ? implode(',', $attr) : (string) $attr,
                        'messages'  => implode(' | ', (array) $f->errors() ),
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

            // نخزّن للتحميلات
            session()->forget([
                'customers_import.failures_simple',
                'customers_import.summary',
            ]);
            session()->put('customers_import.failures_simple', $failuresSimple);
            session()->put('customers_import.summary',        $summary);
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
        return Excel::download(new CustomersTemplateExport, 'customers_import_template.xlsx');
    }

    
    public function exportFailuresFix()
    {
        $failures = session('customers_import.failures_simple', []);

        if (empty($failures) || (is_countable($failures) && count($failures) === 0)) {
            return redirect()->route('customers.import.form')
                ->with('info', 'لا توجد أخطاء لتوليد ملف التصحيح.');
        }

        if ($failures instanceof Collection) {
            $failures = $failures->all();
        }

        return Excel::download(new CustomersFailuresFixExport($failures), 'customers_to_fix.xlsx');
    }

    public function exportSkipped()
    {
        $skipped = session('customers_import.skipped_simple', []);

        if (empty($skipped) || (is_countable($skipped) && count($skipped) === 0)) {
            return redirect()->route('customers.import.form')
                ->with('info', 'لا توجد بيانات متخطاة لتوليد ملف.');
        }

        return Excel::download(new CustomersFailuresFixExport($skipped), 'customers_skipped.xlsx');
    }
}
