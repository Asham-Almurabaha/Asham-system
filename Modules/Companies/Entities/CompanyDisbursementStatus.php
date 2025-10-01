<?php

namespace Modules\Companies\Entities;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDisbursementStatus extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'name',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'bool',
    ];

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function transactions()
    {
        return $this->hasMany(CompanyTransaction::class);
    }

    public static function automationStatuses(): array
    {
        $statuses = static::query()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        $pending = $statuses->first(fn ($status) => (bool) $status->is_default);
        $nonDefault = $statuses->filter(fn ($status) => !(bool) $status->is_default)->values();

        $partial = $nonDefault->first();
        $settled = $nonDefault->count() > 1 ? $nonDefault->last() : $nonDefault->first();

        return [
            'pending' => $pending,
            'partial' => $partial,
            'settled' => $settled,
        ];
    }
}
