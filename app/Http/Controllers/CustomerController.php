<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Nationality;
use App\Models\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    // عرض كل العملاء
    public function index()
    {
        $customers = Customer::with(['nationality', 'title'])->latest()->paginate(10);
        return view('customers.index', compact('customers'));
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
