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
        $query = Guarantor::query();

        // ===== بحث باسم الكفيل فقط =====
        $nameQ = trim((string) $request->input('guarantor_q', ''));
        if ($nameQ !== '') {
            $query->where('guarantors.name', 'like', '%' . $nameQ . '%');
        } else {
            // فلاتر إضافية اختيارية
            $query->when($request->filled('national_id') && Schema::hasColumn('guarantors', 'national_id'),
                    fn($q) => $q->where('national_id', 'like', '%'.trim($request->national_id).'%'))
                ->when($request->filled('phone'),
                    fn($q) => $q->where('phone', 'like', '%'.trim($request->phone).'%'));
        }

        // 20 صف في الصفحة
        $guarantors = $query->latest()->paginate(20)->withQueryString();

        // ===== كروت عامة (غير متأثرة بالفلاتر) =====
        $guarantorsTotalAll = Guarantor::count();

        // “النشط” من جدول العقود
        $endedStatusNames = ['منتهي','منتهى','سداد مبكر','سداد مُبكر','سداد مبكّر','Completed','Early Settlement'];
        $endedStatusIds = [];
        if (class_exists(\App\Models\ContractStatus::class)) {
            $endedStatusIds = \App\Models\ContractStatus::query()
                ->whereIn('name', $endedStatusNames)
                ->pluck('id')->all();
        }

        // الأعمدة المحتملة لحالة العقد
        $statusIdCol = null;
        foreach (['contract_status_id','status_id','state_id'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusIdCol = $col; break; }
        }
        $statusNameCol = null;
        foreach (['contract_status','status','state'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusNameCol = $col; break; }
        }

        $applyActiveContractWhere = function ($c) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
            if ($statusIdCol && !empty($endedStatusIds)) {
                $c->whereNotIn($statusIdCol, $endedStatusIds);
            } elseif ($statusNameCol) {
                $c->whereNotIn($statusNameCol, $endedStatusNames);
            } elseif (Schema::hasColumn('contracts', 'is_closed')) {
                $c->where('is_closed', 0);
            } elseif (Schema::hasColumn('contracts', 'closed_at')) {
                $c->whereNull('closed_at');
            } else {
                $c->whereRaw('1=1');
            }
        };

        // نحاول نعد “الكفلاء النشطين” حسب شكل الربط
        if (method_exists(Guarantor::class, 'contracts')) {
            $activeGuarantorsTotalAll = Guarantor::whereHas('contracts', fn($c) => $applyActiveContractWhere($c))->count();
        } elseif (Schema::hasColumn('contracts', 'guarantor_id')) {
            $activeGuarantorsTotalAll = Guarantor::whereExists(function ($q) use ($applyActiveContractWhere) {
                $q->select(DB::raw(1))
                ->from('contracts')
                ->whereColumn('contracts.guarantor_id', 'guarantors.id');
                $applyActiveContractWhere($q);
            })->count();
        } elseif (Schema::hasTable('contract_guarantors') || Schema::hasTable('contract_guarantor')) {
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
            $activeGuarantorsTotalAll = 0;
        }

        $newGuarantorsThisMonthAll = Guarantor::whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $newGuarantorsThisWeekAll  = Guarantor::whereBetween('created_at', [now()->startOfWeek(),  now()])->count();

        return view('guarantors.index', compact(
            'guarantors',
            'guarantorsTotalAll',
            'activeGuarantorsTotalAll',
            'newGuarantorsThisMonthAll',
            'newGuarantorsThisWeekAll'
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
