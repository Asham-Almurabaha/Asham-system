
{{-- البطاقة: المستثمرون --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <strong>المستثمرون</strong>
        @if($contract->investors->count())
            @php
                $sumPct = $contract->investors->sum(fn($i) => (float)$i->pivot->share_percentage);
                $sumVal = $contract->investors->sum(fn($i) => (float)$i->pivot->share_value);
            @endphp
            <span class="badge bg-light text-dark">
                إجمالي النسبة: {{ number_format($sumPct, 2) }}% — إجمالي المشاركة: {{ number_format($sumVal, 2) }}
            </span>
        @endif
    </div>
    <div class="card-body p-0">
        @if($contract->investors->count())
            <table class="table table-bordered table-striped mb-0 text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>المستثمر</th>
                        <th>النسبة (%)</th>
                        <th>قيمة المشاركة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contract->investors as $inv)
                        <tr>
                            <td>{{ $inv->name }}</td>
                            <td>{{ number_format($inv->pivot->share_percentage, 2) }}</td>
                            <td>{{ number_format($inv->pivot->share_value, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-3 text-muted">لا يوجد مستثمرون مرتبطون بهذا العقد.</div>
        @endif
    </div>
</div>

