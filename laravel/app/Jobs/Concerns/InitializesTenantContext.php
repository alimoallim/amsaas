<?php

namespace App\Jobs\Concerns;

use App\Services\MultiTenancy\TenancyManager;

trait InitializesTenantContext
{
    public string $companyId;

    protected function initializeTenantContext(): void
    {
        app(TenancyManager::class)->setCompanyId($this->companyId);
    }
}
