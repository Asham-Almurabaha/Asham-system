<?php

namespace Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Customers\Entities\Customer;
use Modules\Customers\Exports\CustomersFailuresFixExport;
use Modules\Customers\Exports\CustomersSkippedExport;
use Modules\Customers\Exports\CustomersTemplateExport;
use Modules\Customers\Imports\CustomersImport;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class CustomerImportController extends Controller
{
    public function create()
    {
        // ما نمسحش السيشن هنا علشان نعرض نتائج آخر استيراد بعد الـ redirect.
        return view('customers::import');
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

            session()->put('customers_import.summary', $summary);
            $this->storePendingUpdates(is_array($pendingUpdates) ? $pendingUpdates : []);

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

            $savedMessage = $inserted > 0
                ? 'تم حفظ '.$inserted.' عميل جديد.'
                : 'لا توجد إضافات جديدة.';

            if ($pending > 0) {
                $savedMessage .= ' توجد '.$pending.' تعديلات بانتظار التأكيد.';
            }

            return redirect()
                ->route('customers.import.form') // تأكّد من اسم الروت
                ->with('success', $savedMessage)
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

        if (class_exists(\Modules\Customers\Exports\CustomersIssuesExport::class)) {
            // ملف بشيتين: Failures + Skipped
            return Excel::download(
                new \Modules\Customers\Exports\CustomersIssuesExport(
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

    public function confirmPending(Request $request, string $token)
    {
        $pending = $this->getPendingUpdatesFromSession();

        if (!isset($pending[$token])) {
            return redirect()->route('customers.import.form')
                ->with('info', 'هذا التعديل غير موجود أو تمت معالجته مسبقًا.');
        }

        $entry = $pending[$token];
        unset($pending[$token]);

        $customerId = $entry['customer_id'] ?? null;
        $customer   = $customerId ? Customer::find($customerId) : null;

        if (!$customer) {
            $this->storePendingUpdates($pending);
            $this->syncPendingSummary($pending, false);

            return redirect()->route('customers.import.form')
                ->with('info', 'العميل غير موجود، أزيل التعديل من قائمة الانتظار.');
        }

        $updates = $entry['updates'] ?? [];
        if (!is_array($updates) || empty($updates)) {
            $this->storePendingUpdates($pending);
            $this->syncPendingSummary($pending, false);

            return redirect()->route('customers.import.form')
                ->with('info', 'لا توجد قيم قابلة للتحديث لهذا التعديل.');
        }

        $fillable = array_flip($customer->getFillable());
        $apply     = [];
        foreach ($updates as $field => $value) {
            if (isset($fillable[$field])) {
                $apply[$field] = $value;
            }
        }

        if (empty($apply)) {
            $this->storePendingUpdates($pending);
            $this->syncPendingSummary($pending, false);

            return redirect()->route('customers.import.form')
                ->with('info', 'الحقول المقترحة غير مسموح بتعديلها، تمت إزالتها من القائمة.');
        }

        $customer->fill($apply);

        $applied = false;
        if ($customer->isDirty()) {
            $customer->save();
            $applied = $customer->wasChanged();
        }

        $this->storePendingUpdates($pending);
        $this->syncPendingSummary($pending, $applied);

        if ($applied) {
            return redirect()->route('customers.import.form')
                ->with('success', 'تم تأكيد تعديل العميل '.$customer->name.' بنجاح.');
        }

        return redirect()->route('customers.import.form')
            ->with('info', 'لم يتم تطبيق أي تغييرات لأن البيانات متطابقة سلفًا.');
    }

    public function ignorePending(Request $request, string $token)
    {
        $pending = $this->getPendingUpdatesFromSession();

        if (!isset($pending[$token])) {
            return redirect()->route('customers.import.form')
                ->with('info', 'هذا التعديل غير موجود أو تمت معالجته مسبقًا.');
        }

        $entry = $pending[$token];
        unset($pending[$token]);

        $this->storePendingUpdates($pending);
        $this->syncPendingSummary($pending, false);

        $name = $entry['customer_name'] ?? 'العميل';

        return redirect()->route('customers.import.form')
            ->with('info', 'تم تجاهل التعديل للعميل '.$name.'.');
    }

    public function storePendingAsNew(Request $request, string $token)
    {
        $pending = $this->getPendingUpdatesFromSession();

        if (!isset($pending[$token])) {
            return redirect()->route('customers.import.form')
                ->with('info', 'هذا التعديل غير موجود أو تمت معالجته مسبقًا.');
        }

        $entry = $pending[$token];

        $payload = $entry['payload'] ?? [];
        if (!is_array($payload) || empty($payload)) {
            $payload = $entry['updates'] ?? [];

            $existingId = $entry['customer_id'] ?? null;
            $existing   = $existingId ? Customer::find($existingId) : null;

            if ($existing) {
                foreach ($existing->getFillable() as $field) {
                    if (!array_key_exists($field, $payload)) {
                        $payload[$field] = $existing->getAttribute($field);
                    }
                }
            }
        }

        if (!is_array($payload) || empty($payload)) {
            return redirect()->route('customers.import.form')
                ->with('info', 'لا توجد بيانات كافية لإنشاء سجل جديد من هذا التعديل.');
        }

        $customerPrototype = new Customer();
        $fillableMap       = array_flip($customerPrototype->getFillable());
        $data              = [];

        foreach ($payload as $field => $value) {
            if (isset($fillableMap[$field])) {
                $data[$field] = $value;
            }
        }

        $nameValue = $data['name'] ?? null;
        if ($nameValue === null || trim((string) $nameValue) === '') {
            return redirect()->route('customers.import.form')
                ->with('info', 'الاسم مطلوب لإنشاء عميل جديد من هذا التعديل.');
        }

        try {
            $newCustomer = Customer::create($data);
        } catch (\Throwable $e) {
            return redirect()->route('customers.import.form')
                ->with('error', 'تعذّر إنشاء عميل جديد: '.$e->getMessage());
        }

        unset($pending[$token]);
        $this->storePendingUpdates($pending);
        $this->syncPendingSummary($pending, false, true);

        return redirect()->route('customers.import.form')
            ->with('success', 'تم حفظ عميل جديد باسم '.$newCustomer->name.'.');
    }

    private function getPendingUpdatesFromSession(): array
    {
        $pending = session('customers_import.pending_updates', []);

        if ($pending instanceof Collection) {
            return $pending->toArray();
        }

        return is_array($pending) ? $pending : [];
    }

    private function storePendingUpdates(array $pending): void
    {
        if (empty($pending)) {
            session()->forget('customers_import.pending_updates');
            return;
        }

        session(['customers_import.pending_updates' => $pending]);
    }

    private function syncPendingSummary(array $pending, bool $appliedUpdate, bool $inserted = false): void
    {
        $summary = session('customers_import.summary', []);

        $summary['pending'] = is_countable($pending) ? count($pending) : 0;

        if ($appliedUpdate) {
            $summary['updated'] = (int) ($summary['updated'] ?? 0) + 1;
            $summary['changed'] = (int) ($summary['changed'] ?? 0) + 1;
        }

        if ($inserted) {
            $summary['inserted'] = (int) ($summary['inserted'] ?? 0) + 1;
            $summary['changed']  = (int) ($summary['changed'] ?? 0) + 1;
        }

        session(['customers_import.summary' => $summary]);
    }
}
