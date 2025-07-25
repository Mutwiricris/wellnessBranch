@php
    $isAvailable = $slot['available'] && !$slot['is_past'];
    $isPast = $slot['is_past'];
    $isBooked = !$slot['available'] && !$slot['is_past'];
    
    $baseClasses = 'relative p-3 rounded-lg border transition-all duration-200 text-center min-h-[80px] flex flex-col justify-center';
@endphp

<button
    type="button"
    class="{{ $baseClasses }}"
    data-time-slot="{{ $slot['time'] }}"
    data-available="{{ $isAvailable ? 'true' : 'false' }}"
    :class="{ 
        'bg-blue-500 text-white border-blue-600 shadow-lg scale-105': selectedTime === '{{ $slot['time'] }}',
        'bg-green-50 text-green-800 border-green-300 hover:bg-green-100 hover:border-green-400 hover:shadow-md cursor-pointer': {{ $isAvailable ? 'true' : 'false' }} && selectedTime !== '{{ $slot['time'] }}',
        'bg-red-50 text-red-400 border-red-200 cursor-not-allowed opacity-60': {{ $isPast ? 'true' : 'false' }},
        'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed opacity-60': {{ $isBooked ? 'true' : 'false' }}
    }"
    @if($isAvailable)
        @click="selectTime('{{ $slot['time'] }}'); if(window.updateFilamentField) { window.updateFilamentField('start_time', '{{ $slot['time'] }}'); }"
    @endif
    @if(!$isAvailable)
        disabled
    @endif
    title="{{ $slot['reason'] }}{{ $slot['staff_name'] && !$staffId ? ' - with ' . $slot['staff_name'] : '' }}"
>
    {{-- Time Display --}}
    <div class="font-semibold text-sm">
        {{ $slot['formatted_time'] }}
    </div>
    
    {{-- Staff Name (if auto-assignment and available) --}}
    @if(!$staffId && $slot['staff_name'] && $isAvailable)
        <div class="text-xs mt-1 opacity-75 truncate">
            {{ $slot['staff_name'] }}
        </div>
    @endif
    
    {{-- Status Icon --}}
    <div class="absolute top-1 right-1">
        <svg x-show="selectedTime === '{{ $slot['time'] }}'" class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>
        @if($isPast)
            <svg x-show="selectedTime !== '{{ $slot['time'] }}'" class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
        @elseif($isBooked)
            <svg x-show="selectedTime !== '{{ $slot['time'] }}'" class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
            </svg>
        @endif
    </div>
    
    {{-- Status Label --}}
    @if(!$isAvailable)
        <div class="text-xs mt-1 font-medium">
            @if($isPast)
                Past
            @else
                Booked
            @endif
        </div>
    @endif
</button>