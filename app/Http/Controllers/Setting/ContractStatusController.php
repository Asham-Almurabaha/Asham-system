<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\ContractStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

class ContractStatusController extends Controller
{
    /** الحالات الأساسية الممنوع تعديلها/حذفها */
    private const PROTECTED_NAMES = ['بدون مستثمر', 'معلق', 'جديد', 'متاخر', 'متعثر', 'منتهي', 'سداد مبكر'];

    public function index()
    {
        $statuses = ContractStatus::orderBy('id')->get();
        return view('contract_statuses.index', compact('statuses'));
    }

    public function create()
    {
        return view('contract_statuses.create');
    }

    public function store(Request $request)
    {
        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => ['required', 'max:255', Rule::unique('contract_statuses', 'name')],
        ], [], ['name' => 'الاسم']);

        try {
            $data = ['name' => $name];

            // لو عندك عمود is_protected خليه افتراضيًا false
            if (Schema::hasColumn('contract_statuses', 'is_protected')) {
                $data['is_protected'] = false;
            }

            ContractStatus::create($data);

            return redirect()->route('contract_statuses.index')
                ->with('success', 'تم إضافة حالة العقد بنجاح.');
        } catch (Throwable $e) {
            report($e);
            return back()->withInput()
                ->withErrors(['general' => 'تعذّر الحفظ. حاول مرة أخرى.']);
        }
    }

    public function edit(ContractStatus $contract_status)
    {
        if ($this->isProtected($contract_status)) {
            return redirect()->route('contract_statuses.index')
                ->withErrors(['general' => 'هذه الحالة أساسية ولا يمكن تعديلها.']);
        }

        return view('contract_statuses.edit', compact('contract_status'));
    }

    public function update(Request $request, ContractStatus $contract_status)
    {
        if ($this->isProtected($contract_status)) {
            return redirect()->route('contract_statuses.index')
                ->withErrors(['general' => 'هذه الحالة أساسية ولا يمكن تعديلها.']);
        }

        $name = $this->normalizeName($request->input('name'));
        $request->merge(['name' => $name]);

        $request->validate([
            'name' => [
                'required',
                'max:255',
                Rule::unique('contract_statuses', 'name')->ignore($contract_status->id),
            ],
        ], [], ['name' => 'الاسم']);

        try {
            $contract_status->update(['name' => $name]);

            return redirect()->route('contract_statuses.index')
                ->with('success', 'تم تحديث حالة العقد بنجاح.');
        } catch (Throwable $e) {
            report($e);
            return back()->withInput()
                ->withErrors(['general' => 'تعذّر التحديث. حاول مرة أخرى.']);
        }
    }

    public function destroy(ContractStatus $contract_status)
    {
        if ($this->isProtected($contract_status)) {
            return redirect()->route('contract_statuses.index')
                ->withErrors(['general' => 'هذه الحالة أساسية ولا يمكن حذفها.']);
        }

        try {
            $contract_status->delete();

            return redirect()->route('contract_statuses.index')
                ->with('success', 'تم حذف حالة العقد بنجاح.');
        } catch (Throwable $e) {
            report($e);
            return back()
                ->withErrors(['general' => 'تعذّر الحذف. قد تكون الحالة مستخدمة في سجلات أخرى.']);
        }
    }

    /* ==================== Helpers ==================== */

    /** اعتبر الحالة محمية إذا كان اسمها ضمن القائمة أو is_protected=true (إن وُجد العمود). */
    private function isProtected(ContractStatus $status): bool
    {
        // حماية بالاسم
        $normalizedName = $this->normalizeName($status->name);
        $protectedByName = in_array(
            $normalizedName,
            array_map([$this, 'normalizeName'], self::PROTECTED_NAMES),
            true
        );
        if ($protectedByName) {
            return true;
        }

        // حماية بعمود is_protected (اختياري)
        if (Schema::hasColumn('contract_statuses', 'is_protected')) {
            return (bool) $status->is_protected;
        }

        return false;
    }

    /** توحيد الاسم: trim + دمج المسافات المتعددة لمسافة واحدة. */
    private function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        $name = preg_replace('/\s+/u', ' ', $name) ?: '';
        return $name;
    }
}
