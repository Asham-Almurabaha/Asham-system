@extends('layouts.master')

@section('title', 'لوحة التحكم')

@section('content')
<div class="container py-4" dir="rtl">

    {{-- Bootstrap Icons (لو مش محمّل في الـ layout) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        :root {
            --card-radius: 1.2rem;
            --soft-shadow: 0 6px 18px rgba(0,0,0,.06);
            --soft-shadow-hover: 0 10px 24px rgba(0,0,0,.08);
        }
        .dashboard-hero {
            border-radius: var(--card-radius);
            background: linear-gradient(135deg, #e9f5ff 0%, #f7faff 100%);
            padding: 1.25rem 1.5rem;
            border: 1px solid #eef2f7;
        }
        .kpi-card {
            border: 1px solid #eef2f7;
            border-radius: var(--card-radius);
            box-shadow: var(--soft-shadow);
            transition: .2s ease;
            height: 100%;
        }
        .kpi-card:hover { box-shadow: var(--soft-shadow-hover); transform: translateY(-2px); }
        .kpi-icon {
            width: 48px; height: 48px;
            border-radius: .85rem;
            display: grid; place-items: center;
            background: #f4f6fb;
        }
        .kpi-value { font-size: 2rem; line-height: 1; }
        .section-card {
            border: 1px solid #eef2f7;
            border-radius: var(--card-radius);
            box-shadow: var(--soft-shadow);
            overflow: hidden;
        }
        .section-card .card-header {
            background: #fbfcfe; border-bottom: 1px solid #eef2f7;
            font-weight: 700;
        }
        .status-row + .status-row { border-top: 1px dashed #eef2f7; }
        .table thead th { position: sticky; top: 0; background: #f8f9fb; z-index: 1; }
        .badge-chip {
            background: #f1f4f9; color: #3c4a5d; border-radius: 999px; padding: .35rem .7rem; font-weight: 600;
        }
        .text-pos { color: #16a34a !important; } /* أخضر */
        .text-neg { color: #dc2626 !important; } /* أحمر */
        .help-item { margin-bottom: .35rem; }
        .help-item i { width: 1.2rem; display: inline-block; }
    </style>

    {{-- ====== HERO ====== --}}
    <div class="dashboard-hero mb-3">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="kpi-icon"><i class="bi bi-speedometer2 fs-4 text-primary"></i></div>
                <div>
                    <h3 class="mb-1">لوحة التحكم</h3>
                    <div class="text-muted small">آخر تحديث: {{ now()->format('Y-m-d H:i') }}</div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="badge-chip" data-bs-toggle="tooltip" title="إجمالي عدد العقود المسجلة بالنظام">
                    <i class="bi bi-files me-1"></i> إجمالي العقود: {{ number_format($contractsTotal ?? 0) }}
                </span>

                <span class="badge-chip" data-bs-toggle="tooltip"
                      title="الصافي = داخل − خارج (التحويلات بين حسابات محايدة ولا تدخل في الصافي)">
                    <i class="bi bi-people me-1"></i> صافي سيولة المستثمرين: {{ number_format(($invTotals->net ?? 0), 2) }}
                </span>

                <span class="badge-chip" data-bs-toggle="tooltip"
                      title="الصافي = داخل − خارج (التحويلات بين حسابات محايدة ولا تدخل في الصافي)">
                    <i class="bi bi-building me-1"></i> صافي سيولة المكتب: {{ number_format(($officeTotals->net ?? 0), 2) }}
                </span>

                <button class="btn btn-outline-secondary btn-sm ms-2" type="button"
                        data-bs-toggle="collapse" data-bs-target="#kpiHelp" aria-expanded="false">
                    <i class="bi bi-info-circle"></i> شرح المؤشرات
                </button>
            </div>
        </div>
    </div>

    {{-- مربع شرح المؤشرات --}}
    <div class="collapse mb-3" id="kpiHelp">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="help-item"><i class="bi bi-arrow-down-circle text-success"></i>
                    <strong>داخل</strong>: مجموع الحركات ذات نوع العملية <u>إيداع</u>.
                </div>
                <div class="help-item"><i class="bi bi-arrow-up-circle text-danger"></i>
                    <strong>خارج</strong>: مجموع الحركات ذات نوع العملية <u>سحب</u>.
                </div>
                <div class="help-item"><i class="bi bi-shuffle text-muted"></i>
                    <strong>تحويل بين حسابات</strong>: حركات داخلية بين حسابات (بنك/خزنة) تُسجَّل لقيدين (مصدر/وجهة) وتُعتبر
                    <u>محايدة في الصافي</u> لكنها تظهر في أرصدة كل حساب على حدة.
                </div>
                <div class="help-item"><i class="bi bi-calculator"></i>
                    <strong>الصافي</strong> = داخل − خارج (مع تجاهل التحويلات).
                </div>
            </div>
        </div>
    </div>

    {{-- ====== KPIs ====== --}}
    @php
        $invNet = (float)($invTotals->net ?? 0);
        $offNet = (float)($officeTotals->net ?? 0);
        $invClass = $invNet >= 0 ? 'text-pos' : 'text-neg';
        $offClass = $offNet >= 0 ? 'text-pos' : 'text-neg';
    @endphp

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon"><i class="bi bi-files fs-5 text-primary"></i></div>
                        <div class="fw-bold text-muted">إجمالي العقود</div>
                    </div>
                    <i class="bi bi-question-circle text-muted"
                       data-bs-toggle="tooltip" title="إجمالي السجلات في جدول العقود"></i>
                </div>
                <div class="kpi-value fw-bold">{{ number_format($contractsTotal ?? 0) }}</div>
                <div class="small text-muted mt-2">إجمالي السجلات في النظام</div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon"><i class="bi bi-people fs-5 text-primary"></i></div>
                        <div class="fw-bold text-muted">سيولة المستثمرين</div>
                    </div>
                    <i class="bi bi-question-circle text-muted"
                       data-bs-toggle="tooltip" title="داخل/خارج بناءً على نوع العملية، والتحويلات محايدة"></i>
                </div>
                <div class="kpi-value fw-bold {{ $invClass }}">{{ number_format($invNet, 2) }}</div>
                <div class="small text-muted mt-2">
                    داخل: {{ number_format(($invTotals->inflow ?? 0), 2) }} — خارج: {{ number_format(($invTotals->outflow ?? 0), 2) }}
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="kpi-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="kpi-icon"><i class="bi bi-building fs-5 text-primary"></i></div>
                        <div class="fw-bold text-muted">سيولة المكتب</div>
                    </div>
                    <i class="bi bi-question-circle text-muted"
                       data-bs-toggle="tooltip" title="داخل/خارج بناءً على نوع العملية، والتحويلات محايدة"></i>
                </div>
                <div class="kpi-value fw-bold {{ $offClass }}">{{ number_format($offNet, 2) }}</div>
                <div class="small text-muted mt-2">
                    داخل: {{ number_format(($officeTotals->inflow ?? 0), 2) }} — خارج: {{ number_format(($officeTotals->outflow ?? 0), 2) }}
                </div>
            </div>
        </div>
    </div>

    {{-- ====== الحالات + الرسم ====== --}}
    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="section-card card h-100 border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>توزيع حالات العقود</span>
                    <span class="small text-muted" data-bs-toggle="tooltip"
                          title="النِّسب محسوبة من إجمالي العقود الحالي">
                        <i class="bi bi-info-circle"></i>
                    </span>
                </div>
                <div class="card-body p-0">
                    @if(($statuses->count() ?? 0) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($statuses as $s)
                                <div class="list-group-item status-row">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="fw-semibold">{{ $s['name'] }}</div>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge bg-secondary">{{ number_format($s['count']) }}</span>
                                            <span class="text-muted small">{{ $s['pct'] }}%</span>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $s['pct'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-3 text-muted">لا توجد بيانات للحالات.</div>
                    @endif
                </div>
                <div class="card-footer text-end small text-muted">
                    إجمالي الحالات: {{ number_format($contractsTotal ?? 0) }}
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="section-card card h-100 border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>مخطط الحالات (Doughnut)</span>
                    <span class="small text-muted" data-bs-toggle="tooltip"
                          title="المخطط يعكس نفس التوزيع المعروض يمينًا">
                        <i class="bi bi-graph-up"></i>
                    </span>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ====== أعلى المستثمرين ====== --}}
    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="section-card card border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>أعلى 10 مستثمرين حسب الصافي</span>
                    <span class="text-muted small" data-bs-toggle="tooltip"
                          title="الصافي لكل مستثمر = داخل − خارج (التحويلات محايدة)">
                        إجمالي صافي: {{ number_format(($invTotals->net ?? 0), 2) }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-center">
                                    <th style="width:60px;">#</th>
                                    <th class="text-start">المستثمر</th>
                                    <th>داخل</th>
                                    <th>خارج</th>
                                    <th>صافي</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($invByInvestor as $idx => $row)
                                @php
                                    $rowNet = (float)($row->net ?? 0);
                                    $rowClass = $rowNet >= 0 ? 'text-pos' : 'text-neg';
                                @endphp
                                <tr class="text-center">
                                    <td>{{ $idx + 1 }}</td>
                                    <td class="text-start fw-semibold">{{ $row->name }}</td>
                                    <td>{{ number_format(($row->inflow ?? 0), 2) }}</td>
                                    <td>{{ number_format(($row->outflow ?? 0), 2) }}</td>
                                    <td class="fw-bold {{ $rowClass }}">{{ number_format($rowNet, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-4">لا توجد بيانات للمستثمرين.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(($invByInvestor->count() ?? 0) > 0)
                <div class="card-footer small text-muted">
                    * الأرقام أعلاه مبنية على نوع العملية: <span class="text-success">إيداع</span> يُحتسب داخل،
                    <span class="text-danger">سحب</span> يُحتسب خارج، و<span class="text-muted">تحويل بين حسابات</span> محايد في الصافي.
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ====== Scripts ====== --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Tooltips
    const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el, {container: 'body'}));

    // Chart
    const canvas = document.getElementById('statusChart');
    if (!canvas) return;

    const labels = @json(($chartLabels ?? collect())->values());
    const data   = @json(($chartData ?? collect())->values());

    if (!labels.length || !data.length) {
        canvas.parentElement.innerHTML = '<div class="text-muted">لا توجد بيانات للمخطط.</div>';
        return;
    }

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            cutout: '58%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 10 } },
                tooltip: { rtl: true, bodySpacing: 6, padding: 10 }
            },
            animation: { animateScale: true, animateRotate: true }
        }
    });
});
</script>
@endsection
