<?php

namespace Modules\Companies\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Companies\Entities\CompanyDisbursementStatus;
use Modules\Companies\Http\Requests\StoreCompanyDisbursementStatusRequest;
use Modules\Companies\Http\Requests\UpdateCompanyDisbursementStatusRequest;

class CompanyDisbursementStatusController extends Controller
{
    public function index(): View
    {
        $statuses = CompanyDisbursementStatus::query()
            ->orderBy('id')
            ->get();

        return view('companies::disbursement-statuses.index', compact('statuses'));
    }

    public function create(): View
    {
        return view('companies::disbursement-statuses.create');
    }

    public function store(StoreCompanyDisbursementStatusRequest $request): RedirectResponse
    {
        CompanyDisbursementStatus::create($request->validated());

        return redirect()
            ->route('company-disbursement-statuses.index')
            ->with('success', __('companies::messages.disbursement_statuses.created'));
    }

    public function edit(CompanyDisbursementStatus $companyDisbursementStatus): View
    {
        return view('companies::disbursement-statuses.edit', [
            'status' => $companyDisbursementStatus,
        ]);
    }

    public function update(
        UpdateCompanyDisbursementStatusRequest $request,
        CompanyDisbursementStatus $companyDisbursementStatus
    ): RedirectResponse {
        $companyDisbursementStatus->update($request->validated());

        return redirect()
            ->route('company-disbursement-statuses.index')
            ->with('success', __('companies::messages.disbursement_statuses.updated'));
    }

    public function destroy(CompanyDisbursementStatus $companyDisbursementStatus): RedirectResponse
    {
        $companyDisbursementStatus->delete();

        return redirect()
            ->route('company-disbursement-statuses.index')
            ->with('success', __('companies::messages.disbursement_statuses.deleted'));
    }
}
