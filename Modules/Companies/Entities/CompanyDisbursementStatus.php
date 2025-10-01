<?php

namespace Modules\Companies\Entities;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyDisbursementStatus extends Model
{
    use HasFactory;
    use Auditable;

    public const DOMAIN = 'companies';

    protected $table = 'statuses';

    protected $guarded = ['id', 'domain'];

    protected $casts = [
        'is_protected' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('domain', function (Builder $builder) {
            $builder->where('domain', self::DOMAIN);
        });

        static::saving(function (Model $model) {
            $model->setAttribute('domain', self::DOMAIN);
        });
    }

    public function transactions()
    {
        return $this->hasMany(CompanyTransaction::class, 'company_disbursement_status_id');
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
