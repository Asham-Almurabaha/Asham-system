<?php

namespace Modules\Customers\Entities;

use Modules\Lookups\Entities\Nationality;
use Modules\Lookups\Entities\Title;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Contracts\Entities\Contract;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'national_id',
        'title_id',
        'phone',
        'email',
        'address',
        'nationality_id',
        'id_card_image',
        'notes',
    ];

    // العلاقة مع الجنسية
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
