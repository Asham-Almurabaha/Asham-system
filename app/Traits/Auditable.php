<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            self::writeAudit($model, 'created', null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            // القيم المعدلة فقط
            $dirty = $model->getDirty();
            // تجاهل timestamps لو هي الوحيدة
            $dirty = Arr::except($dirty, ['updated_at', $model->getUpdatedAtColumn()]);
            if (empty($dirty)) return;

            // القيم القديمة المقابلة
            $old = [];
            foreach ($dirty as $k => $v) {
                $old[$k] = $model->getOriginal($k);
            }
            self::writeAudit($model, 'updated', $old, $dirty);
        });

        static::deleted(function (Model $model) {
            // لو SoftDeletes: نعتبرها deleted (وممكن تضيف restored تحت)
            self::writeAudit($model, 'deleted', $model->getOriginal(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                self::writeAudit($model, 'restored', null, $model->getAttributes());
            });
        }
    }

    protected static function writeAudit(Model $model, string $event, ?array $old, ?array $new): void
    {
        // بيانات الطلب/المستخدم (لو داخل HTTP)
        $req = request();
        $userId = optional(auth()->user())->id;

        AuditLog::create([
            'user_id'        => $userId,
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->getKey(),
            'event'          => $event,
            'old_values'     => $old,
            'new_values'     => $new,
            'url'            => $req?->fullUrl(),
            'ip_address'     => $req?->ip(),
            'user_agent'     => $req?->userAgent(),
            'performed_at'   => now(),
        ]);
    }
}