<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MirFileReport extends Model
{
    use HasFactory;

    protected $fillable = [
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
