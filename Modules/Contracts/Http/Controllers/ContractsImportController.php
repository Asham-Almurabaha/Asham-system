<?php

namespace Modules\Contracts\Http\Controllers;

use Modules\Contracts\Exports\ContractsFailuresFixExport;
use Modules\Contracts\Exports\ContractsTemplateExport;
use App\Http\Controllers\Controller;
use Modules\Contracts\Imports\ContractsImport;
use Modules\Contracts\Imports\ContractsBasicImport;
use Modules\Contracts\Imports\ContractInvestorsImport;
use Modules\Contracts\Imports\ContractPaymentsImport;
use App\Support\ImportFailureFormatter;
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
     * فورم استيراد البيانات الأساسية.
     */
    public function createBasic(Request $request)
    {
        $keep = session()->has('import_basic_just_done') || $request->boolean('keep', false);

        if (!$keep) {
            session()->forget([
                'contracts_basic_import.summary',
                'contracts_basic_import.failures_simple',
                'contracts_basic_import.errors_simple',
            ]);
        }

        return view('contracts.import_basic');
    }

    /**
     * فورم استيراد مستثمري العقود.
     */
    public function createInvestors(Request $request)
    {
        $keep = session()->has('import_investors_just_done') || $request->boolean('keep', false);

        if (!$keep) {
            session()->forget([
                'contracts_investors_import.summary',
                'contracts_investors_import.failures_simple',
                'contracts_investors_import.errors_simple',
            ]);
        }

        return view('contracts.import_investors');
    }

    public function createPayments(Request $request)
    {
        $keep = session()->has('import_payments_just_done') || $request->boolean('keep', false);

        if (!$keep) {
            session()->forget([
                'contracts_payments_import.summary',
                'contracts_payments_import.failures_simple',
                'contracts_payments_import.errors_simple',
            ]);
        }

        return view('contracts.import_payments');
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
     * تنفيذ استيراد العقود بالبيانات الأساسية فقط.
     */
    public function storeBasic(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new ContractsBasicImport();

        try {
            Excel::import($import, $request->file('file'));

            $failedValidation = method_exists($import, 'getFailedValidationCount')
                ? (int) $import->getFailedValidationCount()
                : 0;

            $failuresRaw   = $import->failures();
            $failuresCount = is_countable($failuresRaw)
                ? count($failuresRaw)
                : (method_exists($failuresRaw, 'count') ? (int)$failuresRaw->count() : 0);

            $failedValidation = max($failedValidation, $failuresCount);

            $rowsTotal   = $import->getRowCount() + $failedValidation;
            $skippedReal = $import->getSkippedCount() + $failedValidation;
            $inserted    = $import->getInsertedCount();
            $summary = [
                'rows'      => $rowsTotal,
                'inserted'  => $inserted,
                'updated'   => 0,
                'unchanged' => 0,
                'skipped'   => $skippedReal,
                'changed'   => $inserted,
            ];

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

            $customSimple = (array) $import->getFailuresSimple();
            $failuresSimple = array_values(array_merge($traitSimple, $customSimple));

            session()->forget([
                'contracts_basic_import.summary',
                'contracts_basic_import.failures_simple',
                'contracts_basic_import.errors_simple',
            ]);
            session()->put('contracts_basic_import.summary',        $summary);
            session()->put('contracts_basic_import.failures_simple', $failuresSimple);
            session()->put('contracts_basic_import.errors_simple',  (array) $import->getErrorsSimple());
            session()->save();

            return redirect()->route('contracts.import.basic.form')
                ->with('success', "تم حفظ فعليًا: {$inserted} — إجمالي: {$rowsTotal}، متخطّى: {$skippedReal}")
                ->with('summary', $summary)
                ->with('import_basic_just_done', true);

        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('contracts.import.basic.form')
                ->withErrors(['file' => 'تعذّر الاستيراد: ' . $e->getMessage()]);
        }
    }

    /**
     * تنفيذ استيراد مستثمري العقود.
     */
    public function storeInvestors(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new ContractInvestorsImport();

        try {
            Excel::import($import, $request->file('file'));

            $failedValidation = method_exists($import, 'getFailedValidationCount')
                ? (int) $import->getFailedValidationCount()
                : 0;

            $failuresRaw   = $import->failures();
            $failuresCount = is_countable($failuresRaw)
                ? count($failuresRaw)
                : (method_exists($failuresRaw, 'count') ? (int)$failuresRaw->count() : 0);

            $failedValidation = max($failedValidation, $failuresCount);

            $rowsTotal   = $import->getRowCount() + $failedValidation;
            $skippedReal = $import->getSkippedCount() + $failedValidation;
            $updated     = $import->getUpdatedCount();
            $summary = [
                'rows'      => $rowsTotal,
                'inserted'  => 0,
                'updated'   => $updated,
                'unchanged' => 0,
                'skipped'   => $skippedReal,
                'changed'   => $updated,
            ];

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

            $customSimple = (array) $import->getFailuresSimple();
            $failuresSimple = array_values(array_merge($traitSimple, $customSimple));

            session()->forget([
                'contracts_investors_import.summary',
                'contracts_investors_import.failures_simple',
                'contracts_investors_import.errors_simple',
            ]);
            session()->put('contracts_investors_import.summary',        $summary);
            session()->put('contracts_investors_import.failures_simple', $failuresSimple);
            session()->put('contracts_investors_import.errors_simple',  (array) $import->getErrorsSimple());
            session()->save();

            return redirect()->route('contracts.import.investors.form')
                ->with('success', "تم تحديث مستثمري {$updated} عقد — إجمالي: {$rowsTotal}، متخطى: {$skippedReal}")
                ->with('summary', $summary)
                ->with('import_investors_just_done', true);

        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('contracts.import.investors.form')
                ->withErrors(['file' => 'تعذّر الاستيراد: ' . $e->getMessage()]);
        }
    }


    /**
     * تنفيذ استيراد سدادات العقود.
     */
    public function storePayments(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new ContractPaymentsImport();

        try {
            Excel::import($import, $request->file('file'));

            $failedValidation = method_exists($import, 'getFailedValidationCount')
                ? (int) $import->getFailedValidationCount()
                : 0;

            $failuresRaw   = $import->failures();
            $failuresCount = is_countable($failuresRaw)
                ? count($failuresRaw)
                : (method_exists($failuresRaw, 'count') ? (int)$failuresRaw->count() : 0);

            $failedValidation = max($failedValidation, $failuresCount);

            $rowsTotal   = $import->getRowCount() + $failedValidation;
            $skippedReal = $import->getSkippedCount() + $failedValidation;
            $inserted    = $import->getInsertedCount();
            $summary = [
                'rows'      => $rowsTotal,
                'inserted'  => $inserted,
                'updated'   => 0,
                'unchanged' => 0,
                'skipped'   => $skippedReal,
                'changed'   => $inserted,
            ];

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

            $customSimple = (array) $import->getFailuresSimple();
            $failuresSimple = array_values(array_merge($traitSimple, $customSimple));

            session()->forget([
                'contracts_payments_import.summary',
                'contracts_payments_import.failures_simple',
                'contracts_payments_import.errors_simple',
            ]);
            session()->put('contracts_payments_import.summary',        $summary);
            session()->put('contracts_payments_import.failures_simple', $failuresSimple);
            session()->put('contracts_payments_import.errors_simple',  (array) $import->getErrorsSimple());
            session()->save();

            return redirect()->route('contracts.import.payments.form')
                ->with('success', "تم تسجيل {$inserted} سداد — إجمالي الصفوف: {$rowsTotal}، متخطى: {$skippedReal}")
                ->with('summary', $summary)
                ->with('import_payments_just_done', true);

        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('contracts.import.payments.form')
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
