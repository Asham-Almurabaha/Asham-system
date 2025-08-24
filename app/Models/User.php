<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// Spatie Permissions
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * اسم الـ guard المستخدم مع Spatie (غالبًا web).
     * لو عندك guards تانية غيّره بما يناسبك.
     */
    protected string $guard_name = 'web';

    /**
     * الحقول القابلة للإسناد الكتلي.
     * تأكد أن أعمدة phone و locale موجودة بقاعدة البيانات لو هتستخدمها.
     */
    protected $fillable = [
        'name',
        'email',
        // 'phone',    // فعّلها لو العمود موجود عندك
        'locale',      // ar / en
        'password',
    ];

    /**
     * الحقول المخفية عند التحويل إلى مصفوفة/JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * التحويلات (casts).
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'locale'            => 'string',
        ];
    }

    /**
     * قيم افتراضية للخصائص.
     */
    protected $attributes = [
        'locale' => 'ar',
    ];

    /**
     * (اختياري) مساعد سريع لمعرفة إذا كان المستخدم أدمن.
     * يفيدك مع Gate::before مثلاً.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
