<?php

namespace App\Http\Controllers;

use App\Exports\InvestorsFailuresFixExport;
use App\Exports\InvestorsSkippedExport;
use App\Exports\InvestorsTemplateExport;
use App\Imports\InvestorsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class InvestorImportController extends Controller
{
    public function create()
    {
        // مهم: ما نمسحش السيشن هنا، لأننا عايزين نعرض نتيجة الاستيراد بعد redirect.
        // البيانات هتختفي لوحدها بعد أول Refresh لأننا هنستخدم flash في store().
        return view('investors.import');
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

            // المتخطّى المبسّط للتصدير (جاي من InvestorsImport::skipped())
            $skippedSimple = $import->skipped();

            // ==== استخدم "flash" بدل put —> تظهر مرة بعد الـ redirect وتختفي مع أول Refresh ====
            session()->flash('investors_import.failures_simple', $failuresSimple);
            session()->flash('investors_import.skipped_simple',  $skippedSimple);
            session()->flash('investors_import.summary',         $summary);

            return redirect()
                ->route('investors.import.form') // عدّل الاسم لو مختلف عندك
                ->with('success', 'تم حفظ فعليًا: '.$changed.' (جديد: '.$inserted.'، تعديل: '.$updated.')')
                ->with('failures', $failuresRaw)
                ->with('failures_simple', $failuresSimple)
                ->with('errors_simple', collect($import->errors() ?? [])->map(fn($e) =>
                    is_object($e) && method_exists($e, 'getMessage') ? (string)$e->getMessage() : (string)$e
                )->all());

        } catch (\Throwable $e) {
            return redirect()
                ->route('investors.import.form')
                ->withErrors(['file' => 'تعذّر الاستيراد: ' . $e->getMessage()]);
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
                ->with('info', 'لا توجد أخطاء أو صفوف متخطاة لتوليد الملف.');
        }

        if ($failures instanceof Collection) $failures = $failures->all();
        if ($skipped  instanceof Collection) $skipped  = $skipped->all();

        // لو عندك كلاس متعدد الشيتات
        if (class_exists(\App\Exports\InvestorsIssuesExport::class)) {
            return Excel::download(
                new \App\Exports\InvestorsIssuesExport(
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
                ->with('info', 'لا توجد بيانات متخطاة لتوليد ملف.');
        }

        return Excel::download(new InvestorsSkippedExport($skipped), 'investors_skipped.xlsx');
    }
}
