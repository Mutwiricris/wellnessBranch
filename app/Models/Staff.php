<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'specialties',
        'bio',
        'profile_image',
        'experience_years',
        'hourly_rate',
        'status',
        'color',
        // Payroll fields
        'base_salary',
        'payment_type',
        'bank_name',
        'bank_account_number',
        'bank_branch',
        'kra_pin',
        'nssf_number',
        'nhif_number',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected $casts = [
        'specialties' => 'array',
        'experience_years' => 'integer',
        'hourly_rate' => 'decimal:2',
        'base_salary' => 'decimal:2',
    ];

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_staff')
                    ->withPivot('working_hours', 'is_primary_branch')
                    ->withTimestamps();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'staff_services')
                    ->withPivot('proficiency_level')
                    ->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(StaffCommission::class);
    }

    public function commissionAssignments(): HasMany
    {
        return $this->hasMany(StaffCommissionAssignment::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getFormattedExperienceAttribute()
    {
        return $this->experience_years . ' year' . ($this->experience_years !== 1 ? 's' : '') . ' experience';
    }
    
    // Performance Metrics
    public function getTotalBookings($startDate = null, $endDate = null)
    {
        $query = $this->bookings();
        
        if ($startDate) {
            $query->where('appointment_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('appointment_date', '<=', $endDate);
        }
        
        return $query->count();
    }
    
    public function getCompletedBookings($startDate = null, $endDate = null)
    {
        $query = $this->bookings()->where('status', 'completed');
        
        if ($startDate) {
            $query->where('appointment_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('appointment_date', '<=', $endDate);
        }
        
        return $query->count();
    }
    
    public function getCompletionRate($startDate = null, $endDate = null)
    {
        $total = $this->getTotalBookings($startDate, $endDate);
        $completed = $this->getCompletedBookings($startDate, $endDate);
        
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }
    
    public function getTotalRevenue($startDate = null, $endDate = null)
    {
        $query = $this->bookings()->where('status', 'completed');
        
        if ($startDate) {
            $query->where('appointment_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('appointment_date', '<=', $endDate);
        }
        
        return $query->sum('total_amount') ?? 0;
    }
    
    public function getAverageRating()
    {
        // Assuming there's a reviews table/relationship
        return $this->bookings()
            ->whereNotNull('rating')
            ->avg('rating') ?? 0;
    }
    
    public function getUtilizationRate($date = null): float
    {
        $date = $date ?? now()->toDateString();
        
        // Fix: Wrap orWhere in where() for proper grouping
        $schedule = $this->schedules()
            ->where(function($query) use ($date) {
                $query->where('date', $date)
                      ->orWhere(function($q) use ($date) {
                          $dayOfWeek = strtolower(\Carbon\Carbon::parse($date)->format('l'));
                          $q->where('day_of_week', $dayOfWeek)
                            ->whereNull('date');
                      });
            })
            ->first();
            
        if (!$schedule) {
            return 0.0;
        }
        
        $availableMinutes = $this->calculateMinutesBetween(
            $schedule->start_time, 
            $schedule->end_time
        );
        
        if ($availableMinutes <= 0) {
            return 0.0;
        }
        
        $bookedMinutes = $this->bookings()
            ->where('appointment_date', $date)
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->get()
            ->sum(fn($booking) => $this->calculateMinutesBetween(
                $booking->start_time, 
                $booking->end_time
            ));
            
        return round(($bookedMinutes / $availableMinutes) * 100, 2);
    }
    
    private function calculateMinutesBetween($startTime, $endTime)
    {
        $start = \Carbon\Carbon::parse($startTime);
        $end = \Carbon\Carbon::parse($endTime);
        
        return $start->diffInMinutes($end);
    }
    
    public function getTopServices($limit = 5)
    {
        return $this->bookings()
            ->select('service_id')
            ->selectRaw('COUNT(*) as booking_count')
            ->with('service')
            ->groupBy('service_id')
            ->orderByDesc('booking_count')
            ->limit($limit)
            ->get();
    }
    
    public function getWorkingBranches()
    {
        return $this->branches()->where('status', 'active')->get();
    }
    
    public function getPrimaryBranch()
    {
        return $this->branches()->wherePivot('is_primary_branch', true)->first();
    }
    
    public function getColorAttribute($value)
    {
        return $value ?? $this->generateColorFromName();
    }
    
    private function generateColorFromName()
    {
        $colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
            '#DDA0DD', '#98D8C8', '#FFB347', '#87CEEB', '#F0E68C'
        ];
        
        $index = crc32($this->name) % count($colors);
        return $colors[abs($index)];
    }
}