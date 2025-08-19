<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use App\Models\Nationality;
use App\Models\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class InvestorController extends Controller
{
    public function index(Request $request)
    {
        // ====== الاستعلام الأساسي مع الفلاتر ======
        $query = Investor::query()
            ->with(['nationality','title'])
            ->when($request->filled('q'),           fn($q) => $q->where('name','like','%'.$request->q.'%'))
            ->when($request->filled('national_id'), fn($q) => $q->where('national_id','like','%'.$request->national_id.'%'))
            ->when($request->filled('phone'),       fn($q) => $q->where('phone','like','%'.$request->phone.'%'))
            ->when($request->filled('email'),       fn($q) => $q->where('email','like','%'.$request->email.'%'))
            ->when($request->filled('nationality'), fn($q) => $q->where('nationality_id', $request->nationality))
            ->when($request->filled('title'),       fn($q) => $q->where('title_id', $request->title))
            ->latest();

        $investors = $query->paginate(10)->withQueryString();

        // ====== كروت عامة ======
        $investorsTotalAll = Investor::count();

        // تعريف حالات منتهية/مغلقة لاستبعادها عند حساب "نشط"
        $endedStatusNames = ['منتهي','منتهى','سداد مبكر','سداد مُبكر','سداد مبكّر','Completed','Early Settlement','Closed','Inactive'];

        // IDs للحالات لو فيه جدول statuses
        $endedStatusIds = [];
        if (class_exists(\App\Models\InvestmentStatus::class)) {
            $endedStatusIds = \App\Models\InvestmentStatus::whereIn('name',$endedStatusNames)->pluck('id')->all();
        } elseif (class_exists(\App\Models\ContractStatus::class)) {
            $endedStatusIds = \App\Models\ContractStatus::whereIn('name',$endedStatusNames)->pluck('id')->all();
        }

        // حساب النشطين:
        // 1) من جدول investments إن وُجد
        if (Schema::hasTable('investments') && Schema::hasColumn('investments','investor_id')) {
            // أعمدة محتملة للحالة
            $statusIdCol = null; foreach (['status_id','investment_status_id','state_id'] as $c){ if(Schema::hasColumn('investments',$c)){ $statusIdCol=$c; break; } }
            $statusNmCol = null; foreach (['status','state'] as $c){ if(Schema::hasColumn('investments',$c)){ $statusNmCol=$c; break; } }

            $activeInvestorsTotalAll = Investor::whereExists(function($sub) use($statusIdCol,$statusNmCol,$endedStatusIds,$endedStatusNames){
                $sub->from('investments')
                    ->whereColumn('investors.id','investments.investor_id');

                if ($statusIdCol && !empty($endedStatusIds)) {
                    $sub->whereNotIn($statusIdCol, $endedStatusIds);
                } elseif ($statusNmCol) {
                    $sub->whereNotIn($statusNmCol, $endedStatusNames);
                } elseif (Schema::hasColumn('investments','is_closed')) {
                    $sub->where('is_closed', 0);
                } elseif (Schema::hasColumn('investments','closed_at')) {
                    $sub->whereNull('closed_at');
                } else {
                    $sub->whereRaw('1=1'); // أي استثمار يعتبر نشط
                }
            })->count();

        // 2) بدائل منطقية عند عدم وجود investments:
        } else {
            // اعتبره نشط لو عنده صورة عقد أو نسبة مكتب > 0 (كبديل معقول)
            $activeInvestorsTotalAll = Investor::query()
                ->where(function($q){
                    if (Schema::hasColumn('investors','contract_image')) {
                        $q->whereNotNull('contract_image')->where('contract_image','!=','');
                    }
                    if (Schema::hasColumn('investors','office_share_percentage')) {
                        $q->orWhere('office_share_percentage','>',0);
                    }
                })
                ->count();
        }

        // أرقام إضافية
        $newInvestorsThisMonthAll = Investor::whereBetween('created_at',[now()->startOfMonth(), now()])->count();
        $newInvestorsThisWeekAll  = Investor::whereBetween('created_at',[now()->startOfWeek(),  now()])->count();

        // للفلاتر
        $nationalities = class_exists(Nationality::class)
            ? Nationality::select('id','name')->orderBy('name')->get()
            : collect();
        $titles = class_exists(Title::class)
            ? Title::select('id','name')->orderBy('name')->get()
            : collect();

        return view('investors.index', compact(
            'investors',
            'investorsTotalAll',
            'activeInvestorsTotalAll',
            'newInvestorsThisMonthAll',
            'newInvestorsThisWeekAll',
            'nationalities',
            'titles'
        ));
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
