@if($contract->investors->count() > 0)
   
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
                    <td>
                      <a href="{{ route('investors.show', $inv->id) }}" class="text-reset text-decoration-none">
                        {{ $inv->name }}
                      </a>
                    </td>
                    <td>{{ number_format($inv->pivot->share_percentage, 2) }}</td>
                    <td>{{ number_format($inv->pivot->share_value, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="p-3 text-muted">لا يوجد مستثمرون مرتبطون بهذا العقد.</div>
@endif
