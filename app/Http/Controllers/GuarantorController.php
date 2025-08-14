<?php

namespace App\Http\Controllers;

use App\Models\Guarantor;
use App\Models\Nationality;
use App\Models\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuarantorController extends Controller
{
    public function index()
    {
        $guarantors = Guarantor::latest()->paginate(15);
        return view('guarantors.index', compact('guarantors'));
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
            'name' => 'required|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'nationality_id' => 'nullable|exists:nationalities,id',
            'title_id' => 'nullable|exists:titles,id',
            'id_card_image' => 'nullable|image|max:2048', // 2MB max
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
            'name' => 'required|string|max:255',
            'national_id' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'nationality_id' => 'nullable|exists:nationalities,id',
            'title_id' => 'nullable|exists:titles,id',
            'id_card_image' => 'nullable|image|max:2048',
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
