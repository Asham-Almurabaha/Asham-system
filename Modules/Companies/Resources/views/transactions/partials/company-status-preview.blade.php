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

      <div class="form-text text-muted ps-3">
        {{ __('companies::companies.Bank Total') }}: <strong class="text-body">{{ number_format((float) ($totals['bank_amount'] ?? 0), 2) }}</strong>
        • {{ __('companies::companies.Safe Total') }}: <strong class="text-body">{{ number_format((float) ($totals['safe_amount'] ?? 0), 2) }}</strong>
      </div>

      @if($statuses->isEmpty())
        <div class="form-text text-muted ps-3">{{ __('companies::companies.No Dashboard Data') }}</div>
      @else
        @foreach($statuses as $status)
          @php
              $statusName = $status['status_name'] ?? '—';
              $statusCount = number_format((int) ($status['transaction_count'] ?? 0));
              $statusNet = (float) ($status['final_balance'] ?? 0);
              $statusNetClass = $statusNet >= 0 ? 'text-success' : 'text-danger';
              $statusNetSign = $statusNet >= 0 ? '+' : '-';
              $bankAmount = (float) ($status['bank_amount'] ?? 0);
              $safeAmount = (float) ($status['safe_amount'] ?? 0);
              $bankAccounts = collect($status['bank_accounts'] ?? []);
              $safes = collect($status['safes'] ?? []);
              $bankNet = (float) $bankAccounts->sum(fn ($account) => (float) ($account['net'] ?? 0));
              $safeNet = (float) $safes->sum(fn ($safe) => (float) ($safe['net'] ?? 0));
              $bankNetClass = $bankNet >= 0 ? 'text-success' : 'text-danger';
              $bankNetSign = $bankNet >= 0 ? '+' : '-';
              $safeNetClass = $safeNet >= 0 ? 'text-success' : 'text-danger';
              $safeNetSign = $safeNet >= 0 ? '+' : '-';
              $statusRowClasses = $loop->first ? 'mt-3 pt-2' : 'mt-3 border-top pt-3';
          @endphp
          <div class="form-text d-flex flex-wrap align-items-baseline gap-2 {{ $statusRowClasses }}">
            <span class="text-muted">{{ __('companies::companies.Status') }}:</span>
            <span class="fw-semibold text-body">{{ $statusName }}</span>
            <span class="badge bg-light border text-body-secondary">{{ __('companies::companies.Transactions Count') }}: {{ $statusCount }}</span>
            <span class="text-muted ms-auto">{{ __('companies::companies.Net Balance') }}:</span>
            <strong class="{{ $statusNetClass }}">{{ $statusNetSign }}{{ number_format(abs($statusNet), 2) }}</strong>
          </div>
          <div class="form-text d-flex flex-wrap align-items-baseline gap-2 ps-4">
            <span class="text-muted">{{ __('companies::companies.Bank Accounts') }}:</span>
            <strong class="text-body">{{ number_format($bankAmount, 2) }}</strong>
            <span class="text-muted">{{ __('companies::companies.Net Balance') }}:</span>
            <strong class="{{ $bankNetClass }}">{{ $bankNetSign }}{{ number_format(abs($bankNet), 2) }}</strong>
          </div>
          <div class="form-text d-flex flex-wrap align-items-baseline gap-2 ps-4">
            <span class="text-muted">{{ __('companies::companies.Safes') }}:</span>
            <strong class="text-body">{{ number_format($safeAmount, 2) }}</strong>
            <span class="text-muted">{{ __('companies::companies.Net Balance') }}:</span>
            <strong class="{{ $safeNetClass }}">{{ $safeNetSign }}{{ number_format(abs($safeNet), 2) }}</strong>
          </div>
        @endforeach
      @endif
    </div>
  @endforeach
</div>
