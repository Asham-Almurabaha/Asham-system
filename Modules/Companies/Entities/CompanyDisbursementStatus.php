<?php

namespace Modules\Companies\Entities;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDisbursementStatus extends Model
{
    use HasFactory;
    use Auditable;

    protected $guarded = ['id'];

    public function transactions()
    {
        return $this->hasMany(CompanyTransaction::class);
    }

    public static function automationStatuses(): array
    {
        $statuses = static::query()
            ->orderBy('id')
            ->get();

        if ($statuses->isEmpty()) {
            return [
                'pending' => null,
                'partial' => null,
                'settled' => null,
            ];
        }

        $pending = $statuses->get(0);
        $partial = $statuses->get(1) ?? $pending;
        $settled = $statuses->get(2) ?? $statuses->last();

        return [
            'pending' => $pending,
            'partial' => $partial,
            'settled' => $settled,
        ];
    }
}
