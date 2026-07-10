<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveEntitlement extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'leave_year_start',
        'leave_year_end',
        'casual_leave_limit',
        'sick_leave_limit',
        'paid_leave_limit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'leave_year_start' => 'date',
            'leave_year_end' => 'date',
            'casual_leave_limit' => 'integer',
            'sick_leave_limit' => 'integer',
            'paid_leave_limit' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{casual: int, sick: int, paid: int}
     */
    public function limits(): array
    {
        return [
            'casual' => $this->casual_leave_limit,
            'sick' => $this->sick_leave_limit,
            'paid' => $this->paid_leave_limit,
        ];
    }

    public function limitFor(string $leaveType): int
    {
        return $this->limits()[$leaveType] ?? 0;
    }

    public function totalLimit(): int
    {
        return array_sum($this->limits());
    }
}
