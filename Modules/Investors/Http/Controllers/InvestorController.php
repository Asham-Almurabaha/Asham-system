<?php

namespace Modules\Investors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use Modules\Lookups\Entities\Nationality;
use Modules\Lookups\Entities\Title;
use App\Services\InstallmentsMonthlyService;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractInvestor;
use Modules\Lookups\Entities\ContractStatus;
use Modules\Investors\Entities\Investor;
use Modules\Investors\Http\Controllers\Concerns\InvestorLiquiditySummaries;
use Modules\Investors\Services\InvestorDataService;

class InvestorController extends Controller
{
    use InvestorLiquiditySummaries;

    public function index(Request $request)
    {
        // استعلام أساسي
        $query = Investor::query();

        // حقل واحد للبحث بالاسم فقط (زي العملاء)
        $name = trim((string) $request->input('investor_q', ''));
        if ($name !== '') {
            $query->where('name', 'like', '%' . $name . '%');
        }

        // 20 نتيجة لكل صفحة
        $investors = $query->latest()->paginate(20)->withQueryString();

        // كروت عامة (غير متأثرة بالفلاتر)
        $investorsTotalAll = Investor::count();
        $activeInvestorsTotalAll = $this->countActiveInvestors();
        $newInvestorsThisMonthAll = Investor::whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $newInvestorsThisWeekAll  = Investor::whereBetween('created_at', [now()->startOfWeek(), now()])->count();

        // Per-page aggregates for table columns
        $ids = $investors->pluck('id')->all();
        $liquidityByInvestor = collect();
        $activeCountByInvestor = collect();
        $remainingByInvestor = collect();

        if (!empty($ids)) {
            // Liquidity: sum(in) - sum(out) for non-office entries
            $liquidityByInvestor = LedgerEntry::query()
                ->whereIn('investor_id', $ids)
                ->where('is_office', false)
                ->groupBy('investor_id')
                ->selectRaw("investor_id, COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END),0) - COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END),0) AS bal")
                ->pluck('bal', 'investor_id');

            // Active contracts per investor + remaining amount
            $endedStatusIds = $this->endedContractStatusIds();

            $rows = DB::table('contract_investor as ci')
                ->join('contracts as c', 'c.id', '=', 'ci.contract_id')
                ->whereIn('ci.investor_id', $ids)
                ->when(!empty($endedStatusIds), function($q) use ($endedStatusIds) {
                    $q->whereNotIn('c.contract_status_id', $endedStatusIds);
                })
                ->select('ci.investor_id','ci.contract_id','ci.share_percentage','ci.share_value','c.contract_value','c.investor_profit')
                ->get();

            // Count active contracts per investor
            $activeCountByInvestor = $rows->groupBy('investor_id')->map(function($g){
                return $g->pluck('contract_id')->unique()->count();
            });

            // Remaining amount per investor
            $remainingByInvestor = $rows->groupBy('investor_id')->map(function($g){
                return $g->reduce(function($carry,$item){
                    $shareVal = (float) ($item->share_value ?? 0);
                    $sharePct = (float) ($item->share_percentage ?? 0);
                    if ($shareVal <= 0 && $item->contract_value) {
                        $shareVal = round(((float)$item->contract_value) * $sharePct / 100, 2);
                    }
                    $profitGross = isset($item->investor_profit)
                        ? round(((float)$item->investor_profit) * $sharePct / 100, 2)
                        : 0.0;
                    return $carry + $shareVal + $profitGross;
                }, 0);
            });
        }

        $nationalities = Nationality::all();
        $titles = Title::all();
        return view('investors::index', compact('investors', 'nationalities', 'titles', 'investorsTotalAll', 'activeInvestorsTotalAll', 'newInvestorsThisMonthAll', 'newInvestorsThisWeekAll', 'liquidityByInvestor', 'activeCountByInvestor', 'remainingByInvestor'));
    }

