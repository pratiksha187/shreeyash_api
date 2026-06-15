<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FddTestRecord extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'fdd_road_section_id',
        'group_number',
        'section_name',
        'test_date',
        'material',
        'location',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'test_date' => 'date',
            'fdd_road_section_id' => 'integer',
            'group_number' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function roadSection(): BelongsTo
    {
        return $this->belongsTo(FddRoadSection::class, 'fdd_road_section_id');
    }
}
