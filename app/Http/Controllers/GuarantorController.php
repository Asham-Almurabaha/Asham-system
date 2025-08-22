<?php

namespace App\Http\Controllers;

use App\Models\Guarantor;
use App\Models\Nationality;
use App\Models\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Validation\Rule;

class GuarantorController extends Controller
{
    public function index(Request $request)
    {
        // ====== الاستعلام الأساسي ======
        $query = Guarantor::query();

        // فلتر دقيق بالـ ID القادم من الـ dropdown
        $guarantorId = $request->input('guarantor_id');
        if ($guarantorId !== null && $guarantorId !== '' && $guarantorId !== '_none') {
            // التحقق سريعًا لتفادي إدخالات غير متوقعة
            if (is_numeric($guarantorId)) {
                $query->where('guarantors.id', (int) $guarantorId);
            } else {
                // قيمة غير صالحة => لا نتائج
                $query->whereRaw('1=0');
            }
        } else {
            // فلاتر النص التقليدية عند عدم اختيار ID
            $query
                ->when($request->filled('q'),
                    fn($q) => $q->where('name', 'like', '%' . trim($request->q) . '%'))
                ->when($request->filled('national_id') && Schema::hasColumn('guarantors','national_id'),
                    fn($q) => $q->where('national_id', 'like', '%' . trim($request->national_id) . '%'))
                ->when($request->filled('phone'),
                    fn($q) => $q->where('phone', 'like', '%' . trim($request->phone) . '%'));
        }

        $guarantors = $query->latest()->paginate(10)->withQueryString();

        // أسماء الكفلاء للـ dropdown
        $guarantorNameOptions = Guarantor::orderBy('name')->get(['id','name']);

        // ====== كروت عامة ======
        $guarantorsTotalAll = Guarantor::count();

        // تعريف الحالات المنتهية
        $endedStatusNames = ['منتهي','منتهى','سداد مبكر','سداد مُبكر','سداد مبكّر','Completed','Early Settlement'];

        // IDs للحالات إن وُجد جدول حالة العقود
        $endedStatusIds = [];
        if (class_exists(\App\Models\ContractStatus::class)) {
            $endedStatusIds = \App\Models\ContractStatus::query()
                ->whereIn('name', $endedStatusNames)
                ->pluck('id')
                ->all();
        }

        // تحديد أعمدة الحالة المتوفرة في جدول العقود
        $statusIdCol = null;
        foreach (['status_id', 'contract_status_id', 'state_id'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusIdCol = $col; break; }
        }
        $statusNameCol = null;
        foreach (['status', 'state'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusNameCol = $col; break; }
        }

        // كلوجر يطبق شرط "عقد نشط"
        $applyActiveContractWhere = function ($c) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
            if ($statusIdCol && !empty($endedStatusIds)) {
                $c->whereNotIn("contracts.$statusIdCol", $endedStatusIds);
            } elseif ($statusNameCol) {
                $c->whereNotIn("contracts.$statusNameCol", $endedStatusNames);
            } elseif (Schema::hasColumn('contracts', 'is_closed')) {
                $c->where('contracts.is_closed', 0);
            } elseif (Schema::hasColumn('contracts', 'closed_at')) {
                $c->whereNull('contracts.closed_at');
            } else {
                // لو ماعندناش معلومة حالة الإغلاق، اعتبر وجود أي عقد = نشط
                $c->whereRaw('1=1');
            }
        };

