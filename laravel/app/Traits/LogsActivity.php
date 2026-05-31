<?php 
namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(fn ($model) => self::logAction($model, 'created'));
        static::updated(fn ($model) => self::logAction($model, 'updated'));
        static::deleted(fn ($model) => self::logAction($model, 'deleted'));
    }

    private static function logAction($model, $action)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $model->getOriginal(),
            'new_values' => $model->getChanges(),
            'ip_address' => request()->ip(),
        ]);
    }
}