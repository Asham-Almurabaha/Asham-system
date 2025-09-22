@if($contract->investors->count() > 0)
   
    <x-table head-class="table-light" striped bordered class="text-center" :hover="false">
        <x-slot name="head">
            <tr>
                <th>المستثمر</th>
                <th>النسبة (%)</th>
                <th>قيمة المشاركة</th>
                <th>نسبة المكتب (%)</th>
            </tr>
        </x-slot>
        @foreach($contract->investors as $inv)
            <tr>
                <td>
                  <a href="{{ route('investors.show', $inv->id) }}" class="text-reset text-decoration-none">
                    {{ $inv->name }}
                  </a>
                </td>
                <td>{{ number_format($inv->pivot->share_percentage, 2) }}</td>
                <td>{{ number_format($inv->pivot->share_value, 2) }}</td>
                <td>{{ number_format($inv->pivot->office_share_percentage ?? $inv->office_share_percentage ?? 0, 2) }}</td>
            </tr>
        @endforeach
    </x-table>
@else
    <div class="p-3 text-muted">لا يوجد مستثمرون مرتبطون بهذا العقد.</div>
@endif
