<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\AvailabilityService;
use App\Models\Service;
use Carbon\Carbon;

class TimeSlotSelector extends Component
{
    public $selectedDate;
    public $selectedTime;
    public $serviceId;
    public $branchId; 
    public $staffId;
    public $timeSlots = [];
    public $availableDates = [];
    
    protected AvailabilityService $availabilityService;
    
    public function boot(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }
    
    public function mount($serviceId = null, $branchId = null, $staffId = null)
    {
        $this->serviceId = $serviceId;
        $this->branchId = $branchId;
        $this->staffId = $staffId;
        
        if ($this->serviceId && $this->branchId) {
            $this->loadAvailableDates();
        }
    }
    
    public function updatedServiceId()
    {
        $this->reset(['selectedDate', 'selectedTime', 'timeSlots']);
        if ($this->serviceId && $this->branchId) {
            $this->loadAvailableDates();
        }
    }
    
    public function updatedStaffId()
    {
        $this->reset(['selectedTime', 'timeSlots']);
        if ($this->selectedDate) {
            $this->loadTimeSlots();
        }
    }
    
    public function updatedSelectedDate()
    {
        $this->reset(['selectedTime', 'timeSlots']);
        if ($this->selectedDate && $this->serviceId && $this->branchId) {
            $this->loadTimeSlots();
        }
    }
    
    public function loadAvailableDates()
    {
        if (!$this->serviceId || !$this->branchId) {
            return;
        }
        
        try {
            $this->availableDates = $this->availabilityService
                ->getAvailableDates($this->serviceId, $this->branchId, 30)
                ->toArray();
        } catch (\Exception $e) {
            $this->availableDates = [];
            session()->flash('error', 'Unable to load available dates.');
        }
    }
    
    public function loadTimeSlots()
    {
        if (!$this->selectedDate || !$this->serviceId || !$this->branchId) {
            return;
        }
        
        try {
            $this->timeSlots = $this->availabilityService
                ->getAvailableTimeSlots(
                    $this->selectedDate,
                    $this->serviceId,
                    $this->branchId,
                    $this->staffId
                )
                ->toArray();
                
            $this->dispatch('timeSlotsLoaded', $this->timeSlots);
        } catch (\Exception $e) {
            $this->timeSlots = [];
            session()->flash('error', 'Unable to load available time slots.');
        }
    }
    
    public function selectTimeSlot($time)
    {
        $this->selectedTime = $time;
        $this->dispatch('timeSlotSelected', [
            'date' => $this->selectedDate,
            'time' => $this->selectedTime
        ]);
    }
    
    public function render()
    {
        return view('livewire.time-slot-selector');
    }
}