    public function dashboard(Request $request)
    {
        $investorsTotalAll = Investor::count();
        $activeInvestorsTotalAll = $this->countActiveInvestors();
        $inactiveInvestorsTotalAll = max($investorsTotalAll - $activeInvestorsTotalAll, 0);

        $newInvestorsThisMonthAll = Investor::whereBetween('created_at', [now()->startOfMonth(), now()])->count();
        $newInvestorsThisWeekAll  = Investor::whereBetween('created_at', [now()->startOfWeek(), now()])->count();

        $percentages = [
            'active'   => $investorsTotalAll > 0 ? round(($activeInvestorsTotalAll / $investorsTotalAll) * 100, 1) : 0.0,
            'inactive' => $investorsTotalAll > 0 ? round(($inactiveInvestorsTotalAll / $investorsTotalAll) * 100, 1) : 0.0,
        ];

        $liquidityData = $this->summarizeInvestorLiquidity();
        $liquidityTotals = $liquidityData['totals'];
        $liquidityByInvestor = $liquidityData['perInvestor'];

        $investorLookup = $liquidityByInvestor->isNotEmpty()
            ? Investor::whereIn('id', $liquidityByInvestor->keys()->all())
                ->get(['id', 'name'])
                ->keyBy('id')
            : collect();

        $liquidityReport = $liquidityByInvestor
            ->map(function (array $row, int $investorId) use ($investorLookup) {
                return [
                    'id'   => $investorId,
                    'name' => $investorLookup[$investorId]->name ?? ('#' . $investorId),
                    'in'   => $row['in'],
                    'out'  => $row['out'],
                    'net'  => $row['net'],
                ];
            })
            ->sortByDesc('net')
            ->values();

        $topLiquidity = $liquidityReport
            ->filter(fn ($row) => $row['net'] > 0)
            ->take(10)
            ->values();

        $topLiquidityTotalNet = round((float) $topLiquidity->sum('net'), 2);

        $recentInvestors = Investor::query()
            ->latest()
            ->withCount('contracts')
            ->take(5)
            ->get(['id', 'name', 'created_at', 'investment_start_date', 'office_share_percentage']);

        $investorsWithoutContracts = Investor::doesntHave('contracts')->count();
        $investorsWithContracts = Investor::has('contracts')->count();

        $avgOfficeShare = (float) Investor::avg('office_share_percentage');
        $avgOfficeShare = round($avgOfficeShare ?: 0, 2);

        $endedContractStatusIds = $this->endedContractStatusIds();
        $contractsQuery = DB::table('contract_investor as ci')
            ->join('contracts as c', 'c.id', '=', 'ci.contract_id');

        if (!empty($endedContractStatusIds)) {
            $placeholders = implode(',', array_fill(0, count($endedContractStatusIds), '?'));
            $contractsRow = $contractsQuery
                ->selectRaw('COUNT(DISTINCT c.id) AS total_contracts')
                ->selectRaw("COUNT(DISTINCT CASE WHEN c.contract_status_id NOT IN ($placeholders) THEN c.id END) AS active_contracts", $endedContractStatusIds)
                ->first();
        } else {
            $contractsRow = $contractsQuery
                ->selectRaw('COUNT(DISTINCT c.id) AS total_contracts')
                ->selectRaw('COUNT(DISTINCT c.id) AS active_contracts')
                ->first();
        }

        $contractStats = [
            'total'   => (int) ($contractsRow->total_contracts ?? 0),
            'active'  => (int) ($contractsRow->active_contracts ?? 0),
        ];
        $contractStats['coverage_pct'] = $contractStats['total'] > 0
            ? round(($contractStats['active'] / $contractStats['total']) * 100, 1)
            : 0.0;

        return view('investors::dashboard', [
            'investorsTotalAll'         => $investorsTotalAll,
            'activeInvestorsTotalAll'   => $activeInvestorsTotalAll,
            'inactiveInvestorsTotalAll' => $inactiveInvestorsTotalAll,
            'newInvestorsThisMonthAll'  => $newInvestorsThisMonthAll,
            'newInvestorsThisWeekAll'   => $newInvestorsThisWeekAll,
            'percentages'               => $percentages,
            'liquidityTotals'           => $liquidityTotals,
            'liquidityReport'           => $liquidityReport,
            'topLiquidity'              => $topLiquidity,
            'topLiquidityTotalNet'      => $topLiquidityTotalNet,
            'recentInvestors'           => $recentInvestors,
            'investorsWithoutContracts' => $investorsWithoutContracts,
            'investorsWithContracts'    => $investorsWithContracts,
            'avgOfficeShare'            => $avgOfficeShare,
            'contractStats'             => $contractStats,
        ]);
    }

