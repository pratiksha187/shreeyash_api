<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    use UsesTenantConnection;

    protected static function bootBelongsToCompany(): void
    {
        static::creating(function ($model) {
            if (! $model->company_id && app(Tenant::class)->hasCompany()) {
                $model->company_id = app(Tenant::class)->id();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCurrentCompany(Builder $query): Builder
    {
        $companyId = app(Tenant::class)->id();

        if ($companyId) {
            return $query->where($query->getModel()->getTable().'.company_id', $companyId);
        }

        if (session('admin_role') === 'company_admin') {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function scopeForCompany(Builder $query, int|Company $company): Builder
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        return $query->where($query->getModel()->getTable().'.company_id', $companyId);
    }
}
