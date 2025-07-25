<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use App\Models\Booking;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class CalendarBookings extends Page
{
    protected static string $resource = BookingResource::class;

    protected static string $view = 'filament.resources.booking-resource.pages.calendar-bookings';
    
    protected static ?string $title = 'Appointment Calendar';
    
    protected static ?string $navigationLabel = 'Calendar';
    
    public $selectedDate;
    public $selectedStaff = null;
    public $selectedStatus = null;
    
    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Booking')
                ->icon('heroicon-o-plus')
                ->url(fn (): string => BookingResource::getUrl('create')),
            Actions\Action::make('list_view')
                ->label('List View')
                ->icon('heroicon-o-list-bullet')
                ->url(fn (): string => BookingResource::getUrl('index'))
                ->color('gray'),
        ];
    }
    
    public function getCalendarEvents(): array
    {
        try {
            $tenant = \Filament\Facades\Filament::getTenant();
            
            if (!$tenant) {
                return [];
            }
            
            $startDate = Carbon::parse($this->selectedDate)->startOfMonth()->startOfWeek();
            $endDate = Carbon::parse($this->selectedDate)->endOfMonth()->endOfWeek();
            
            $query = Booking::with(['client', 'service', 'staff'])
                ->where('branch_id', $tenant->id)
                ->whereBetween('appointment_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                
            if ($this->selectedStaff) {
                $query->where('staff_id', $this->selectedStaff);
            }
            
            if ($this->selectedStatus) {
                $query->where('status', $this->selectedStatus);
            }
            
            $bookings = $query->get();
            
            return $bookings->map(function ($booking) {
                // Handle appointment_date properly - it could be a Carbon instance or string
                $appointmentDate = $booking->appointment_date instanceof Carbon 
                    ? $booking->appointment_date->format('Y-m-d')
                    : $booking->appointment_date;
                    
                // Use staff color if available, otherwise use status color
                $backgroundColor = $booking->staff && $booking->staff->color 
                    ? $booking->staff->color 
                    : $this->getStatusColor($booking->status);
                    
                // Make border slightly darker for better visibility
                $borderColor = $this->darkenColor($backgroundColor, 20);
                
                return [
                    'id' => $booking->id,
                    'title' => ($booking->client->first_name ?? '') . ' ' . ($booking->client->last_name ?? ''),
                    'subtitle' => $booking->service->name ?? 'No Service',
                    'start' => $appointmentDate . 'T' . $booking->start_time . ':00',
                    'end' => $appointmentDate . 'T' . $booking->end_time . ':00',
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $borderColor,
                    'textColor' => $this->getContrastColor($backgroundColor),
                    'status' => $booking->status,
                    'staff' => $booking->staff?->name ?? 'Unassigned',
                    'staff_color' => $booking->staff?->color ?? null,
                    'client' => ($booking->client->first_name ?? '') . ' ' . ($booking->client->last_name ?? ''),
                    'service' => $booking->service->name ?? 'No Service',
                    'notes' => $booking->notes ?? '',
                    'total_amount' => $booking->total_amount ?? 0,
                    'payment_status' => $booking->payment_status,
                    'booking_reference' => $booking->booking_reference,
                    'url' => BookingResource::getUrl('view', ['record' => $booking]),
                ];
            })->toArray();
        } catch (\Exception $e) {
            \Log::error('Calendar events error: ' . $e->getMessage());
            return [];
        }
    }
    
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => '#f59e0b',
            'confirmed' => '#3b82f6',
            'in_progress' => '#8b5cf6',
            'completed' => '#10b981',
            'cancelled' => '#ef4444',
            'no_show' => '#6b7280',
            default => '#6b7280'
        };
    }
    
    public function getStaffOptions(): array
    {
        try {
            $tenant = \Filament\Facades\Filament::getTenant();
            
            if (!$tenant) {
                return [];
            }
            
            return Staff::whereHas('branches', function (Builder $query) use ($tenant) {
                $query->where('branch_id', $tenant->id);
            })
            ->where('status', 'active')
            ->pluck('name', 'id')
            ->toArray();
        } catch (\Exception $e) {
            \Log::error('Staff options error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getTodayStats(): array
    {
        try {
            $tenant = \Filament\Facades\Filament::getTenant();
            
            if (!$tenant) {
                return $this->getEmptyStats();
            }
            
            $today = Carbon::parse($this->selectedDate);
            
            $todayBookings = Booking::where('branch_id', $tenant->id)
                ->whereDate('appointment_date', $today);
                
            return [
                'total' => $todayBookings->count(),
                'pending' => $todayBookings->clone()->where('status', 'pending')->count(),
                'confirmed' => $todayBookings->clone()->where('status', 'confirmed')->count(),
                'in_progress' => $todayBookings->clone()->where('status', 'in_progress')->count(),
                'completed' => $todayBookings->clone()->where('status', 'completed')->count(),
                'cancelled' => $todayBookings->clone()->where('status', 'cancelled')->count(),
                'revenue' => $todayBookings->clone()->where('payment_status', 'completed')->sum('total_amount') ?? 0,
            ];
        } catch (\Exception $e) {
            \Log::error('Calendar stats error: ' . $e->getMessage());
            return $this->getEmptyStats();
        }
    }
    
    private function getEmptyStats(): array
    {
        return [
            'total' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'revenue' => 0,
        ];
    }
    
    public function updatedSelectedDate(): void
    {
        // Trigger calendar refresh when date changes
        $this->dispatch('refresh-calendar');
    }
    
    public function updatedSelectedStaff(): void
    {
        // Trigger calendar refresh when staff filter changes
        $this->dispatch('refresh-calendar');
    }
    
    public function updatedSelectedStatus(): void
    {
        // Trigger calendar refresh when status filter changes
        $this->dispatch('refresh-calendar');
    }
    
    private function darkenColor($hex, $percent)
    {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Darken
        $r = max(0, $r - ($r * $percent / 100));
        $g = max(0, $g - ($g * $percent / 100));
        $b = max(0, $b - ($b * $percent / 100));
        
        // Convert back to hex
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
    
    private function getContrastColor($hex)
    {
        // Remove # if present
        $hex = ltrim($hex, '#');
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Calculate luminance
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        
        // Return white for dark backgrounds, black for light backgrounds
        return $luminance > 0.5 ? '#000000' : '#FFFFFF';
    }
}