<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Challan extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'challan_no',
        'challan_date',
        'party_name',
        'material_machine',
        'vehicle_no',
        'measurement',
        'location',
        'delivery_time',
        'receiver_name',
        'driver_name',
        'pdf_file_path',
    ];

    protected function casts(): array
    {
        return [
            'challan_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
