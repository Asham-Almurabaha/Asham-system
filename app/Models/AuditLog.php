<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id','auditable_type','auditable_id','event',
        'old_values','new_values','url','ip_address','user_agent','performed_at',
    ];

    protected $casts = [
        'old_values'  => 'array',
        'new_values'  => 'array',
        'performed_at'=> 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}