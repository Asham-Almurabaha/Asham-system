<?php

namespace Modules\Contracts\Entities;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractNote extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'contract_id',
        'note_date',
        'note',
    ];

    protected $casts = [
        'note_date' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
