<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use App\Models\Nationality;
use App\Models\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvestorController extends Controller
{
    public function index()
    {
        $investors = Investor::latest()->paginate(15);
        return view('investors.index', compact('investors'));
    }

    public function create()
    {
        $nationalities = Nationality::all();
        $titles = Title::all();
        return view('investors.create', compact('nationalities', 'titles'));
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
            'id_card_image' => 'nullable|image|max:2048',
            'contract_image' => 'nullable|image|max:2048',
            'office_share_percentage' => 'required|numeric|between:0,100',
        ]);

        if ($request->hasFile('id_card_image')) {
            $validated['id_card_image'] = $request->file('id_card_image')->store('investor/investor_id_cards', 'public');
        }

        if ($request->hasFile('contract_image')) {
            $validated['contract_image'] = $request->file('contract_image')->store('investor/investor_contracts', 'public');
        }

        Investor::create($validated);

        return redirect()->route('investors.index')->with('success', 'تم إضافة المستثمر بنجاح');
    }

    public function show(Investor $investor)
    {
        return view('investors.show', compact('investor'));
    }

    public function edit(Investor $investor)
    {
        $nationalities = Nationality::all();
        $titles = Title::all();
        return view('investors.edit', compact('investor', 'nationalities', 'titles'));
    }

    public function update(Request $request, Investor $investor)
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
            'contract_image' => 'nullable|image|max:2048',
            'office_share_percentage' => 'required|numeric|between:0,100',
        ]);

        if ($request->hasFile('id_card_image')) {
            if ($investor->id_card_image) {
                Storage::disk('public')->delete($investor->id_card_image);
            }
            $validated['id_card_image'] = $request->file('id_card_image')->store('investor/investor_id_cards', 'public');
        }

        if ($request->hasFile('contract_image')) {
            if ($investor->contract_image) {
                Storage::disk('public')->delete($investor->contract_image);
            }
            $validated['contract_image'] = $request->file('contract_image')->store('investor/investor_contracts', 'public');
        }

        $investor->update($validated);

        return redirect()->route('investors.index')->with('success', 'تم تحديث بيانات المستثمر بنجاح');
    }

    public function destroy(Investor $investor)
    {
        if ($investor->id_card_image) {
            Storage::disk('public')->delete($investor->id_card_image);
        }
        if ($investor->contract_image) {
            Storage::disk('public')->delete($investor->contract_image);
        }

        $investor->delete();

        return redirect()->route('investors.index')->with('success', 'تم حذف المستثمر بنجاح');
    }
}
