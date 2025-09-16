<?php

namespace Modules\Guarantors\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Lookups\Nationality;
use App\Models\Lookups\Title;
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

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
