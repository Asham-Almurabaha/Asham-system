<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Nationality;
use App\Models\Title;
use App\Services\CustomerDetailsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;


class CustomerController extends Controller
{
    // عرض كل العملاء
    public function index(Request $request)
    {
        // ====== الاستعلام الأساسي ======
        $query = Customer::query();

        // فلتر دقيق بالـ ID القادم من الـ dropdown
        $customerId = $request->input('customer_id');
        if ($customerId !== null && $customerId !== '' && $customerId !== '_none') {
            // تحقّق سريع أن القيمة رقمية لتفادي أي قيم غريبة
            if (is_numeric($customerId)) {
                $query->where('customers.id', (int) $customerId);
            } else {
                // قيمة غير صالحة => رجّع لا شيء بشكل آمن
                $query->whereRaw('1=0');
            }
        } else {
            // فلاتر النص التقليدية
            $query->when($request->filled('national_id'),
                    fn($q) => $q->where('national_id', 'like', '%'.trim($request->national_id).'%'))
                ->when($request->filled('phone'),
                    fn($q) => $q->where('phone', 'like', '%'.trim($request->phone).'%'));
        }

        $customers = $query->latest()->paginate(10)->withQueryString();

        // أسماء العملاء للـ dropdown
        $customerNameOptions = Customer::orderBy('name')->get(['id','name']);

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

        // محاولة معرفة عمود الحالة في جدول العقود
        $statusIdCol = null;
        foreach (['status_id', 'contract_status_id', 'state_id'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusIdCol = $col; break; }
        }
        $statusNameCol = null;
        foreach (['status', 'state'] as $col) {
            if (Schema::hasColumn('contracts', $col)) { $statusNameCol = $col; break; }
        }

        // عدد العملاء ذوي العقود النشطة
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
                    $c->whereRaw('1=1');
                }
            })
            ->count();

        // إحصائيات إضافية
        $newCustomersThisMonthAll = Customer::whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $newCustomersThisWeekAll  = Customer::whereBetween('created_at', [now()->startOfWeek(),  now()])->count();

        // (اختياري) فلاتر إضافية
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
            'customerNameOptions',
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
    public function show(Customer $customer, Request $request, CustomerDetailsService $detailsSvc)
    {
        // helpers لتنظيف المُدخلات
        $parseIds = function ($value): array {
            if (is_string($value)) {
                $value = array_map('trim', explode(',', $value));
            }
            $value = is_array($value) ? $value : [];
            // أرقام صحيحة موجبة وفريدة
            $value = array_values(array_unique(array_filter(array_map(fn ($v) => (int)$v, $value), fn ($v) => $v > 0)));
            return $value;
        };

        $parseDate = function ($value): ?string {
            if (empty($value)) return null;
            try {
                // نقبل أي صيغة قابلة للبارس ونخرج Y-m-d
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        };

        // فلاتر مُنظَّفة من الكويري
        $filters = [
            'status_ids' => $parseIds($request->input('status_ids', [])),
            'from_start' => $parseDate($request->input('from_start')),
            'to_start'   => $parseDate($request->input('to_start')),
            'from_due'   => $parseDate($request->input('from_due')),
            'to_due'     => $parseDate($request->input('to_due')),
        ];

        // بناء تفاصيل العميل (DTOs)
        $details = $detailsSvc->build($customer->id, $filters);

        // JSON API (عند الطلب)
        if ($request->wantsJson()) {
            return response()->json($details->toArray());
        }

        // بعض النِّسَب الجاهزة للعرض (اختياري للواجهة)
        $totalContracts = (int)$details->total_contracts;
        $percent = function (int $part) use ($totalContracts): float {
            return $totalContracts > 0 ? round(($part / $totalContracts) * 100, 1) : 0.0;
        };

        // تمرير البيانات للواجهة
        return view('customers.show', [
            'customer'          => $customer,
            'details'           => $details, // الكائن الكامل لو حاب تتعامل معه مباشرة

            // مختصرات جاهزة للبلِيد
            'activeContracts'   => $details->active,
            'finishedContracts' => $details->finished,
            'otherContracts'    => $details->other,

            'contractsSummary'  => [
                'total'       => $details->total_contracts,
                'active'      => $details->active_count,
                'finished'    => $details->finished_count,
                'other'       => $details->other_count,
                'pct_active'  => $percent($details->active_count),
                'pct_finished'=> $percent($details->finished_count),
                'pct_other'   => $percent($details->other_count),
            ],

            'statusesBreakdown' => $details->statuses_breakdown,
            'installments'      => $details->installments_summary,

            // الفلاتر بعد التنظيف لإعادة ملؤها في الواجهة
            'filters'           => $filters,
        ]);
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
