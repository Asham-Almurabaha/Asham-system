<?php

namespace Modules\Guarantors\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Lookups\Entities\GuarantorStatus;
use Modules\Lookups\Entities\Nationality;
use Modules\Lookups\Entities\Title;
use Modules\Contracts\Entities\Contract;

class Guarantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'national_id',
        'phone',
        'email',
        'address',
        'nationality_id',
        'title_id',
        'guarantor_status_id',
        'id_card_image',
        'notes',
    ];

    public function nationality()
    {
        return $this->belongsTo(Nationality::class);
    }

    public function title()
    {
        return $this->belongsTo(Title::class);
    }

    public function guarantorStatus()
    {
        return $this->belongsTo(GuarantorStatus::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
