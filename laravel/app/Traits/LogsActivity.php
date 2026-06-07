<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => self::logAction($model, 'created'));
        static::updated(fn ($model) => self::logAction($model, 'updated'));
        static::deleted(fn ($model) => self::logAction($model, 'deleted'));
    }

    private static function logAction($model, string $action): void
    {
        $companyId = $model->company_id ?? Auth::user()?->company_id;

        AuditLog::create([
            'company_id' => $companyId,
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $model::class,
            'entity_id' => $model->getKey(),
            'old_values' => $action === 'created' ? null : $model->getOriginal(),
            'new_values' => $action === 'deleted' ? null : $model->getChanges(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
