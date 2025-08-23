<?php

namespace App\Http\Controllers;

use App\Exports\ContractsFailuresFixExport;
use App\Exports\ContractsTemplateExport;
use App\Imports\ContractsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ContractsImportController extends Controller
{
    /**
     * عرض فورم الاستيراد.
     * ينظّف حالة الجلسة إلا إذا جايين مباشرة بعد عملية استيراد ناجحة (import_just_done)
     * أو تم تمرير keep=1 يدويًا في الـ URL.
     */
    public function create(Request $request)
    {
        $keep = session()->has('import_just_done') || $request->boolean('keep', false);

        // مسح الحالة لإرجاع الصفحة للوضع الافتراضي بعد أي Refresh عادي
        if (!$keep) {
            session()->forget([
                'contracts_import.summary',
                'contracts_import.failures_simple',
                'contracts_import.errors_simple',
            ]);
        }

        return view('contracts.import');
    }

    /**
     * تنفيذ الاستيراد من الملف المرفوع.
     * يحسب الملخص، يبسط الإخفاقات، ويخزنها في الجلسة (keys namespaced)
     * ثم يعيد التوجيه للفورم مع فلاش import_just_done لتفادي المسح في أول عرض فقط.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new ContractsImport();

        try {
            Excel::import($import, $request->file('file'));

            // تقدير أي فشل Validation مبكر (لو الكلاس بيستخدم WithValidation)
            $failedValidation = method_exists($import, 'getFailedValidationCount')
                ? (int) $import->getFailedValidationCount()
                : 0;

            // إخفاقات SkipsFailures (لو موجودة)
            $failuresRaw   = $import->failures();
            $failuresCount = is_countable($failuresRaw)
                ? count($failuresRaw)
                : (method_exists($failuresRaw, 'count') ? (int)$failuresRaw->count() : 0);

            $failedValidation = max($failedValidation, $failuresCount);

            // إجماليات نهائية
            $rowsTotal   = $import->getRowCount() + $failedValidation;
            $skippedReal = $import->getSkippedCount() + $failedValidation;
            $inserted    = $import->getInsertedCount();
            $updated     = $import->getUpdatedCount();
            $unchanged   = $import->getUnchangedCount();
            $changed     = $inserted + $updated;

            $summary = [
                'rows'      => $rowsTotal,
                'inserted'  => $inserted,
                'updated'   => $updated,
                'unchanged' => $unchanged,
                'skipped'   => $skippedReal,
                'changed'   => $changed,
            ];

            // تبسيط الإخفاقات القادمة من SkipsFailures (إن وجدت)
            $iter = $failuresRaw instanceof Collection ? $failuresRaw : collect($failuresRaw);
            $traitSimple = $iter->map(function ($f) {
                if (is_object($f) && method_exists($f, 'row')) {
                    $attr = $f->attribute();
                    return [
                        'row'       => (int) $f->row(),
                        'attribute' => is_array($attr) ? implode(',', $attr) : (string) $attr,
                        'messages'  => implode(' | ', (array) $f->errors()),
                        'values'    => (array) $f->values(),
                    ];
                }
                if (is_array($f)) {
                    $attr = $f['attribute'] ?? '';
                    $errs = $f['errors'] ?? [];
                    return [
                        'row'       => (int) ($f['row'] ?? 0),
                        'attribute' => is_array($attr) ? implode(',', $attr) : (string) $attr,
                        'messages'  => implode(' | ', (array) $errs),
                        'values'    => (array)($f['values'] ?? []),
                    ];
                }
                return [
                    'row'       => 0,
                    'attribute' => '',
                    'messages'  => 'Unknown failure format',
                    'values'    => [],
                ];
            })->all();

            // إخفاقات مخصّصة من ContractsImport::pushFailure()
            $customSimple = (array) $import->getFailuresSimple();

            // الدمج (+ إعادة فهرسة)
            $failuresSimple = array_values(array_merge($traitSimple, $customSimple));

            // تخزين دائم للعرض والتصدير (سيُمسح تلقائياً في create() بعد أول Refresh)
            session()->forget([
                'contracts_import.summary',
                'contracts_import.failures_simple',
                'contracts_import.errors_simple',
            ]);
            session()->put('contracts_import.summary',        $summary);
            session()->put('contracts_import.failures_simple', $failuresSimple);
            session()->put('contracts_import.errors_simple',  (array) $import->getErrorsSimple());
            session()->save();

            // ريدايركت للفورم + إشعار نجاح + فلاش import_just_done (كي لا نمسح الحالة في أول عرض فقط)
            return redirect()->route('contracts.import.form')
                ->with('success', "تم حفظ فعليًا: {$changed} (جديد: {$inserted}، تعديل: {$updated}) — إجمالي: {$rowsTotal}، متخطّى: {$skippedReal}")
                ->with('summary', $summary)
                ->with('import_just_done', true);

        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('contracts.import.form')
                ->withErrors(['file' => 'تعذّر الاستيراد: ' . $e->getMessage()]);
        }
    }

    /**
     * تنزيل تمبليت الاستيراد.
     */
    public function template()
    {
        return Excel::download(new ContractsTemplateExport(), 'contracts_template.xlsx');
    }

    /**
     * تنزيل ملف لتصحيح الصفوف الفاشلة.
     * يعتمد على النسخة المخزّنة دائمًا في الجلسة (وليس الفلاش).
     */
    public function exportFailuresFix()
    {
        $failures = session('contracts_import.failures_simple');

        if ($failures instanceof Collection) {
            $failures = $failures->all();
        }

        if (empty($failures) || (is_countable($failures) && count($failures) === 0)) {
            return redirect()->route('contracts.import.form')
                ->with('info', 'لا توجد أخطاء لتوليد ملف التصحيح.');
        }

        return Excel::download(new ContractsFailuresFixExport($failures), 'contracts_to_fix.xlsx');
    }
}
