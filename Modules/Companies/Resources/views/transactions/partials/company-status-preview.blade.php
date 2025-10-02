@php
    $companyStatusSummaries = collect($companyStatusSummaries ?? [])->sortBy('company_name');
    $selectedCompanyId = $selectedCompanyId ?? null;
    $previewId = $previewId ?? 'company_status_preview_' . uniqid();
@endphp

<div class="company-status-preview" id="{{ $previewId }}">
  <div class="alert alert-secondary small {{ $selectedCompanyId ? 'd-none' : '' }}" data-role="hint">
    {{ __('companies::companies.CompanyStatusPreviewHint') }}
  </div>

  @foreach($companyStatusSummaries as $summary)
    @php
        $companyId = $summary['company_id'] ?? null;
        $companyName = $summary['company_name'] ?? '—';
        $isActive = (bool) ($summary['is_active'] ?? false);
        $totals = (array) ($summary['totals'] ?? []);
        $statuses = collect($summary['statuses'] ?? []);
        $net = (float) ($totals['final_balance'] ?? 0);
        $netClass = $net >= 0 ? 'text-success' : 'text-danger';

        $bankAccounts = $statuses
            ->flatMap(fn ($status) => collect($status['bank_accounts'] ?? []))
            ->groupBy(function ($account) {
                $name = isset($account['name']) ? trim((string) $account['name']) : '';

                return $name === '' ? '__unassigned_bank_account__' : $name;
            })
            ->map(function ($records, $groupKey) {
                $records = collect($records);
                $net = (float) $records->sum(fn ($account) => (float) ($account['net'] ?? 0));
                $displayName = $groupKey === '__unassigned_bank_account__'
                    ? __('companies::companies.Unassigned Bank Account')
                    : $groupKey;

                return [
                    'name' => $displayName,
                    'net' => $net,
                ];
            })
            ->sortBy('name')
            ->values();

        $safes = $statuses
            ->flatMap(fn ($status) => collect($status['safes'] ?? []))
            ->groupBy(function ($safe) {
                $name = isset($safe['name']) ? trim((string) $safe['name']) : '';

                return $name === '' ? '__unassigned_safe__' : $name;
            })
            ->map(function ($records, $groupKey) {
                $records = collect($records);
                $net = (float) $records->sum(fn ($safe) => (float) ($safe['net'] ?? 0));
                $displayName = $groupKey === '__unassigned_safe__'
                    ? __('companies::companies.Unassigned Safe')
                    : $groupKey;

                return [
                    'name' => $displayName,
                    'net' => $net,
                ];
            })
            ->sortBy('name')
            ->values();
    @endphp
    <div class="mt-3 {{ (int) $selectedCompanyId === (int) $companyId ? '' : 'd-none' }}" data-company-id="{{ $companyId }}">
      <div class="form-text d-flex flex-wrap align-items-baseline gap-2 fw-semibold text-body">
        <span>{{ $companyName }}</span>
        <span class="badge {{ $isActive ? 'bg-success-subtle text-success border' : 'bg-secondary-subtle text-secondary border' }}">
          {{ $isActive ? __('companies::companies.Active') : __('companies::companies.Inactive') }}
        </span>
        <span class="text-muted ms-auto">{{ __('companies::companies.Net Balance') }}:</span>
        <strong class="{{ $netClass }}">{{ number_format($net, 2) }}</strong>
      </div>

      @if($statuses->isEmpty())
        <div class="form-text text-muted ps-3">{{ __('companies::companies.No Dashboard Data') }}</div>
      @else
        <div class="form-text ps-3 mt-3">
          <div class="fw-semibold text-muted mb-1">{{ __('companies::companies.Bank Accounts') }}</div>
          @if($bankAccounts->isEmpty())
            <div class="text-muted">{{ __('companies::companies.No Bank Account Activity') }}</div>
          @else
            <ul class="list-unstyled mb-0">
              @foreach($bankAccounts as $account)
                @php
                    $accountNetValue = (float) ($account['net'] ?? 0);
                    $accountNetClass = $accountNetValue >= 0 ? 'text-success' : 'text-danger';
                    $accountNetSign = $accountNetValue >= 0 ? '+' : '-';
                @endphp
                <li class="d-flex align-items-baseline justify-content-between gap-2">
                  <span class="fw-semibold text-body">{{ $account['name'] }}</span>
                  <strong class="{{ $accountNetClass }}">{{ $accountNetSign }}{{ number_format(abs($accountNetValue), 2) }}</strong>
                </li>
              @endforeach
            </ul>
          @endif
        </div>

        <div class="form-text ps-3 mt-3">
          <div class="fw-semibold text-muted mb-1">{{ __('companies::companies.Safes') }}</div>
          @if($safes->isEmpty())
            <div class="text-muted">{{ __('companies::companies.No Safe Activity') }}</div>
          @else
            <ul class="list-unstyled mb-0">
              @foreach($safes as $safe)
                @php
                    $safeNetValue = (float) ($safe['net'] ?? 0);
                    $safeNetClass = $safeNetValue >= 0 ? 'text-success' : 'text-danger';
                    $safeNetSign = $safeNetValue >= 0 ? '+' : '-';
                @endphp
                <li class="d-flex align-items-baseline justify-content-between gap-2">
                  <span class="fw-semibold text-body">{{ $safe['name'] }}</span>
                  <strong class="{{ $safeNetClass }}">{{ $safeNetSign }}{{ number_format(abs($safeNetValue), 2) }}</strong>
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      @endif
    </div>
  @endforeach
</div>
