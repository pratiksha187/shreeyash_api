<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialIssue extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'material_request_id',
        'material_id',
        'labour_site_id',
        'issued_quantity',
        'issued_by',
        'issued_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'issued_quantity' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaterialRequest::class, 'material_request_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(LabourSite::class, 'labour_site_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
