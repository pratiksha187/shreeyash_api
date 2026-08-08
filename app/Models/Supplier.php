<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'contact_person',
        'mobile',
        'email',
        'gstin',
        'address',
        'default_dispatched_through',
        'default_destination',
        'default_terms',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
