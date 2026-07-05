<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Branch extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'address',
        'phone',
        'email',
        'working_hours',
        'timezone',
        'status',
        'payment_settings'
    ];

    protected $casts = [
        'working_hours' => 'array',
        'payment_settings' => 'array'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'branch_services')
                    ->withPivot('is_available', 'custom_price')
                    ->withTimestamps();
    }

    public function activeServices(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'branch_services')
                    ->withPivot('is_available', 'custom_price')
                    ->wherePivot('is_available', true)
                    ->where('services.status', 'active')
                    ->withTimestamps();
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'branch_staff')
                    ->withPivot('working_hours', 'is_primary_branch')
                    ->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_managers')
                    ->where('user_type', 'branch_manager')
                    ->withPivot('is_active')
                    ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Ecommerce relationships
    public function productInventory(): HasMany
    {
        return $this->hasMany(BranchProductInventory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(ProductStockMovement::class);
    }

    public function ecommerceOrders(): HasMany
    {
        return $this->hasMany(EcommerceOrder::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(EcommerceOrderItem::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(ProductTransfer::class, 'from_branch_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(ProductTransfer::class, 'to_branch_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    // POS and Marketing relationships
    public function discountCoupons(): HasMany
    {
        return $this->hasMany(DiscountCoupon::class);
    }

    public function giftVouchers(): HasMany
    {
        return $this->hasMany(GiftVoucher::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(
            Payment::class,
            Booking::class,
            'branch_id', // Foreign key on bookings table
            'booking_id', // Foreign key on payments table
            'id', // Local key on branches table
            'id' // Local key on bookings table
        );
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function posTransactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    public function commissionStructures(): HasMany
    {
        return $this->hasMany(CommissionStructure::class);
    }

    public function staffCommissions(): HasMany
    {
        return $this->hasMany(StaffCommission::class);
    }

    public function membershipTiers(): HasMany
    {
        return $this->hasMany(MembershipTier::class);
    }

    public function clientMemberships(): HasMany
    {
        return $this->hasMany(ClientMembership::class);
    }

    public function subscriptionPayments(): HasManyThrough
    {
        return $this->hasManyThrough(
            SubscriptionPayment::class,
            Subscription::class,
            'user_id', // Foreign key on subscriptions table
            'subscription_id', // Foreign key on subscription_payments table
            'user_id', // Local key on branches table
            'id' // Local key on subscriptions table
        );
    }

    // Helper methods for ecommerce
    public function getAvailableProductsCount()
    {
        return $this->productInventory()
                   ->where('is_available', true)
                   ->where('quantity_on_hand', '>', 0)
                   ->count();
    }

    public function getLowStockProductsCount()
    {
        return $this->productInventory()
                   ->whereRaw('quantity_on_hand <= reorder_level')
                   ->count();
    }

    public function getTotalInventoryValue()
    {
        return $this->productInventory()
                   ->join('products', 'branch_product_inventory.product_id', '=', 'products.id')
                   ->selectRaw('SUM(quantity_on_hand * cost_price) as total_value')
                   ->value('total_value') ?? 0;
    }
}