        // حساب الكفلاء النشطين بحسب شكل الربط
        if (method_exists(Guarantor::class, 'contracts')) {
            // علاقة contracts معرّفة على الموديل
            $activeGuarantorsTotalAll = Guarantor::whereHas('contracts', function ($c) use ($applyActiveContractWhere) {
                $applyActiveContractWhere($c);
            })->count();

        } elseif (Schema::hasColumn('contracts', 'guarantor_id')) {
            // ربط مباشر عبر عمود guarantor_id
            $activeGuarantorsTotalAll = Guarantor::whereExists(function ($q) use ($applyActiveContractWhere) {
                $q->select(DB::raw(1))
                ->from('contracts')
                ->whereColumn('contracts.guarantor_id', 'guarantors.id');
                $applyActiveContractWhere($q);
            })->count();

        } elseif (Schema::hasTable('contract_guarantors') || Schema::hasTable('contract_guarantor')) {
            // ربط Pivot
            $pivot = Schema::hasTable('contract_guarantors') ? 'contract_guarantors' : 'contract_guarantor';
            $activeGuarantorsTotalAll = Guarantor::whereExists(function ($q) use ($pivot, $applyActiveContractWhere) {
                $q->select(DB::raw(1))
                ->from("$pivot as cg")
                ->whereColumn('cg.guarantor_id', 'guarantors.id')
                ->whereExists(function ($qq) use ($applyActiveContractWhere) {
                    $qq->select(DB::raw(1))
                        ->from('contracts')
                        ->whereColumn('contracts.id', 'cg.contract_id');
                    $applyActiveContractWhere($qq);
                });
            })->count();

        } else {
            // شكل الربط غير معروف
            $activeGuarantorsTotalAll = 0;
        }

        // أرقام إضافية (الجدد)
        $newGuarantorsThisMonthAll = Guarantor::whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $newGuarantorsThisWeekAll  = Guarantor::whereBetween('created_at', [now()->startOfWeek(),  now()])->count();

        return view('guarantors.index', compact(
            'guarantors',
            'guarantorsTotalAll',
            'activeGuarantorsTotalAll',
            'newGuarantorsThisMonthAll',
            'newGuarantorsThisWeekAll',
            'guarantorNameOptions'
        ));
    }

    public function create()
    {
        $nationalities = Nationality::all();
        $titles = Title::all();
        return view('guarantors.create', compact('nationalities', 'titles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:guarantors,name,',
            'national_id' => 'nullable|digits:10|regex:/^[12]\d{9}$/|unique:guarantors,national_id,',
            'phone' => 'nullable|regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/|unique:guarantors,phone,',
            'email' => 'nullable|email|max:255',
            'title_id' => 'nullable|exists:titles,id',
            'address' => 'nullable|string',
            'nationality_id' => 'nullable|exists:nationalities,id',
            'id_card_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('id_card_image')) {
            $validated['id_card_image'] = $request->file('id_card_image')->store('guarantor_id_cards', 'public');
        }

        Guarantor::create($validated);

        return redirect()->route('guarantors.index')->with('success', 'تم إضافة الكفيل بنجاح');
    }

    public function show(Guarantor $guarantor)
    {
        return view('guarantors.show', compact('guarantor'));
    }

    public function edit(Guarantor $guarantor)
    {
        $nationalities = Nationality::all();
        $titles = Title::all();
        return view('guarantors.edit', compact('guarantor', 'nationalities', 'titles'));
    }

    public function update(Request $request, Guarantor $guarantor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:guarantors,name,' . $guarantor->id,
            'national_id' => 'nullable|digits:10|regex:/^[12]\d{9}$/|unique:guarantors,national_id,' . $guarantor->id,
            'phone' => 'nullable|regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/|unique:guarantors,phone,' . $guarantor->id,
            'email' => 'nullable|email|max:255',
            'title_id' => 'nullable|exists:titles,id',
            'address' => 'nullable|string',
            'nationality_id' => 'nullable|exists:nationalities,id',
            'id_card_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('id_card_image')) {
            // حذف الصورة القديمة إذا موجودة
            if ($guarantor->id_card_image) {
                Storage::disk('public')->delete($guarantor->id_card_image);
            }
            $validated['id_card_image'] = $request->file('id_card_image')->store('guarantor_id_cards', 'public');
        }

        $guarantor->update($validated);

        return redirect()->route('guarantors.index')->with('success', 'تم تحديث بيانات الكفيل بنجاح');
    }

    public function destroy(Guarantor $guarantor)
    {
        if ($guarantor->id_card_image) {
            Storage::disk('public')->delete($guarantor->id_card_image);
        }
        $guarantor->delete();

        return redirect()->route('guarantors.index')->with('success', 'تم حذف الكفيل بنجاح');
    }
}
