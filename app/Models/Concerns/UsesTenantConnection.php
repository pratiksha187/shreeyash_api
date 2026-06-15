<?php

namespace App\Models\Concerns;

use App\Support\Tenant;

trait UsesTenantConnection
{
    public function getConnectionName()
    {
        return app(Tenant::class)->connectionName() ?: parent::getConnectionName();
    }
}
