<?php

namespace App\Http\Controllers;

use App\Models\ContractStatus;
use App\Models\Investor;
use App\Models\Nationality;
use App\Models\Title;
use App\Services\InstallmentsMonthlyService;
use App\Services\InvestorDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Validation\Rule;


class InvestorController extends Controller
{
     public function index(Request $request)
    {
        // ====== الاستعلام الأساسي ======
        $query = Investor::query();

        // فلتر دقيق بالـ ID القادم من الـ dropdown (إن وُجد)
        $invId = $request->input('investor_id');
        if ($invId !== null && $invId !== '' && $invId !== '_none') {
            // حماية: نتأكد أنها رقمية
            if (is_numeric($invId)) {
                $query->whereKey((int) $invId);
            } else {
                // قيمة غير صالحة => رجّع لا شيء
                $query->whereRaw('1=0');
            }
        } else {
            // باقي الفلاتر النصية القديمة كما هي
            $query
                ->when($request->filled('q'),
                    fn($q) => $q->where('name', 'like', '%'.trim($request->q).'%'))
                ->when($request->filled('national_id') && Schema::hasColumn('investors','national_id'),
                    fn($q) => $q->where('national_id', 'like', '%'.trim($request->national_id).'%'))
                ->when($request->filled('phone'),
                    fn($q) => $q->where('phone', 'like', '%'.trim($request->phone).'%'));
        }

        $investors = $query->latest()->paginate(10)->withQueryString();

        // أسماء المستثمرين للـ dropdown
        $investorNameOptions = Investor::orderBy('name')->get(['id', 'name']);

        // ====== كروت عامة (إجمالي كل المستثمرين) ======
        $investorsTotalAll = Investor::count();

        // تعريف حالات منتهية/مغلقة لاستبعادها عند حساب "نشِط"
        $endedStatusNames = [
            'منتهي','منتهى','سداد مبكر','سداد مُبكر','سداد مبكّر',
            'Completed','Early Settlement','Closed','Inactive'
        ];

        // IDs للحالات لو فيه جدول statuses
        $endedStatusIds = [];
        if (class_exists(InvestmentStatus::class)) {
            $endedStatusIds = InvestmentStatus::whereIn('name',$endedStatusNames)->pluck('id')->all();
        } elseif (class_exists(ContractStatus::class)) {
            $endedStatusIds = ContractStatus::whereIn('name',$endedStatusNames)->pluck('id')->all();
        }

        // ====== حساب عدد المستثمرين النشطين على مستوى النظام ======
        if (Schema::hasTable('investments') && Schema::hasColumn('investments','investor_id')) {
            // محاولة تعرّف اسم عمود الحالة
            $statusIdCol = null; foreach (['status_id','investment_status_id','state_id'] as $c){ if(Schema::hasColumn('investments',$c)){ $statusIdCol=$c; break; } }
            $statusNmCol = null; foreach (['status','state'] as $c){ if(Schema::hasColumn('investments',$c)){ $statusNmCol=$c; break; } }

            $activeInvestorsTotalAll = Investor::whereExists(function($sub) use($statusIdCol,$statusNmCol,$endedStatusIds,$endedStatusNames){
                $sub->from('investments')
                    ->selectRaw('1')
                    ->whereColumn('investors.id','investments.investor_id');

                if ($statusIdCol && !empty($endedStatusIds)) {
                    $sub->whereNotIn($statusIdCol, $endedStatusIds);
                } elseif ($statusNmCol) {
                    $sub->whereNotIn($statusNmCol, $endedStatusNames);
                } elseif (Schema::hasColumn('investments','is_closed')) {
                    $sub->where('is_closed', 0);
                } elseif (Schema::hasColumn('investments','closed_at')) {
                    $sub->whereNull('closed_at');
                }
            })->count();
        } else {
            // بديل منطقي لو ما فيش جدول investments
            $activeInvestorsTotalAll = Investor::query()
                ->where(function($q){
                    $added = false;
                    if (Schema::hasColumn('investors','contract_image')) {
                        $q->whereNotNull('contract_image')->where('contract_image','!=','');
                        $added = true;
                    }
                    if (Schema::hasColumn('investors','office_share_percentage')) {
                        $added ? $q->orWhere('office_share_percentage','>',0)
                            : $q->where('office_share_percentage','>',0);
                    }
                })
                ->count();
        }

        $newInvestorsThisMonthAll = Investor::whereBetween('created_at',[now()->startOfMonth(), now()])->count();
        $newInvestorsThisWeekAll  = Investor::whereBetween('created_at',[now()->startOfWeek(),  now()])->count();

        // (لو عندك قوائم فلاتر إضافية ومحتاجة في الواجهة)
        // $nationalities = class_exists(Nationality::class)
        //     ? Nationality::select('id','name')->orderBy('name')->get()
        //     : collect();
        // $titles = class_exists(Title::class)
        //     ? Title::select('id','name')->orderBy('name')->get()
        //     : collect();

        return view('investors.index', compact(
            'investors',
            'investorsTotalAll',
            'activeInvestorsTotalAll',
            'newInvestorsThisMonthAll',
            'newInvestorsThisWeekAll',
            'investorNameOptions',
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
            'name' => 'required|string|max:255|unique:investors,name,',
            'nullable|digits:10|regex:/^[12]\d{9}$/|unique:investors,national_id,',
            'phone' => 'nullable|regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/|unique:investors,phone,',
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

    public function show(Request $request,Investor $investor,InvestorDataService $service,InstallmentsMonthlyService $installmentsSvc)
     {
        // بيانات العرض الأساسية (توافق مع نسخ PHP لا تدعم named args)
        try {
            $data = $service->build($investor, currencySymbol: 'ر.س');
        } catch (\Throwable $e) {
            $data = $service->build($investor, 'ر.س');
        }

        // باراميترات شهر/سنة + حالات مستثناة
        $m = $request->integer('m') ?: null;   // 1..12
        $y = $request->integer('y') ?: null;   // YYYY
        $excluded = ['مؤجل', 'معتذر'];

        // ملخص الأقساط — أولوية لاستخدام نسخة المستثمر فقط، مع fallback آمن
        try {
            if (method_exists($installmentsSvc, 'buildForInvestor')) {
                // الإصدار الجديد من السيرفيس
                $installmentsMonthly = $installmentsSvc->buildForInvestor($investor, $m, $y, $excluded);
            } else {
                // محاولة استخدام توقيع build الجديد (4 معاملات)
                $installmentsMonthly = $installmentsSvc->build($m, $y, $excluded, $investor->id);
            }
        } catch (\ArgumentCountError $e) {
            // fallback للإصدار القديم (3 معاملات) — إجمالي النظام
            $installmentsMonthly = $installmentsSvc->build($m, $y, $excluded);
        }

        return view('investors.show', [
            'investor'            => $investor,
            'installmentsMonthly' => $installmentsMonthly,
        ] + $data);
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
            'name' => 'required|string|max:255|unique:investors,name,' . $investor->id,
            'nullable|digits:10|regex:/^[12]\d{9}$/|unique:investors,national_id,'. $investor->id,
            'phone' => 'nullable|regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/|unique:investors,phone,' . $investor->id,
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
