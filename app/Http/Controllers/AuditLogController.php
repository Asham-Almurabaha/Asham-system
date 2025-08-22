<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // قيم الفلاتر
        $event   = $request->string('event')->toString();         // created/updated/deleted/restored
        $userId  = $request->input('user_id');                    // معرّف المستخدم
        $model   = $request->string('model')->toString();         // اسم الموديل كامل أو class_basename
        $ip      = $request->string('ip')->toString();            // فلترة IP
        $q       = $request->string('q')->toString();             // بحث حر: في ref أو الملاحظات إن وجدت
        $from    = $request->date('from');                        // تاريخ بداية
        $to      = $request->date('to');                          // تاريخ نهاية (شامل اليوم كله)

        $logs = AuditLog::with('user')
            ->when($event, fn($q2) => $q2->where('event', $event))
            ->when($userId, fn($q2) => $q2->where('user_id', $userId))
            ->when($ip, fn($q2) => $q2->where('ip_address', 'like', '%'.$ip.'%'))
            ->when($model, function ($q2) use ($model) {
                // دعم class_basename في الفلتر
                $q2->where(function ($w) use ($model) {
                    $w->where('auditable_type', $model)
                      ->orWhereRaw('LOWER(SUBSTRING_INDEX(auditable_type, "\\\\", -1)) = ?', [mb_strtolower($model)]);
                });
            })
            ->when($q, function ($q2) use ($q) {
                // لو عندك أعمدة أخرى للبحث أضفها هنا
                $q2->where(function ($w) use ($q) {
                    $w->where('event', 'like', '%'.$q.'%')
                      ->orWhere('ip_address', 'like', '%'.$q.'%')
                      ->orWhereJsonContains('new_values->notes', $q)
                      ->orWhereJsonContains('old_values->notes', $q);
                });
            })
            ->when($from, fn($q2) => $q2->whereDate('performed_at', '>=', $from))
            ->when($to,   fn($q2) => $q2->whereDate('performed_at', '<=', $to))
            ->latest('performed_at')
            ->paginate(20)
            ->withQueryString();

        // خيارات القوائم المنسدلة
        $users  = User::orderBy('name')->get(['id','name']);
        $events = ['created','updated','deleted','restored'];
        // موديلات مميزة من السجل
        $models = AuditLog::select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->map(function ($fqn) {
                return [
                    'fqn'  => $fqn,
                    'base' => class_basename($fqn),
                ];
            });

        return view('audit_logs.index', compact('logs','users','events','models'));
    }
}