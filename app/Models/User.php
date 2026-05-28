<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'gender',
        'marital_status',
        'date_of_birth',
        'join_date',
        'confirmation_date',
        'probation_months',
        'aadhaar_number',
        'hours_per_day',
        'days_per_week',
        'salary',
        'insurance',
        'pt',
        'advance',
        'pf',
        'designation',
        'password',
        'api_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function dailyProgressReports()
    {
        return $this->hasMany(DailyProgressReport::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function challans()
    {
        return $this->hasMany(Challan::class);
    }

    public function missedAttendanceRequests()
    {
        return $this->hasMany(MissedAttendanceRequest::class);
    }

    public function submittedLabourAttendances()
    {
        return $this->hasMany(LabourAttendance::class, 'engineer_user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'join_date' => 'date',
            'confirmation_date' => 'date',
            'hours_per_day' => 'decimal:2',
            'salary' => 'decimal:2',
            'insurance' => 'decimal:2',
            'pt' => 'decimal:2',
            'advance' => 'decimal:2',
            'pf' => 'decimal:2',
            'password' => 'hashed',
        ];
    }
}
