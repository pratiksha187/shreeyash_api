<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialRequest extends Model
{
    use BelongsToCompany;

    public const STATUSES = [
        'pending',
        'approved',
        'partially_approved',
        'rejected',
        'purchase_required',
        'issued',
        'cancelled',
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'labour_site_id',
        'material_id',
        'requested_quantity',
        'approved_quantity',
        'issued_quantity',
        'required_date',
        'purpose',
        'status',
        'admin_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'decimal:2',
            'approved_quantity' => 'decimal:2',
            'issued_quantity' => 'decimal:2',
            'required_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(LabourSite::class, 'labour_site_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(MaterialIssue::class);
    }
}
