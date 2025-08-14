<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * الحقول القابلة للإسناد الكتلي.
     *
     * تأكد أن أعمدة phone و locale موجودة بقاعدة البيانات.
     */
    protected $fillable = [
        'name',
        'email',
        'locale',  // ar / en
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
     * قيم افتراضية للخصائص (اختياري).
     */
    protected $attributes = [
        'locale' => 'ar',
    ];
}
