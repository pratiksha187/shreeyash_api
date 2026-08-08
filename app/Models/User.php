<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use App\Support\Tenant;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, UsesTenantConnection;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'company_id',
        'role',
        'admin_permissions',
        'is_active',
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
        'mobile_device_id',
        'mobile_device_name',
        'mobile_device_registered_at',
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
        'mobile_device_id',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isCompanyAdmin(): bool
    {
        return $this->role === 'company_admin';
    }

    public function resolvedAdminPermissions(): array
    {
        if (is_array($this->admin_permissions)) {
            return array_values(array_filter($this->admin_permissions));
        }

        if ($this->isSuperAdmin()) {
            return config('admin.super_admin_permissions', ['dashboard', 'companies']);
        }

        if ($this->isCompanyAdmin()) {
            return config('admin.company_admin_permissions', []);
        }

        return [];
    }

    public function scopeForCurrentCompany(Builder $query): Builder
    {
        $companyId = app(Tenant::class)->id();

        if ($companyId) {
            return $query->where('company_id', $companyId);
        }

        if (session('admin_role') === 'company_admin') {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function scopeEmployees(Builder $query): Builder
    {
        return $query->where('role', 'employee');
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

    public function leaveEntitlements()
    {
        return $this->hasMany(LeaveEntitlement::class);
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
            'admin_permissions' => 'array',
            'is_active' => 'boolean',
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
            'mobile_device_registered_at' => 'datetime',
        ];
    }
}
