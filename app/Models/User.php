<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\PasswordResetNotification($token));
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification());
    }
    
    /**
     * Disable automatic tenant scoping since users table doesn't have branch_id column
     */
    protected static $isTenantAware = false;

    /**
     * Boot the model and disable automatic tenant scoping
     */
    protected static function boot()
    {
        parent::boot();
        
        // Override any tenant global scope that might be applied automatically
        static::addGlobalScope('tenant', function ($builder) {
            // Empty scope - no tenant filtering for users
        });

        static::saving(function ($user) {
            if ($user->first_name || $user->last_name) {
                $user->name = trim($user->first_name . ' ' . $user->last_name);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'user_type',
        'first_name',
        'last_name', 
        'phone',
        'date_of_birth',
        'gender',
        'allergies',
        'preferences',
        'marketing_consent',
        'create_account_status',
        'onboarding_completed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'preferences' => 'array',
            'marketing_consent' => 'boolean',
            'onboarding_completed' => 'boolean',
        ];
    }

    /**
     * Get the user's bookings
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'client_id');
    }


    /**
     * Get the user's client memberships
     */
    public function clientMemberships()
    {
        return $this->hasMany(ClientMembership::class, 'user_id');
    }

    /**
     * Get the user's active client membership
     */
    public function activeMembership()
    {
        return $this->clientMemberships()->where('status', 'active')->latest()->first();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: $this->name;
    }

    /**
     * Get formatted phone number
     */
    public function getFormattedPhoneAttribute(): string
    {
        if (!$this->phone) return '';
        
        $phone = preg_replace('/\D/', '', $this->phone);
        
        if (str_starts_with($phone, '254')) {
            return '+254 ' . substr($phone, 3, 3) . ' ' . substr($phone, 6, 3) . ' ' . substr($phone, 9, 3);
        } elseif (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return substr($phone, 0, 4) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7, 3);
        }
        
        return $this->phone;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    /**
     * Check if user is staff
     */
    public function isStaff(): bool
    {
        return $this->user_type === 'staff';
    }

    /**
     * Check if user is regular user
     */
    public function isUser(): bool
    {
        return $this->user_type === 'user';
    }

    /**
     * Check if user is branch manager
     */
    public function isBranchManager(): bool
    {
        return $this->user_type === 'branch_manager';
    }

    /**
     * Check if user is director
     */
    public function isDirector(): bool
    {
        return $this->user_type === 'director';
    }

    /**
     * Check if user can login to admin area
     */
    public function canAccessAdmin(): bool
    {
        return in_array($this->user_type, ['admin', 'staff', 'branch_manager']);
    }

    /**
     * Check if user can access branch management
     */
    public function canManageBranch(): bool
    {
        return $this->user_type === 'branch_manager';
    }

    /**
     * Get the branch ID this user can manage
     * Note: Branch managers are associated with branches through a separate mechanism
     */
    public function getManagedBranchId(): ?int
    {
        // This would need to be implemented through a separate branch_managers table
        // or another relationship mechanism since users don't have direct branch_id
        return null;
    }

    /**
     * Scope to get only users who can login
     */
    public function scopeCanLogin($query)
    {
        return $query->whereIn('user_type', ['admin', 'staff', 'branch_manager']);
    }

    /**
     * Scope to get only regular users (booking customers)
     */
    public function scopeCustomers($query)
    {
        return $query->where('user_type', 'user');
    }

    /**
     * Scope to get only branch managers
     */
    public function scopeBranchManagers($query)
    {
        return $query->where('user_type', 'branch_manager');
    }

    /**
     * Scope to get branch managers for a specific branch
     * Note: This would need to be implemented through a separate relationship
     */
    public function scopeForBranch($query, $branchId)
    {
        // This would need to be implemented through a separate branch_managers table
        // since users don't have direct branch_id column
        return $query->whereRaw('1 = 0'); // Return empty result for now
    }

    /**
     * Get the branches owned by this user (Directors)
     */
    public function ownedBranches()
    {
        return $this->hasMany(Branch::class, 'user_id');
    }

    /**
     * Get the branches assigned to this user (Managers)
     */
    public function branches(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_managers')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }

    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow branch managers, directors, and admins to access the admin/director panel
        if (in_array($panel->getId(), ['admin', 'director'])) {
            return in_array($this->user_type, ['branch_manager', 'director', 'admin']);
        }
        
        return false;
    }

    /**
     * Get the user's display name for Filament
     */
    public function getFilamentName(): string
    {
        return $this->getFullNameAttribute() ?: ($this->name ?: 'Unknown User');
    }

    /**
     * Get the user's avatar URL for Filament
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return null; // Will use default UI Avatars
    }

    /**
     * Get the user's current subscription
     */
    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Check if the user has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription?->isActive() ?? false;
    }

    /**
     * Get the tenants (branches) that this user can access
     */
    public function getTenants(?Panel $panel = null): \Illuminate\Database\Eloquent\Collection
    {
        // If panel is null, we still want to return all accessible branches
        // Directors and admins can access their owned branches
        if (in_array($this->user_type, ['director', 'admin'])) {
            return Branch::where('user_id', $this->id)->active()->get();
        }
        
        // Branch managers can only access their assigned branches
        // Check if there's a pivot relationship via branch_managers table
        if ($this->user_type === 'branch_manager') {
            return Branch::whereHas('managers', function ($query) {
                $query->where('user_id', $this->id);
            })->active()->get();
        }
        
        return Branch::query()->whereRaw('1 = 0')->get(); // Empty collection
    }

    /**
     * Check if the user can access a specific tenant (branch)
     */
    public function canAccessTenant(Model $tenant): bool
    {
        // Ensure the tenant is a Branch model
        if (!$tenant instanceof Branch) {
            return false;
        }
        
        // Directors and admins can access their owned branches
        if (in_array($this->user_type, ['director', 'admin'])) {
            return $tenant->user_id === $this->id && $tenant->status === 'active';
        }
        
        // Branch managers can only access their assigned branches
        if ($this->user_type === 'branch_manager') {
            return DB::table('branch_managers')
                ->where('branch_id', $tenant->id)
                ->where('user_id', $this->id)
                ->where('is_active', true)
                ->exists();
        }
        
        return false;
    }
}
