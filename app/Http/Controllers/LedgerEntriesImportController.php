<?php

namespace App\Http\Controllers;

use App\Exports\LedgerEntriesFailuresFixExport;
use App\Exports\LedgerEntriesTemplateExport;
use App\Imports\LedgerEntriesImport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class LedgerEntriesImportController extends Controller
{
    public function create()
    {
        return view('ledger.import');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [], [
            'file' => 'الملف',
        ]);

        $import = new LedgerEntriesImport();

        try {
            Excel::import($import, $request->file('file'));

            // أرقام أساسية من الإنبوتر
            $rowsModel  = (int) $import->getRowCount();
            $inserted   = (int) $import->getInsertedCount();
            $skipped    = (int) $import->getSkippedCount();

            // Failures من WithValidation + SkipsFailures (لو الإنبوتر بيستخدمهم)
            $failuresRaw   = $import->failures();
            $failuresCount = $failuresRaw instanceof Collection
                ? $failuresRaw->count()
                : (is_countable($failuresRaw) ? count($failuresRaw) : 0);

            // إجمالي الصفوف = اللي وصلت لـ model() + اللي وقفت في الفاليديشن
            $rowsTotal = $rowsModel + $failuresCount;

            // بنبسّط الفيليرز للعرض في الجدول
            $iter = ($failuresRaw instanceof Collection) ? $failuresRaw : collect($failuresRaw);
            $failuresSimple = $iter->map(function ($f) {
                if (is_object($f) && method_exists($f, 'row')) {
                    $attr = method_exists($f, 'attribute') ? $f->attribute() : '';
                    return [
                        'row'       => (int) $f->row(),
                        'attribute' => is_array($attr) ? implode(',', $attr) : (string) $attr,
                        'messages'  => implode(' | ', (array) (method_exists($f, 'errors') ? $f->errors() : [])),
                        'values'    => method_exists($f, 'values') ? (array) $f->values() : [],
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

            // خزّن للـ blade (نفس فكرة guarantors/Contracts)
            session()->forget([
                'ledger_import.failures_simple',
                'ledger_import.summary',
            ]);
            session()->put('ledger_import.failures_simple', $failuresSimple);
            session()->put('ledger_import.summary', [
                'rows'     => $rowsTotal,
                'inserted' => $inserted,
                'skipped'  => $skipped + $failuresCount,
                'changed'  => $inserted, // مفيش update في الاستيراد ده افتراضاً
            ]);
            session()->save();

            return back()
                ->with('success', "تم الاستيراد: محفوظ {$inserted} / إجمالي صفوف {$rowsTotal} / متخطّي " . ($skipped + $failuresCount))
                ->with('summary', session('ledger_import.summary'))
                ->with('failures', $failuresRaw)
                ->with('failures_simple', $failuresSimple)
                ->with('errors_simple', collect($import->errors() ?? [])->map(fn($e) =>
                    is_object($e) && method_exists($e, 'getMessage') ? (string)$e->getMessage() : (string)$e
                )->all());

        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['file' => 'تعذّر الاستيراد: ' . $e->getMessage()]);
        }
    }

    // تنزيل تمبليت الأعمدة
    public function template()
    {
        return Excel::download(new LedgerEntriesTemplateExport(), 'ledger_import_template.xlsx');
    }

    // تنزيل ملف لتصحيح الصفوف الفاشلة (CSV سريع)
    public function exportFailuresFix()
{
    $failures = session('ledger_import.failures_simple', []);

    if (empty($failures) || (is_countable($failures) && count($failures) === 0)) {
        return redirect()->route('ledger.import.form')
            ->with('info', 'لا توجد أخطاء لتوليد ملف التصحيح.');
    }

    if ($failures instanceof Collection) {
        $failures = $failures->all();
    }

    return Excel::download(new LedgerEntriesFailuresFixExport($failures), 'ledger_entries_to_fix.xlsx');
}

}
