<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MirFileReport extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'report_date',
        'material',
        'quantity',
        'unit',
        'location',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'quantity' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }
}
