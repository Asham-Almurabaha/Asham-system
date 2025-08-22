<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


class Setting extends Model
{
    use HasFactory;
    protected $table = 'settings';
    protected $fillable = ['owner_name','name','name_ar','logo','favicon'];
    protected $hidden = ['created_at','updated_at'];

    
    // Accessors لروابط العرض
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::url($this->logo) : null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon ? Storage::url($this->favicon) : null;
    }
}
