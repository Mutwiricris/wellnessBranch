<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings
     */
    public function register(): void
    {
        // Booking repositories
        $this->app->bind(
            \App\Domain\Booking\Repositories\BookingRepositoryInterface::class,
            \App\Domain\Booking\Repositories\BookingRepository::class
        );

        // Payment repositories
        $this->app->bind(
            \App\Domain\Payment\Repositories\PaymentRepositoryInterface::class,
            \App\Domain\Payment\Repositories\PaymentRepository::class
        );

        // Staff repositories
        $this->app->bind(
            \App\Domain\Staff\Repositories\StaffRepositoryInterface::class,
            \App\Domain\Staff\Repositories\StaffRepository::class
        );

        $this->app->bind(
            \App\Domain\Staff\Repositories\StaffScheduleRepositoryInterface::class,
            \App\Domain\Staff\Repositories\StaffScheduleRepository::class
        );

        // Inventory repositories
        $this->app->bind(
            \App\Domain\Inventory\Repositories\ProductRepositoryInterface::class,
            \App\Domain\Inventory\Repositories\ProductRepository::class
        );

        $this->app->bind(
            \App\Domain\Inventory\Repositories\InventoryRepositoryInterface::class,
            \App\Domain\Inventory\Repositories\InventoryRepository::class
        );

        // POS repositories
        $this->app->bind(
            \App\Domain\POS\Repositories\PosTransactionRepositoryInterface::class,
            \App\Domain\POS\Repositories\PosTransactionRepository::class
        );
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}