    public function create()
    {
        $nationalities = Nationality::all();
        $titles = Title::all();
        return view('investors::create', compact('nationalities', 'titles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('investors', 'name')],
            'national_id' => ['nullable', 'digits:10', 'regex:/^[12]\d{9}$/', Rule::unique('investors', 'national_id')],
            'phone' => ['nullable', 'regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/', Rule::unique('investors', 'phone')],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'nationality_id' => ['nullable', 'exists:nationalities,id'],
            'title_id' => ['nullable', 'exists:titles,id'],
            'id_card_image' => ['nullable', 'image', 'max:2048'],
            'contract_image' => ['nullable', 'image', 'max:2048'],
            'office_share_percentage' => ['required', 'numeric', 'between:0,100'],
            'investment_start_date' => ['nullable', 'date'],
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

    public function show(Request $request, Investor $investor, InvestorDataService $service, InstallmentsMonthlyService $installmentsSvc)
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

        return view('investors::show', [
            'investor'            => $investor,
            'installmentsMonthly' => $installmentsMonthly,
        ] + $data);
    }

    public function edit(Investor $investor)
    {
        $nationalities = Nationality::all();
        $titles = Title::all();
        return view('investors::edit', compact('investor', 'nationalities', 'titles'));
    }

    public function update(Request $request, Investor $investor)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('investors', 'name')->ignore($investor->id),
            ],
            'national_id' => [
                'nullable',
                'digits:10',
                'regex:/^[12]\d{9}$/',
                Rule::unique('investors', 'national_id')->ignore($investor->id),
            ],
            'phone' => [
                'nullable',
                'regex:/^(?:05\d{8}|\+?9665\d{8}|009665\d{8})$/',
                Rule::unique('investors', 'phone')->ignore($investor->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'nationality_id' => ['nullable', 'exists:nationalities,id'],
            'title_id' => ['nullable', 'exists:titles,id'],
            'id_card_image' => ['nullable', 'image', 'max:2048'],
            'contract_image' => ['nullable', 'image', 'max:2048'],
            'office_share_percentage' => ['required', 'numeric', 'between:0,100'],
            'investment_start_date' => ['nullable', 'date'],
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

    protected function countActiveInvestors(): int
    {
        $contractInvestorTable = (new ContractInvestor())->getTable();
        $contractsTable = (new Contract())->getTable();
        $investorsTable = (new Investor())->getTable();

        if (Schema::hasTable($contractInvestorTable) && Schema::hasTable($contractsTable)) {
            $query = DB::table($contractInvestorTable . ' as ci')
                ->join($contractsTable . ' as c', 'c.id', '=', 'ci.contract_id');

            if (Schema::hasTable($investorsTable)) {
                $query->join($investorsTable . ' as i', 'i.id', '=', 'ci.investor_id');
            }

            if (Schema::hasColumn($contractInvestorTable, 'deleted_at')) {
                $query->whereNull('ci.deleted_at');
            }

            if (Schema::hasColumn($contractsTable, 'deleted_at')) {
                $query->whereNull('c.deleted_at');
            }

            $statusIdColumn = Schema::hasColumn($contractsTable, 'contract_status_id') ? 'contract_status_id' : null;

            if ($statusIdColumn) {
                $endedStatusIds = $this->endedContractStatusIds();
                if (!empty($endedStatusIds)) {
                    $query->whereNotIn('c.' . $statusIdColumn, $endedStatusIds);
                }
            } else {
                $statusNameColumn = null;
                foreach (['status', 'state'] as $column) {
                    if (Schema::hasColumn($contractsTable, $column)) {
                        $statusNameColumn = $column;
                        break;
                    }
                }

                if ($statusNameColumn) {
                    $query->whereNotIn('c.' . $statusNameColumn, $this->endedContractStatusNames());
                }
            }

            $aggregate = $query
                ->selectRaw('COUNT(DISTINCT ci.investor_id) AS aggregate')
                ->value('aggregate');

            return (int) ($aggregate ?? 0);
        }

        $endedStatusNames = $this->endedInvestmentStatusNames();
        $endedStatusIds = $this->resolveEndedInvestmentStatusIds();

        if (Schema::hasTable('investments') && Schema::hasColumn('investments', 'investor_id')) {
            $statusIdCol = null;
            foreach (['status_id', 'investment_status_id', 'state_id'] as $column) {
                if (Schema::hasColumn('investments', $column)) {
                    $statusIdCol = $column;
                    break;
                }
            }

            $statusNameCol = null;
            foreach (['status', 'state'] as $column) {
                if (Schema::hasColumn('investments', $column)) {
                    $statusNameCol = $column;
                    break;
                }
            }

            return Investor::whereExists(function ($sub) use ($statusIdCol, $statusNameCol, $endedStatusIds, $endedStatusNames) {
                $sub->from('investments')
                    ->selectRaw('1')
                    ->whereColumn('investors.id', 'investments.investor_id');

                if ($statusIdCol && !empty($endedStatusIds)) {
                    $sub->whereNotIn($statusIdCol, $endedStatusIds);
                } elseif ($statusNameCol) {
                    $sub->whereNotIn($statusNameCol, $endedStatusNames);
                } elseif (Schema::hasColumn('investments', 'is_closed')) {
                    $sub->where('is_closed', 0);
                } elseif (Schema::hasColumn('investments', 'closed_at')) {
                    $sub->whereNull('closed_at');
                }
            })->count();
        }

        return Investor::query()
            ->where(function ($q) {
                $added = false;
                if (Schema::hasColumn('investors', 'contract_image')) {
                    $q->whereNotNull('contract_image')->where('contract_image', '!=', '');
                    $added = true;
                }
                if (Schema::hasColumn('investors', 'office_share_percentage')) {
                    $added ? $q->orWhere('office_share_percentage', '>', 0)
                        : $q->where('office_share_percentage', '>', 0);
                }
            })
            ->count();
    }

    protected function endedInvestmentStatusNames(): array
    {
        return [
            'منتهي',
            'منتهى',
            'منتهي بمطالبة',
            'منتهى بمطالبة',
            'سداد مبكر',
            'سداد مُبكر',
            'سداد مبكّر',
            'Completed',
            'Early Settlement',
            'Closed',
            'Inactive',
            'Ended with claim',
            'Ended With Claim',
            'Claim closed',
        ];
    }

    protected function resolveEndedInvestmentStatusIds(): array
    {
        $names = $this->endedInvestmentStatusNames();
        $investmentStatusTable = 'investment_statuses';
        $investmentStatusClass = '\\Modules\\Lookups\\Entities\\InvestmentStatus';

        if (class_exists($investmentStatusClass) && Schema::hasTable($investmentStatusTable)) {
            return $investmentStatusClass::whereIn('name', $names)->pluck('id')->all();
        }

        $contractStatusTable = (new ContractStatus())->getTable();
        if (Schema::hasTable($contractStatusTable)) {
            return ContractStatus::whereIn('name', $names)->pluck('id')->all();
        }

        return [];
    }

    protected function endedContractStatusIds(): array
    {
        $names = $this->endedContractStatusNames();
        $contractStatusTable = (new ContractStatus())->getTable();

        if (!Schema::hasTable($contractStatusTable)) {
            return [];
        }

        return ContractStatus::whereIn('name', $names)->pluck('id')->all();
    }

    protected function endedContractStatusNames(): array
    {
        return [
            'مكتمل',
            'منتهي',
            'منتهى',
            'منتهي بمطالبة',
            'منتهى بمطالبة',
            'سداد مبكر',
            'سداد مُبكر',
            'سداد مبكّر',
            'إلغاء',
            'Closed',
            'Completed',
            'Early Settlement',
            'Inactive',
            'Ended with claim',
            'Ended With Claim',
            'Claim closed',
        ];
    }
}
