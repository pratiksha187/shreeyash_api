<?php

namespace App\Support;

use App\Models\Company;

class Tenant
{
    private ?Company $company = null;

    private ?string $connectionName = null;

    public function set(?Company $company): void
    {
        $this->company = $company;
        $this->connectionName = null;

        if ($company?->database_name) {
            app(TenantDatabaseManager::class)->configure($company);
            $this->connectionName = 'tenant';
        }
    }

    public function company(): ?Company
    {
        return $this->company;
    }

    public function id(): ?int
    {
        return $this->company?->id;
    }

    public function hasCompany(): bool
    {
        return $this->company !== null;
    }

    public function connectionName(): ?string
    {
        return $this->connectionName;
    }
}
