<?php

namespace Modules\Lookups\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimFirstParty extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
}
