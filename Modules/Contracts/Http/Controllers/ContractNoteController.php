<?php

namespace Modules\Contracts\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractNote;
use Modules\Contracts\Http\Requests\StoreContractNoteRequest;

class ContractNoteController extends Controller
{
    public function store(StoreContractNoteRequest $request, Contract $contract): RedirectResponse
    {
        $data = $request->validated();

        $contract->notes()->create([
            'note_date' => Carbon::parse($data['note_date'])->format('Y-m-d'),
            'note' => $data['note'],
        ]);

        return redirect()
            ->route('contracts.show', $contract)
            ->with('success', __('contracts::notes.created'));
    }

    public function destroy(Contract $contract, ContractNote $contractNote): RedirectResponse
    {
        if ($contractNote->contract_id !== $contract->id) {
            abort(404);
        }

        $contractNote->delete();

        return redirect()
            ->route('contracts.show', $contract)
            ->with('success', __('contracts::notes.deleted'));
    }
}
