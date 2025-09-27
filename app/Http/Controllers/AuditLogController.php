<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

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

    public function show(AuditLog $auditLog)
    {
        $auditLog->loadMissing('user');

        return view('audit_logs.show', [
            'log' => $auditLog,
            'eventColor' => match($auditLog->event) {
                'created' => 'success',
                'updated' => 'warning',
                'deleted' => 'danger',
                'restored' => 'info',
                default => 'secondary',
            },
        ]);
    }

    public function revert(AuditLog $auditLog): RedirectResponse
    {
        $modelClass = $auditLog->auditable_type;

        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return redirect()
                ->route('audit.logs.show', $auditLog)
                ->with('error', __('Audit log revert missing model'));
        }

        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass));

        try {
            $result = DB::transaction(function () use ($auditLog, $modelClass, $usesSoftDeletes) {
                $query = $modelClass::query();

                if ($usesSoftDeletes && method_exists($query, 'withTrashed')) {
                    $query = $query->withTrashed();
                }

                $model = $query->find($auditLog->auditable_id);

                return match ($auditLog->event) {
                    'updated' => $this->revertUpdated($auditLog, $model),
                    'created' => $this->revertCreated($auditLog, $model),
                    'deleted' => $this->revertDeleted($auditLog, $model, $modelClass, $usesSoftDeletes),
                    default    => ['type' => 'error', 'message' => __('Audit log revert unsupported')],
                };
            });
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('audit.logs.show', $auditLog)
                ->with('error', __('Audit log revert failed'));
        }

        return redirect()
            ->route('audit.logs.show', $auditLog)
            ->with($result['type'], $result['message']);
    }

    public function destroyRange(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($data['from'])->startOfDay();
        $to   = Carbon::parse($data['to'])->endOfDay();

        $query = AuditLog::whereBetween('performed_at', [$from, $to]);
        $count = (clone $query)->count();

        if ($count === 0) {
            return redirect()
                ->route('audit.logs')
                ->with('info', __('Audit log range delete empty'));
        }

        $query->delete();

        return redirect()
            ->route('audit.logs')
            ->with('success', __('Audit log range delete success', ['count' => $count]));
    }

    protected function revertUpdated(AuditLog $log, ?Model $model): array
    {
        if (! $model) {
            return ['type' => 'error', 'message' => __('Audit log revert missing model')];
        }

        $oldValues = $log->old_values ?? [];

        if (empty($oldValues)) {
            return ['type' => 'error', 'message' => __('Audit log revert missing values')];
        }

        $model->forceFill($oldValues);
        $model->save();

        return ['type' => 'success', 'message' => __('Audit log revert success updated')];
    }

    protected function revertCreated(AuditLog $log, ?Model $model): array
    {
        if (! $model) {
            return ['type' => 'info', 'message' => __('Audit log revert success already removed')];
        }

        $model->delete();

        return ['type' => 'success', 'message' => __('Audit log revert success created')];
    }

    protected function revertDeleted(AuditLog $log, ?Model $model, string $modelClass, bool $usesSoftDeletes): array
    {
        $oldValues = $log->old_values ?? [];

        if ($usesSoftDeletes && $model && method_exists($model, 'trashed') && $model->trashed()) {
            $model->restore();

            if (! empty($oldValues)) {
                $model->forceFill($oldValues);
                $model->save();
            }

            return ['type' => 'success', 'message' => __('Audit log revert success deleted')];
        }

        if ($model) {
            return ['type' => 'info', 'message' => __('Audit log revert success already restored')];
        }

        if (empty($oldValues)) {
            return ['type' => 'error', 'message' => __('Audit log revert missing values')];
        }

        /** @var \Illuminate\Database\Eloquent\Model $newModel */
        $newModel = new $modelClass();

        if (method_exists($newModel, 'usesTimestamps') && $newModel->usesTimestamps()) {
            $newModel->timestamps = false;
        }

        $newModel->forceFill($oldValues);
        $newModel->save();

        if (property_exists($newModel, 'timestamps') && $newModel->timestamps === false) {
            $newModel->timestamps = true;
        }

        return ['type' => 'success', 'message' => __('Audit log revert success deleted')];
    }
}
