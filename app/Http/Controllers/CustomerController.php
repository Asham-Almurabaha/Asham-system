<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Nationality;
use App\Models\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    // عرض كل العملاء
    public function index(Request $request)
    {
        // ====== الاستعلام الأساسي ======
        $query = Customer::query()
            ->with(['nationality','title'])
            ->when($request->filled('q'),            fn($q) => $q->where('name','like','%'.$request->q.'%'))
            ->when($request->filled('national_id'),  fn($q) => $q->where('national_id','like','%'.$request->national_id.'%'))
            ->when($request->filled('phone'),        fn($q) => $q->where('phone','like','%'.$request->phone.'%'))
            ->when($request->filled('email'),        fn($q) => $q->where('email','like','%'.$request->email.'%'))
            ->when($request->filled('nationality'),  fn($q) => $q->where('nationality_id', $request->nationality))
            ->when($request->filled('title'),        fn($q) => $q->where('title_id', $request->title))
            ->latest();

        $customers = $query->paginate(10)->withQueryString();

        // ====== كروت عامة ======
        $customersTotalAll = Customer::count();

        // الحالات غير النشطة
        $endedStatusNames = ['منتهي','منتهى','سداد مبكر','سداد مُبكر','سداد مبكّر','Completed','Early Settlement'];

        $endedStatusIds = [];
        if (class_exists(\App\Models\ContractStatus::class)) {
            $endedStatusIds = \App\Models\ContractStatus::query()
                ->whereIn('name', $endedStatusNames)
                ->pluck('id')
                ->all();
        }

        $statusIdCol = null;
        foreach (['status_id', 'contract_status_id', 'state_id'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusIdCol = $col; break; }
        }
        $statusNameCol = null;
        foreach (['status', 'state'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusNameCol = $col; break; }
        }

        $activeCustomersTotalAll = Customer::query()
            ->whereHas('contracts', function ($c) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
                if ($statusIdCol && !empty($endedStatusIds)) {
                    $c->whereNotIn($statusIdCol, $endedStatusIds);
                } elseif ($statusNameCol) {
                    $c->whereNotIn($statusNameCol, $endedStatusNames);
                } elseif (Schema::hasColumn('contracts', 'is_closed')) {
                    $c->where('is_closed', 0);
                } elseif (Schema::hasColumn('contracts', 'closed_at')) {
                    $c->whereNull('closed_at');
                } else {
                    $c->whereRaw('1 = 1');
                }
            })
            ->count();

        // أرقام إضافية للإبقاء
        $newCustomersThisMonthAll = Customer::whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $newCustomersThisWeekAll  = Customer::whereBetween('created_at', [now()->startOfWeek(),  now()])->count();

        // قوائم للفلاتر
        $nationalities = class_exists(Nationality::class)
            ? Nationality::select('id','name')->orderBy('name')->get()
            : collect();
        $titles = class_exists(Title::class)
            ? Title::select('id','name')->orderBy('name')->get()
            : collect();

        return view('customers.index', compact(
            'customers',
            'customersTotalAll',
            'activeCustomersTotalAll',
            'newCustomersThisMonthAll',
            'newCustomersThisWeekAll',
            'nationalities',
            'titles'
        ));
    }
    // صفحة إنشاء عميل جديد
    public function create()
    {
        $titles = Title::all();
        $nationalities = Nationality::all();
        return view('customers.create', compact('titles', 'nationalities'));
    }

    // حفظ عميل جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'national_id'   => 'nullable|string|max:50',
            'title_id'      => 'nullable|exists:titles,id',
            'phone'         => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:255',
            'address'       => 'nullable|string',
            'nationality_id'=> 'nullable|exists:nationalities,id',
            'id_card_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes'         => 'nullable|string',
        ]);

        // رفع صورة الهوية
        if ($request->hasFile('id_card_image')) {
            $validated['id_card_image'] = $request->file('id_card_image')->store('customer_id_cards', 'public');
        }

        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'تم إضافة العميل بنجاح.');
    }

    // عرض تفاصيل عميل
    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    // صفحة تعديل عميل
    public function edit(Customer $customer)
    {
        $titles = Title::all();
        $nationalities = Nationality::all();
        return view('customers.edit', compact('customer', 'titles', 'nationalities'));
    }

    // تحديث بيانات عميل
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'national_id'   => 'nullable|string|max:50',
            'title_id'      => 'nullable|exists:titles,id',
            'phone'         => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:255',
            'address'       => 'nullable|string',
            'nationality_id'=> 'nullable|exists:nationalities,id',
            'id_card_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'notes'         => 'nullable|string',
        ]);

        // رفع صورة الهوية الجديدة وحذف القديمة
        if ($request->hasFile('id_card_image')) {
            if ($customer->id_card_image && Storage::disk('public')->exists($customer->id_card_image)) {
                Storage::disk('public')->delete($customer->id_card_image);
            }
            $validated['id_card_image'] = $request->file('id_card_image')->store('customer_id_cards', 'public');
        }

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'تم تعديل بيانات العميل بنجاح.');
    }

    // حذف عميل
    public function destroy(Customer $customer)
    {
        if ($customer->id_card_image && Storage::disk('public')->exists($customer->id_card_image)) {
            Storage::disk('public')->delete($customer->id_card_image);
        }

        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'تم حذف العميل بنجاح.');
    }
}
