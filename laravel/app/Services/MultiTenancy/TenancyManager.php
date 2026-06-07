<?php 
namespace App\Services\MultiTenancy;

class TenancyManager
{
    protected ?string $companyId = null;

    public function setCompanyId(string $companyId): void
    {
        $this->companyId = $companyId;
    }

    public function getCompanyId(): ?string
    {
        return $this->companyId;
    }

    public function hasCompany(): bool
    {
        return !is_null($this->companyId);
    }
}