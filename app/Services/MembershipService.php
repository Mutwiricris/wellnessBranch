<?php

namespace App\Services;

use App\Models\User;
use App\Models\Service;
use App\Models\ClientMembership;
use App\Models\ClientMembershipUsage;
use App\Models\Booking;

class MembershipService
{
    /**
     * Check if a client has an active membership that covers a specific service.
     */
    public function getActiveMembershipForService(User $user, Service $service): ?ClientMembership
    {
        $membership = $user->activeMembership();

        if (!$membership) {
            return null;
        }

        // Check if the service is included in the membership tier
        $tierService = $membership->membershipTier->services()
            ->where('service_id', $service->id)
            ->first();

        if (!$tierService) {
            return null;
        }

        // Check quota if not unlimited
        if (!$tierService->pivot->is_unlimited) {
            $usedCount = ClientMembershipUsage::where('client_membership_id', $membership->id)
                ->where('service_id', $service->id)
                ->where('used_at', '>=', $membership->start_date) // Assuming simple cycle for now
                ->count();

            if ($usedCount >= $tierService->pivot->allocated_quantity) {
                return null;
            }
        }

        return $membership;
    }

    /**
     * Calculate the discounted price for a service/product.
     */
    public function calculateDiscountedPrice(User $user, $item, string $type = 'service'): float
    {
        $membership = $user->activeMembership();
        $originalPrice = (float) $item->price;

        if (!$membership) {
            return $originalPrice;
        }

        $discountPercentage = 0;

        if ($type === 'service') {
            // Check if service is FULLY covered (100% discount)
            if ($this->getActiveMembershipForService($user, $item)) {
                return 0.00;
            }
            $discountPercentage = $membership->membershipTier->service_discount_percentage;
        } elseif ($type === 'product') {
            $discountPercentage = $membership->membershipTier->product_discount_percentage;
        }

        return $originalPrice * (1 - ($discountPercentage / 100));
    }

    /**
     * Record a payment for a membership.
     */
    public function recordPayment(ClientMembership $membership, float $amount, string $method, ?string $reference = null): \App\Models\Payment
    {
        return \App\Models\Payment::create([
            'branch_id' => $membership->branch_id,
            'customer_id' => $membership->user_id,
            'amount' => $amount,
            'payment_method' => $method,
            'payment_category' => 'membership',
            'transaction_reference' => $reference,
            'status' => 'completed',
            'processed_at' => now(),
            'notes' => "Membership Payment: {$membership->membershipTier->name}",
        ]);
    }

    /**
     * Record usage of a membership service.
     */
    public function recordUsage(ClientMembership $membership, Service $service, ?Booking $booking = null): ClientMembershipUsage
    {
        return ClientMembershipUsage::create([
            'client_membership_id' => $membership->id,
            'service_id' => $service->id,
            'booking_id' => $booking?->id,
            'used_at' => now(),
        ]);
    }
}
