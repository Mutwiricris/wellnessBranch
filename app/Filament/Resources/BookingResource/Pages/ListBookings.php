<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Booking;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Booking')
                ->icon('heroicon-o-plus'),
            Actions\Action::make('calendar')
                ->label('Calendar View')
                ->icon('heroicon-o-calendar')
                ->url(fn (): string => BookingResource::getUrl('calendar'))
                ->color('gray'),
        ];
    }
    
    public function getTabs(): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        return [
            'all' => Tab::make('All Bookings')
                ->badge(Booking::where('branch_id', $tenant->id)->count()),
                
            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('appointment_date', today()))
                ->badge(Booking::where('branch_id', $tenant->id)->whereDate('appointment_date', today())->count())
                ->badgeColor('primary'),
                
            'upcoming' => Tab::make('Upcoming')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('appointment_date', '>', today()))
                ->badge(Booking::where('branch_id', $tenant->id)->where('appointment_date', '>', today())->count())
                ->badgeColor('success'),
                
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(Booking::where('branch_id', $tenant->id)->where('status', 'pending')->count())
                ->badgeColor('warning'),
                
            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed'))
                ->badge(Booking::where('branch_id', $tenant->id)->where('status', 'confirmed')->count())
                ->badgeColor('info'),
                
            'in_progress' => Tab::make('In Progress')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress'))
                ->badge(Booking::where('branch_id', $tenant->id)->where('status', 'in_progress')->count())
                ->badgeColor('primary'),
                
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(Booking::where('branch_id', $tenant->id)->where('status', 'completed')->count())
                ->badgeColor('success'),
                
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge(Booking::where('branch_id', $tenant->id)->where('status', 'cancelled')->count())
                ->badgeColor('danger'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            BookingResource\Widgets\BookingStatsWidget::class,
        ];
    }
}