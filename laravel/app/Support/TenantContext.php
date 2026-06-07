<?php

namespace App\Support;

use App\Services\MultiTenancy\TenancyManager;

/**
 * Resolves the active tenant company for HTTP, jobs, and console.
 */
final class TenantContext
{
    public static function currentCompanyId(): ?string
    {
        if (auth()->check() && auth()->user()?->company_id) {
            return (string) auth()->user()->company_id;
        }

        $fromManager = app(TenancyManager::class)->getCompanyId();

        if ($fromManager) {
            return (string) $fromManager;
        }

        $legacy = app()->bound('tenant.current_id')
            ? app('tenant.current_id')
            : null;

        return $legacy !== null ? (string) $legacy : null;
    }

    public static function setCompanyId(string $companyId): void
    {
        app(TenancyManager::class)->setCompanyId($companyId);
        app()->instance('tenant.current_id', $companyId);
    }
}
