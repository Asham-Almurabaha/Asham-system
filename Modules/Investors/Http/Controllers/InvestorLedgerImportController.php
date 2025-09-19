<?php

namespace Modules\Investors\Http\Controllers;

use App\Exports\LedgerEntriesFailuresFixExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Investors\Exports\InvestorLedgerTemplateExport;
use Modules\Investors\Imports\InvestorLedgerEntriesImport;

class InvestorLedgerImportController extends Controller
{
    public function create()
    {
        return view('investors::ledger.import');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [], [
            'file' => __('investors::investor_ledger_import.File'),
        ]);

        $import = new InvestorLedgerEntriesImport();

        try {
            Excel::import($import, $request->file('file'));

            $rowsModel = (int) $import->getRowCount();
            $inserted  = (int) $import->getInsertedCount();
            $skipped   = (int) $import->getSkippedCount();

            $failuresRaw = $import->failures();
            $failuresCount = $failuresRaw instanceof Collection
                ? $failuresRaw->count()
                : (is_countable($failuresRaw) ? count($failuresRaw) : 0);

            $rowsTotal = $rowsModel + $failuresCount;

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

            session()->forget([
                'investors_ledger_import.failures_simple',
                'investors_ledger_import.summary',
            ]);

            session()->put('investors_ledger_import.failures_simple', $failuresSimple);
            session()->put('investors_ledger_import.summary', [
                'rows'    => $rowsTotal,
                'inserted'=> $inserted,
                'skipped' => $skipped + $failuresCount,
                'changed' => $inserted,
            ]);
            session()->save();

            return back()
                ->with('success', __('investors::investor_ledger_import.Success message', [
                    'inserted' => number_format($inserted),
                    'rows'     => number_format($rowsTotal),
                    'skipped'  => number_format($skipped + $failuresCount),
                ]))
                ->with('summary', session('investors_ledger_import.summary'))
                ->with('failures', $failuresRaw)
                ->with('failures_simple', $failuresSimple)
                ->with('errors_simple', collect($import->errors() ?? [])->map(fn($e) =>
                    is_object($e) && method_exists($e, 'getMessage') ? (string) $e->getMessage() : (string) $e
                )->all());
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['file' => __('investors::investor_ledger_import.Import failed', [
                'message' => $e->getMessage(),
            ])]);
        }
    }

    public function template()
    {
        return Excel::download(new InvestorLedgerTemplateExport(), 'investor_ledger_import_template.xlsx');
    }

    public function exportFailuresFix()
    {
        $failures = session('investors_ledger_import.failures_simple', []);

        if (empty($failures) || (is_countable($failures) && count($failures) === 0)) {
            return redirect()->route('investors.ledger.import.form')
                ->with('info', __('investors::investor_ledger_import.No failures to export'));
        }

        if ($failures instanceof Collection) {
            $failures = $failures->all();
        }

        return Excel::download(new LedgerEntriesFailuresFixExport($failures), 'investor_ledger_entries_to_fix.xlsx');
    }
}
