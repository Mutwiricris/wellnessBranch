<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Closure;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;
    
    protected static bool $canCreateAnother = false;
    
    protected AvailabilityService $availabilityService;
    
    public function boot(AvailabilityService $availabilityService): void
    {
        $this->availabilityService = $availabilityService;
    }
    
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Service Selection')
                        ->icon('heroicon-o-sparkles')
                        ->description('Choose the service for this booking')
                        ->completedIcon('heroicon-o-check')
                        ->afterValidation(function (array $state) {
                            // Validate service selection step
                            if (empty($state['service_id'])) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    'service_id' => 'Please select a service before continuing.'
                                ]);
                            }
                            
                            Log::info('✓ Service Selection step validated successfully', [
                                'service_id' => $state['service_id']
                            ]);
                        })
                        ->schema([
                            Forms\Components\Grid::make(1)
                                ->schema([
                                    Forms\Components\Select::make('service_id')
                                        ->label('Select Service')
                                        ->options(function () {
                                            $tenant = \Filament\Facades\Filament::getTenant();
                                            return Service::whereHas('branches', function (Builder $query) use ($tenant) {
                                                $query->where('branch_id', $tenant?->id);
                                            })
                                            ->with('category')
                                            ->get()
                                            ->groupBy('category.name')
                                            ->map(fn ($services) => $services->pluck('name', 'id'))
                                            ->toArray();
                                        })
                                        ->required()
                                        ->reactive()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                $service = Service::find($state);
                                                if ($service) {
                                                    $set('total_amount', $service->price);
                                                }
                                                
                                                // Clear time selection when service changes
                                                $set('start_time', null);
                                            }
                                        })
                                        ->native(false)
                                        ->searchable()
                                        ->preload(),
                                        
                                    Forms\Components\Placeholder::make('service_info')
                                        ->label('Service Information')
                                        ->content(function (Forms\Get $get): string {
                                            $serviceId = $get('service_id');
                                            if (!$serviceId) {
                                                return 'Please select a service to see details.';
                                            }
                                            
                                            $service = Service::find($serviceId);
                                            if (!$service) {
                                                return 'Service not found.';
                                            }
                                            
                                            return sprintf(
                                                "**%s**\n\n%s\n\n**Duration:** %d minutes\n**Price:** KES %s",
                                                $service->name,
                                                $service->description ?? 'No description available',
                                                $service->duration ?? 60,
                                                number_format((float) $service->price, 2)
                                            );
                                        })
                                        ->hidden(fn (Forms\Get $get): bool => !$get('service_id')),
                                ])
                        ]),
                        
                    Step::make('Staff Selection')
                        ->icon('heroicon-o-user-group')
                        ->description('Choose a staff member (optional)')
                        ->completedIcon('heroicon-o-check')
                        ->afterValidation(function (array $state) {
                            // Staff selection is optional, so just validate that service is still selected
                            if (empty($state['service_id'])) {
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    'service_id' => 'Service selection was lost. Please go back to step 1.'
                                ]);
                            }
                            
                            Log::info('✓ Staff Selection step validated successfully', [
                                'service_id' => $state['service_id'],
                                'staff_id' => $state['staff_id'] ?? 'auto-assign'
                            ]);
                        })
                        ->schema([
                            Forms\Components\Grid::make(1)
                                ->schema([
                                    Forms\Components\Select::make('staff_id')
                                        ->label('Preferred Staff Member')
                                        ->placeholder('Any available staff member')
                                        ->options(function (Forms\Get $get) {
                                            $tenant = \Filament\Facades\Filament::getTenant();
                                            $serviceId = $get('service_id');
                                            
                                            if (!$serviceId || !$tenant) {
                                                return [];
                                            }
                                            
                                            // Always show ALL qualified staff regardless of current availability
                                            // Individual time slots will show as available/booked, not the staff members
                                            $staff = Staff::where('status', 'active')
                                                ->whereHas('branches', function (Builder $query) use ($tenant) {
                                                    $query->where('branch_id', $tenant->id);
                                                })
                                                ->whereHas('services', function (Builder $query) use ($serviceId) {
                                                    $query->where('service_id', $serviceId);
                                                })
                                                ->orderBy('name')
                                                ->get();
                                            
                                            $options = [];
                                            foreach ($staff as $member) {
                                                $label = $member->name;
                                                if ($member->specialization && $member->specialization !== 'General') {
                                                    $label .= ' - ' . $member->specialization;
                                                }
                                                $options[$member->id] = $label;
                                            }
                                            
                                            return $options;
                                        })
                                        ->reactive()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            // Clear time selection when staff changes to refresh availability
                                            if ($state !== null) {
                                                $set('start_time', null);
                                            }
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->helperText('Select a specific staff member or leave empty for automatic assignment based on your chosen time slot.'),
                                        
                                    Forms\Components\Placeholder::make('staff_note')
                                        ->label('')
                                        ->content('💡 **Tip:** All qualified staff members are available for selection. Individual time slots will show as available or booked for your chosen staff member and date.')
                                ])
                        ]),
                        
                    Step::make('Date & Time')
                        ->icon('heroicon-o-calendar-days')
                        ->description('Select appointment date and time')
                        ->completedIcon('heroicon-o-check')
                        ->afterValidation(function (array $state) {
                            // Validate date and time selection
                            $errors = [];
                            
                            if (empty($state['service_id'])) {
                                $errors['service_id'] = 'Service selection was lost. Please go back to step 1.';
                            }
                            
                            if (empty($state['appointment_date'])) {
                                $errors['appointment_date'] = 'Please select an appointment date.';
                            }
                            
                            if (empty($state['start_time'])) {
                                $errors['start_time'] = 'Please select a time slot from the available options.';
                            }
                            
                            // Validate appointment date is not in the past
                            if (!empty($state['appointment_date'])) {
                                try {
                                    $appointmentDate = $this->parseAppointmentDate($state['appointment_date']);
                                    $today = \Carbon\Carbon::now('Africa/Nairobi')->startOfDay();
                                    
                                    if ($appointmentDate->lt($today)) {
                                        $errors['appointment_date'] = 'Please select a current or future date.';
                                    }
                                } catch (\Exception $e) {
                                    $errors['appointment_date'] = 'Invalid date format. Please select a valid date.';
                                }
                            }
                            
                            // Validate time slot availability if all required fields are present
                            if (empty($errors) && !empty($state['start_time']) && !empty($state['service_id'])) {
                                try {
                                    $tenant = \Filament\Facades\Filament::getTenant();
                                    $availabilityService = app(AvailabilityService::class);
                                    
                                    $available = $availabilityService->isSpecificTimeSlotAvailable(
                                        $state['appointment_date'],
                                        $state['start_time'],
                                        $state['service_id'],
                                        $tenant?->id,
                                        $state['staff_id'] ?? null
                                    );
                                    
                                    if (!$available) {
                                        $errors['start_time'] = 'The selected time slot is no longer available. Please choose a different time.';
                                    }
                                } catch (\Exception $e) {
                                    Log::warning('Time slot validation failed', ['error' => $e->getMessage()]);
                                    // Don't block progression for availability check errors in step validation
                                }
                            }
                            
                            if (!empty($errors)) {
                                throw \Illuminate\Validation\ValidationException::withMessages($errors);
                            }
                            
                            Log::info('✓ Date & Time step validated successfully', [
                                'appointment_date' => $state['appointment_date'],
                                'start_time' => $state['start_time']
                            ]);
                        })
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('appointment_date')
                                        ->label('Appointment Date')
                                        ->required()
                                        ->minDate(now('Africa/Nairobi')->toDateString())
                                        ->maxDate(now('Africa/Nairobi')->addDays(60)->toDateString())
                                        ->timezone('Africa/Nairobi')
                                        ->reactive()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            // Clear time when date changes
                                            if ($state !== null) {
                                                $set('start_time', null);
                                                
                                                // Trigger a refresh by updating a dummy field
                                                $set('time_slot_grid', time());
                                            }
                                        })
                                        ->helperText('Select a date within the next 60 days (East Africa Time)')
                                        ->displayFormat('Y-m-d')
                                        ->format('Y-m-d'),
                                        
                                    Forms\Components\ViewField::make('time_slot_grid')
                                        ->label('Select Time Slot')
                                        ->view('filament.forms.time-slot-grid')
                                        ->reactive()
                                        ->live()
                                        ->dehydrated(false) // Don't include in form data
                                        ->viewData(function (Forms\Get $get): array {
                                            $date = $get('appointment_date');
                                            $serviceId = $get('service_id');
                                            $staffId = $get('staff_id');
                                            $selectedTime = $get('start_time');
                                            
                                            // Debug logging for troubleshooting
                                            Log::debug('Time slot grid view data generation', [
                                                'date' => $date,
                                                'service_id' => $serviceId,
                                                'staff_id' => $staffId,
                                                'selected_time' => $selectedTime
                                            ]);
                                            
                                            // Ensure we have proper values
                                            $date = $date ?: $get('appointment_date');
                                            $serviceId = $serviceId ?: $get('service_id');
                                            
                                            if (empty($date) || empty($serviceId)) {
                                                return [
                                                    'timeSlots' => [],
                                                    'selectedTime' => $selectedTime,
                                                    'date' => $date,
                                                    'message' => 'Please select a date and service first.',
                                                    'debug' => "Date: {$date}, Service: {$serviceId}",
                                                    'formFieldId' => 'start_time_field' // Help Alpine find the field
                                                ];
                                            }
                                            
                                            try {
                                                $tenant = \Filament\Facades\Filament::getTenant();
                                                
                                                if (!$tenant) {
                                                    return [
                                                        'timeSlots' => [],
                                                        'selectedTime' => null,
                                                        'message' => 'Unable to determine branch context. Please refresh the page.'
                                                    ];
                                                }
                                                
                                                $availabilityService = app(AvailabilityService::class);
                                                
                                                $timeSlots = $availabilityService->getAvailableTimeSlots(
                                                    $date,
                                                    $serviceId,
                                                    $tenant->id,
                                                    $staffId
                                                );
                                                
                                                if ($timeSlots->isEmpty()) {
                                                    return [
                                                        'timeSlots' => [],
                                                        'selectedTime' => $selectedTime,
                                                        'date' => $date,
                                                        'staffId' => $staffId,
                                                        'message' => 'No time slots are available for the selected date and service. Please try a different date.'
                                                    ];
                                                }
                                                
                                                return [
                                                    'timeSlots' => $timeSlots->toArray(),
                                                    'selectedTime' => $selectedTime,
                                                    'date' => $date,
                                                    'staffId' => $staffId,
                                                    'message' => null
                                                ];
                                            } catch (\Exception $e) {
                                                // Log the error for debugging
                                                Log::error('Time slot loading error', [
                                                    'error' => $e->getMessage(),
                                                    'date' => $date,
                                                    'serviceId' => $serviceId,
                                                    'staffId' => $staffId
                                                ]);
                                                
                                                return [
                                                    'timeSlots' => [],
                                                    'selectedTime' => null,
                                                    'message' => 'Unable to load time slots due to a system error. Please refresh and try again.'
                                                ];
                                            }
                                        })
                                        ->columnSpanFull(),
                                        
                                    Forms\Components\Hidden::make('start_time')
                                        ->required()
                                        ->reactive()
                                        ->live()
                                        ->dehydrated()
                                        ->extraAttributes([
                                            'data-field' => 'start_time',
                                            'id' => 'start_time_field',
                                            'class' => 'filament-start-time-field',
                                            'x-model' => 'startTimeValue', // Alpine.js binding
                                            'x-init' => 'startTimeValue = $el.value', // Initialize Alpine value
                                        ])
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                Log::info('✅ start_time field updated successfully', [
                                                    'value' => $state,
                                                    'timestamp' => now()->toISOString()
                                                ]);
                                            } else {
                                                Log::warning('⚠️ start_time field cleared or empty', [
                                                    'timestamp' => now()->toISOString()
                                                ]);
                                            }
                                        })
                                        ->validationMessages([
                                            'required' => 'Please select a time slot from the available options above.',
                                        ])
                                        ->rules([
                                            'required',
                                            'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', // Time format validation
                                        ]),
                                        
                                    Forms\Components\Placeholder::make('availability_check')
                                        ->label('Availability')
                                        ->content(function (Forms\Get $get): string {
                                            $date = $get('appointment_date');
                                            $time = $get('start_time');
                                            $serviceId = $get('service_id');
                                            $staffId = $get('staff_id');
                                            
                                            if (!$date || !$time || !$serviceId) {
                                                return '⏳ Please select date, time, and service to check availability.';
                                            }
                                            
                                            try {
                                                $tenant = \Filament\Facades\Filament::getTenant();
                                                $availabilityService = app(AvailabilityService::class);
                                                
                                                $available = $availabilityService->isSpecificTimeSlotAvailable(
                                                    $date,
                                                    $time, 
                                                    $serviceId,
                                                    $tenant?->id,
                                                    $staffId
                                                );
                                                
                                                if ($available) {
                                                    return '✅ **Time slot available** - You can proceed with this booking.';
                                                } else {
                                                    return '❌ **Time slot unavailable** - Please choose a different time.';
                                                }
                                            } catch (\Exception) {
                                                return '⚠️ **Unable to check availability** - Please verify your selection.';
                                            }
                                        })
                                        ->columnSpanFull(),
                                ])
                        ]),
                        
                    Step::make('Client Information')
                        ->icon('heroicon-o-user')
                        ->description('Enter client details')
                        ->completedIcon('heroicon-o-check')
                        ->afterValidation(function (array $state) {
                            // Validate client information step
                            $errors = [];
                            
                            // Check if either existing client is selected or new client info is provided
                            $hasExistingClient = !empty($state['client_id']);
                            $hasNewClientInfo = !empty($state['new_client_first_name']) || 
                                              !empty($state['new_client_last_name']) || 
                                              !empty($state['new_client_email']);
                            
                            if (!$hasExistingClient && !$hasNewClientInfo) {
                                $errors['client_id'] = 'Please select an existing client or provide new client information.';
                            }
                            
                            // If creating new client, validate required fields
                            if (!$hasExistingClient && $hasNewClientInfo) {
                                $requiredNewClientFields = [
                                    'new_client_first_name' => 'First name is required for new clients.',
                                    'new_client_last_name' => 'Last name is required for new clients.',
                                    'new_client_email' => 'Email is required for new clients.',
                                    'new_client_phone' => 'Phone number is required for new clients.'
                                ];
                                
                                foreach ($requiredNewClientFields as $field => $message) {
                                    if (empty($state[$field])) {
                                        $errors[$field] = $message;
                                    }
                                }
                                
                                // Validate email format
                                if (!empty($state['new_client_email']) && !filter_var($state['new_client_email'], FILTER_VALIDATE_EMAIL)) {
                                    $errors['new_client_email'] = 'Please enter a valid email address.';
                                }
                                
                                // Validate phone format
                                if (!empty($state['new_client_phone']) && !$this->isValidKenyanPhone($state['new_client_phone'])) {
                                    $errors['new_client_phone'] = 'Please enter a valid Kenyan phone number.';
                                }
                            }
                            
                            if (!empty($errors)) {
                                throw \Illuminate\Validation\ValidationException::withMessages($errors);
                            }
                            
                            Log::info('✓ Client Information step validated successfully', [
                                'has_existing_client' => $hasExistingClient,
                                'has_new_client_info' => $hasNewClientInfo,
                                'client_id' => $state['client_id'] ?? null
                            ]);
                        })
                        ->schema([
                            Forms\Components\Select::make('client_id')
                                ->label('Existing Client')
                                ->relationship('client', 'first_name')
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->email})")
                                ->searchable(['first_name', 'last_name', 'email', 'phone'])
                                ->preload()
                                ->native(false)
                                ->reactive()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        // Clear new client fields when existing client is selected
                                        $set('new_client_first_name', null);
                                        $set('new_client_last_name', null);
                                        $set('new_client_email', null);
                                        $set('new_client_phone', null);
                                        $set('new_client_gender', null);
                                        $set('new_client_allergies', null);
                                    }
                                })
                                ->helperText('Search for an existing client or leave empty to create a new one'),
                                
                            Forms\Components\Placeholder::make('client_selection_info')
                                ->label('Client Selection Status')
                                ->content(function (Forms\Get $get): string {
                                    $clientId = $get('client_id');
                                    
                                    if ($clientId) {
                                        $client = User::find($clientId);
                                        if ($client) {
                                            return sprintf(
                                                "✅ **Selected Client:** %s (%s)",
                                                $client->first_name . ' ' . $client->last_name,
                                                $client->email
                                            );
                                        }
                                    }
                                    
                                    $firstName = $get('new_client_first_name');
                                    $lastName = $get('new_client_last_name');
                                    $email = $get('new_client_email');
                                    
                                    if ($firstName || $lastName || $email) {
                                        return sprintf(
                                            "📝 **New Client Data:**\nName: %s %s\nEmail: %s",
                                            $firstName ?: '[Not entered]',
                                            $lastName ?: '[Not entered]',
                                            $email ?: '[Not entered]'
                                        );
                                    }
                                    
                                    return '⏳ Please select an existing client or enter new client details below.';
                                })
                                ->columnSpanFull(),

                            Forms\Components\Fieldset::make('New Client Information')
                                ->hidden(fn (Forms\Get $get): bool => (bool) $get('client_id'))
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('new_client_first_name')
                                                ->label('First Name')
                                                ->required(fn (Forms\Get $get): bool => !$get('client_id'))
                                                ->maxLength(255)
                                                ->reactive()
                                                ->live()
                                                ->dehydrated() // Ensure field is included in form data
                                                ->rules([
                                                    fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                        if (!$get('client_id') && empty($value)) {
                                                            $fail('First name is required when creating a new client.');
                                                        }
                                                    },
                                                ]),
                                                
                                            Forms\Components\TextInput::make('new_client_last_name')
                                                ->label('Last Name')
                                                ->required(fn (Forms\Get $get): bool => !$get('client_id'))
                                                ->maxLength(255)
                                                ->reactive()
                                                ->live()
                                                ->dehydrated() // Ensure field is included in form data
                                                ->rules([
                                                    fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                        if (!$get('client_id') && empty($value)) {
                                                            $fail('Last name is required when creating a new client.');
                                                        }
                                                    },
                                                ]),
                                                
                                            Forms\Components\TextInput::make('new_client_email')
                                                ->label('Email')
                                                ->email()
                                                ->required(fn (Forms\Get $get): bool => !$get('client_id'))
                                                ->maxLength(255)
                                                ->reactive()
                                                ->live()
                                                ->dehydrated() // Ensure field is included in form data
                                                ->rules([
                                                    fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                        if (!$get('client_id') && empty($value)) {
                                                            $fail('Email is required when creating a new client.');
                                                        }
                                                        if (!$get('client_id') && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                                            $fail('Please enter a valid email address.');
                                                        }
                                                    },
                                                ]),
                                                
                                            Forms\Components\TextInput::make('new_client_phone')
                                                ->label('Phone Number')
                                                ->tel()
                                                ->required(fn (Forms\Get $get): bool => !$get('client_id'))
                                                ->maxLength(20)
                                                ->reactive()
                                                ->live()
                                                ->dehydrated() // Ensure field is included in form data
                                                ->placeholder('0712345678 or +254712345678')
                                                ->helperText('Formats accepted: 0712345678, +254712345678, or 254712345678')
                                                ->prefix('📱')
                                                ->rules([
                                                    fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                        if (!$get('client_id') && empty($value)) {
                                                            $fail('Phone number is required when creating a new client.');
                                                            return;
                                                        }
                                                        
                                                        if (!$get('client_id') && $value) {
                                                            // Validate Kenyan phone number format
                                                            if (!$this->isValidKenyanPhone($value)) {
                                                                $fail('Please enter a valid Kenyan phone number (e.g., 0712345678 or +254712345678).');
                                                            }
                                                        }
                                                    },
                                                ]),
                                                
                                            Forms\Components\Select::make('new_client_gender')
                                                ->label('Gender')
                                                ->options([
                                                    'male' => 'Male',
                                                    'female' => 'Female',
                                                    'other' => 'Other',
                                                    'prefer_not_to_say' => 'Prefer not to say'
                                                ])
                                                ->native(false)
                                                ->dehydrated(), // Ensure field is included in form data
                                                
                                            Forms\Components\DatePicker::make('new_client_date_of_birth')
                                                ->label('Date of Birth')
                                                ->maxDate(now())
                                                ->dehydrated(), // Ensure field is included in form data
                                                
                                            Forms\Components\Textarea::make('new_client_allergies')
                                                ->label('Allergies & Medical Notes')
                                                ->rows(2)
                                                ->columnSpanFull()
                                                ->placeholder('Any allergies or medical conditions we should know about...')
                                                ->dehydrated(), // Ensure field is included in form data
                                        ])
                                ]),
                        ]),
                        
                    Step::make('Booking Details')
                        ->icon('heroicon-o-document-check')
                        ->description('Finalize booking details')
                        ->completedIcon('heroicon-o-check')
                        ->afterValidation(function (array $state) {
                            // Final validation before submission
                            $errors = [];
                            
                            // Validate all required fields are still present
                            $requiredFields = [
                                'service_id' => 'Service selection is required.',
                                'appointment_date' => 'Appointment date is required.',
                                'start_time' => 'Time slot selection is required.',
                                'status' => 'Booking status is required.',
                                'payment_method' => 'Payment method is required.',
                                'total_amount' => 'Total amount is required.'
                            ];
                            
                            foreach ($requiredFields as $field => $message) {
                                if (empty($state[$field])) {
                                    $errors[$field] = $message;
                                }
                            }
                            
                            // Validate client information is complete
                            $hasClient = !empty($state['client_id']);
                            $hasNewClient = !empty($state['new_client_first_name']) && 
                                          !empty($state['new_client_last_name']) && 
                                          !empty($state['new_client_email']) && 
                                          !empty($state['new_client_phone']);
                            
                            if (!$hasClient && !$hasNewClient) {
                                $errors['client_info'] = 'Complete client information is required to finalize the booking.';
                            }
                            
                            if (!empty($errors)) {
                                throw \Illuminate\Validation\ValidationException::withMessages($errors);
                            }
                            
                            Log::info('✓ Final booking validation completed successfully', [
                                'all_fields_present' => true,
                                'ready_for_submission' => true
                            ]);
                        })
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('status')
                                        ->label('Booking Status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'confirmed' => 'Confirmed',
                                        ])
                                        ->default('confirmed')
                                        ->required()
                                        ->native(false)
                                        ->helperText('Branch bookings are usually confirmed immediately'),
                                        
                                    Forms\Components\TextInput::make('total_amount')
                                        ->label('Total Amount')
                                        ->numeric()
                                        ->prefix('KES')
                                        ->step(0.01)
                                        ->required()
                                        ->disabled()
                                        ->dehydrated(),
                                        
                                    Forms\Components\Select::make('payment_method')
                                        ->label('Payment Method')
                                        ->options([
                                            'cash' => 'Cash',
                                            'mpesa' => 'M-Pesa',
                                            'card' => 'Card',
                                            'bank_transfer' => 'Bank Transfer'
                                        ])
                                        ->default('cash')
                                        ->native(false)
                                        ->required(),
                                        
                                    Forms\Components\Select::make('payment_status')
                                        ->label('Payment Status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'completed' => 'Completed',
                                        ])
                                        ->default('pending')
                                        ->required()
                                        ->native(false),
                                        
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Booking Notes')
                                        ->rows(3)
                                        ->columnSpanFull()
                                        ->placeholder('Any special requests or notes for this booking...'),
                                        
                                    Forms\Components\Placeholder::make('final_check')
                                        ->label('Pre-submission Validation')
                                        ->content(function (Forms\Get $get): string {
                                            $checks = [
                                                'Service Selected' => $get('service_id') ? '✅' : '❌',
                                                'Date Selected' => $get('appointment_date') ? '✅' : '❌',
                                                'Time Selected' => $get('start_time') ? '✅' : '❌',
                                                'Client Info' => ($get('client_id') || $get('new_client_first_name')) ? '✅' : '❌',
                                                'Payment Method' => $get('payment_method') ? '✅' : '❌',
                                                'Total Amount' => $get('total_amount') ? '✅' : '❌'
                                            ];
                                            
                                            $result = "**Pre-submission Checklist:**\n\n";
                                            foreach ($checks as $item => $status) {
                                                $result .= "• $item: $status\n";
                                            }
                                            
                                            $allGood = !in_array('❌', $checks);
                                            if ($allGood) {
                                                $result .= "\n🎉 **All requirements met! Ready to submit.**";
                                            } else {
                                                $result .= "\n⚠️ **Please complete missing requirements above.**";
                                            }
                                            
                                            return $result;
                                        })
                                        ->columnSpanFull(),
                                ])
                        ]),
                ])
                ->columnSpanFull()
                ->persistStepInQueryString()
                ->skippable(false) // Ensure all steps must be completed
            ]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            // Debug: Log the incoming data
            Log::info('=== BOOKING SUBMISSION STARTED ===', [
                'timestamp' => now()->toISOString(),
                'user_id' => auth()->id(),
                'data_keys' => array_keys($data),
                'data_summary' => [
                    'service_id' => $data['service_id'] ?? 'missing',
                    'staff_id' => $data['staff_id'] ?? 'missing', 
                    'appointment_date' => $data['appointment_date'] ?? 'missing',
                    'start_time' => $data['start_time'] ?? 'missing',
                    'client_id' => $data['client_id'] ?? 'missing',
                    'new_client_first_name' => $data['new_client_first_name'] ?? 'missing',
                    'status' => $data['status'] ?? 'missing',
                    'payment_method' => $data['payment_method'] ?? 'missing',
                    'total_amount' => $data['total_amount'] ?? 'missing'
                ],
                'full_data' => $data
            ]);
            
            $tenant = \Filament\Facades\Filament::getTenant();
            
            if (!$tenant) {
                throw ValidationException::withMessages([
                    'branch' => 'Unable to determine branch context. Please refresh and try again.'
                ]);
            }
            
            $data['branch_id'] = $tenant->id;
            Log::info('✓ Tenant validation passed', ['tenant_id' => $tenant->id]);
            
            // Validate required fields with user-friendly messages
            Log::info('Starting field validation...');
            $this->validateRequiredFields($data);
            Log::info('✓ Field validation passed');
            
            // Auto-assign staff if none selected
            if (empty($data['staff_id'])) {
                Log::info('Auto-assigning staff...');
                $data['staff_id'] = $this->autoAssignStaff($data, $tenant);
                Log::info('✓ Staff auto-assigned', ['staff_id' => $data['staff_id']]);
            } else {
                Log::info('✓ Staff already selected', ['staff_id' => $data['staff_id']]);
            }
            
            // Final availability check before creating booking
            Log::info('Checking time slot availability...');
            $this->validateTimeSlotAvailability($data, $tenant);
            Log::info('✓ Time slot availability confirmed');
            
            // Calculate end_time based on service duration
            Log::info('Calculating end time...');
            $data = $this->calculateEndTime($data);
            Log::info('✓ End time calculated', ['end_time' => $data['end_time'] ?? 'not set']);
            
            // Handle new client creation
            if (empty($data['client_id'])) {
                Log::info('No existing client selected, checking for new client data', [
                    'has_new_client_first_name' => isset($data['new_client_first_name']),
                    'new_client_first_name' => $data['new_client_first_name'] ?? 'not set',
                    'new_client_email' => $data['new_client_email'] ?? 'not set'
                ]);
                
                if (isset($data['new_client_first_name']) && !empty($data['new_client_first_name'])) {
                    $data['client_id'] = $this->createNewClient($data);
                    Log::info('New client created with ID: ' . $data['client_id']);
                } else {
                    Log::error('No client selected and no new client data provided');
                    throw ValidationException::withMessages([
                        'client_id' => 'Please select an existing client or provide new client information.',
                        'new_client_first_name' => 'First name is required for new client.'
                    ]);
                }
            }
            
            // Clean up new client fields from data
            Log::info('Cleaning up client fields...');
            $data = $this->cleanupClientFields($data);
            Log::info('✓ Client fields cleaned up');
            
            // Generate booking reference if not provided
            if (!isset($data['booking_reference'])) {
                $data['booking_reference'] = 'SPA-' . strtoupper(Str::random(8));
                Log::info('✓ Booking reference generated', ['reference' => $data['booking_reference']]);
            }
            
            // Final data validation
            Log::info('=== FINAL BOOKING DATA ===', [
                'final_data' => $data,
                'required_fields_check' => [
                    'service_id' => isset($data['service_id']) ? '✓' : '✗',
                    'staff_id' => isset($data['staff_id']) ? '✓' : '✗',
                    'client_id' => isset($data['client_id']) ? '✓' : '✗',
                    'appointment_date' => isset($data['appointment_date']) ? '✓' : '✗',
                    'start_time' => isset($data['start_time']) ? '✓' : '✗',
                    'end_time' => isset($data['end_time']) ? '✓' : '✗',
                    'branch_id' => isset($data['branch_id']) ? '✓' : '✗',
                    'booking_reference' => isset($data['booking_reference']) ? '✓' : '✗'
                ]
            ]);
            
            // Success notification
            Notification::make()
                ->title('Booking validation successful')
                ->body('All booking details have been validated successfully.')
                ->success()
                ->send();
            
            Log::info('=== BOOKING VALIDATION COMPLETED SUCCESSFULLY ===');
            return $data;
            
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Booking creation error', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Booking Creation Error')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
            
            throw ValidationException::withMessages([
                'booking' => $e->getMessage()
            ]);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
    
    /**
     * Override handleRecordCreation to add debugging
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            Log::info('=== ATTEMPTING TO CREATE BOOKING RECORD ===', [
                'timestamp' => now()->toISOString(),
                'data' => $data
            ]);
            
            $booking = static::getModel()::create($data);
            
            Log::info('=== BOOKING RECORD CREATED SUCCESSFULLY ===', [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'created_at' => $booking->created_at
            ]);
            
            return $booking;
            
        } catch (\Exception $e) {
            Log::error('=== BOOKING RECORD CREATION FAILED ===', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Validate Kenyan phone number format
     */
    private function isValidKenyanPhone(string $phone): bool
    {
        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);
        
        // Kenyan phone number patterns:
        // - 07xxxxxxxx (9 digits starting with 07)
        // - 01xxxxxxxx (9 digits starting with 01) 
        // - +254xxxxxxx (12 digits starting with +254)
        // - 254xxxxxxx (11 digits starting with 254)
        
        $patterns = [
            '/^0[17]\d{8}$/',           // 07xxxxxxxx or 01xxxxxxxx
            '/^\+254[17]\d{8}$/',       // +254xxxxxxx
            '/^254[17]\d{8}$/',         // 254xxxxxxx
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Format phone number to standard Kenyan format (+254xxxxxxx)
     */
    private function formatKenyanPhone(string $phone): string
    {
        // Remove spaces, dashes, and other non-numeric characters except +
        $phone = preg_replace('/[^\d\+]/', '', $phone);
        
        // Convert to standard +254 format
        if (preg_match('/^0([17]\d{8})$/', $phone, $matches)) {
            // 07xxxxxxxx or 01xxxxxxxx -> +254xxxxxxx
            return '+254' . $matches[1];
        } elseif (preg_match('/^254([17]\d{8})$/', $phone, $matches)) {
            // 254xxxxxxx -> +254xxxxxxx
            return '+254' . $matches[1];
        } elseif (preg_match('/^\+254[17]\d{8}$/', $phone)) {
            // Already in correct format
            return $phone;
        }
        
        // Return as-is if no pattern matches (shouldn't happen after validation)
        return $phone;
    }
    
    /**
     * Parse appointment date with proper timezone handling
     */
    private function parseAppointmentDate($dateValue): Carbon
    {
        try {
            // If the date is already a Carbon instance, convert to EAT
            if ($dateValue instanceof Carbon) {
                return $dateValue->setTimezone('Africa/Nairobi')->startOfDay();
            }
            
            // Handle different date formats
            $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'Y-m-d H:i:s', 'd-m-Y'];
            
            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $dateValue, 'Africa/Nairobi');
                    if ($date && $date->format($format) === $dateValue) {
                        return $date->startOfDay();
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // Fallback to Carbon::parse with EAT timezone
            return Carbon::parse($dateValue, 'Africa/Nairobi')->startOfDay();
            
        } catch (\Exception $e) {
            Log::error('Failed to parse appointment date', [
                'date_value' => $dateValue,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Validate required fields for booking creation
     */
    private function validateRequiredFields(array $data): void
    {
        $requiredFields = [
            'start_time' => 'Please select a time slot for your appointment.',
            'appointment_date' => 'Please select an appointment date.',
            'service_id' => 'Please select a service.',
        ];
        
        foreach ($requiredFields as $field => $message) {
            if (empty($data[$field])) {
                throw ValidationException::withMessages([$field => $message]);
            }
        }
        
        // Validate client selection - either existing client or new client data
        if (empty($data['client_id'])) {
            $newClientRequired = [
                'new_client_first_name' => 'First name is required when creating a new client.',
                'new_client_last_name' => 'Last name is required when creating a new client.',
                'new_client_email' => 'Email is required when creating a new client.',
                'new_client_phone' => 'Phone number is required when creating a new client.',
            ];
            
            foreach ($newClientRequired as $field => $message) {
                if (empty($data[$field])) {
                    throw ValidationException::withMessages([$field => $message]);
                }
            }
            
            // Validate email format for new client
            if (!empty($data['new_client_email']) && !filter_var($data['new_client_email'], FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'new_client_email' => 'Please enter a valid email address.'
                ]);
            }
            
            // Validate phone number format for new client
            if (!empty($data['new_client_phone']) && !$this->isValidKenyanPhone($data['new_client_phone'])) {
                throw ValidationException::withMessages([
                    'new_client_phone' => 'Please enter a valid Kenyan phone number (e.g., 0712345678 or +254712345678).'
                ]);
            }
        }
        
        // Validate date is not in the past with proper timezone handling  
        try {
            // Parse the date in EAT timezone or convert if needed
            $appointmentDate = $this->parseAppointmentDate($data['appointment_date']);
            $today = Carbon::now('Africa/Nairobi')->startOfDay();
            
            Log::info('Date validation check', [
                'appointment_date_raw' => $data['appointment_date'],
                'appointment_date_parsed' => $appointmentDate->toDateString(),
                'appointment_date_tz' => $appointmentDate->timezone->getName(),
                'today_eat' => $today->toDateString(),
                'today_tz' => $today->timezone->getName(),
                'is_same_or_future' => $appointmentDate->greaterThanOrEqualTo($today),
                'diff_in_days' => $appointmentDate->diffInDays($today, false)
            ]);
            
            // TEMPORARY: Skip date validation for debugging
            if (config('app.debug')) {
                Log::info('⚠️ SKIPPING DATE VALIDATION (DEBUG MODE)');
            } else {
                // Compare dates only (ignore time)
                if ($appointmentDate->lt($today)) {
                    Log::warning('Date validation failed - appointment date is in the past', [
                        'appointment_date' => $appointmentDate->toDateString(),
                        'today' => $today->toDateString()
                    ]);
                    
                    throw ValidationException::withMessages([
                        'appointment_date' => sprintf(
                            'Please select a current or future date. Selected: %s, Today: %s',
                            $appointmentDate->format('Y-m-d'),
                            $today->format('Y-m-d')
                        )
                    ]);
                }
            }
            
            Log::info('✓ Date validation passed');
            
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Date parsing error', [
                'error' => $e->getMessage(),
                'raw_date' => $data['appointment_date'],
                'trace' => $e->getTraceAsString()
            ]);
            
            throw ValidationException::withMessages([
                'appointment_date' => 'Invalid date format. Please select a valid date from the date picker.'
            ]);
        }
        
        // Validate time format
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['start_time'])) {
            throw ValidationException::withMessages([
                'start_time' => 'Invalid time format. Please select a time slot from the grid.'
            ]);
        }
    }
    
    /**
     * Auto-assign the best available staff member with comprehensive debugging
     */
    private function autoAssignStaff(array $data, $tenant): int
    {
        try {
            Log::info('Starting staff auto-assignment process', [
                'service_id' => $data['service_id'],
                'appointment_date' => $data['appointment_date'],
                'start_time' => $data['start_time'],
                'branch_id' => $tenant->id
            ]);
            
            $availabilityService = app(AvailabilityService::class);
            
            // First, check if the selected time slot is actually available
            $slotAvailable = $availabilityService->isSpecificTimeSlotAvailable(
                $data['appointment_date'],
                $data['start_time'],
                $data['service_id'],
                $tenant->id
            );
            
            Log::info('Time slot availability check', [
                'slot_available' => $slotAvailable,
                'date' => $data['appointment_date'],
                'time' => $data['start_time']
            ]);
            
            if (!$slotAvailable) {
                // This shouldn't happen if the UI is working correctly
                // The time slot grid should only show available slots
                Log::error('Selected time slot is not available', [
                    'date' => $data['appointment_date'],
                    'time' => $data['start_time'],
                    'service_id' => $data['service_id']
                ]);
                
                throw new \Exception('The selected time slot is no longer available. This may indicate a technical issue - please refresh the page and try again.');
            }
            
            // Get the best available staff for this specific time slot
            $bestStaff = $availabilityService->getBestAvailableStaff(
                $data['service_id'],
                $tenant->id,
                $data['appointment_date'],
                $data['start_time']
            );
            
            Log::info('Auto-assignment result', [
                'best_staff_found' => !is_null($bestStaff),
                'staff_data' => $bestStaff
            ]);
            
            if (!$bestStaff) {
                // Get more debugging information
                $allQualifiedStaff = \App\Models\Staff::where('status', 'active')
                    ->whereHas('branches', function($query) use ($tenant) {
                        $query->where('branch_id', $tenant->id)->where('status', 'active');
                    })
                    ->whereHas('services', function($query) use ($data) {
                        $query->where('service_id', $data['service_id'])->where('status', 'active');
                    })
                    ->count();
                
                $allBookingsAtTime = \App\Models\Booking::where('appointment_date', $data['appointment_date'])
                    ->where('branch_id', $tenant->id)
                    ->where('start_time', $data['start_time'])
                    ->where('status', '!=', 'cancelled')
                    ->count();
                
                Log::error('Staff auto-assignment failed - detailed analysis', [
                    'qualified_staff_count' => $allQualifiedStaff,
                    'bookings_at_this_time' => $allBookingsAtTime,
                    'selected_date' => $data['appointment_date'],
                    'selected_time' => $data['start_time'],
                    'service_id' => $data['service_id']
                ]);
                
                if ($allQualifiedStaff === 0) {
                    throw new \Exception('No staff members are qualified to provide this service. Please contact the spa administrator.');
                } else {
                    // This is a logic error - the time slot showed as available but no staff can be assigned
                    throw new \Exception('Technical error: Time slot appeared available but no staff can be assigned. Please try a different time slot or refresh the page.');
                }
            }
            
            // Success notification for auto-assignment
            Notification::make()
                ->title('Staff Auto-Assigned')
                ->body("Automatically assigned to {$bestStaff['name']} ({$bestStaff['workload_status']}) who is available for your selected time.")
                ->info()
                ->duration(4000)
                ->send();
            
            Log::info('Staff auto-assignment completed successfully', [
                'assigned_staff_id' => $bestStaff['id'],
                'assigned_staff_name' => $bestStaff['name'],
                'workload_status' => $bestStaff['workload_status']
            ]);
            
            return $bestStaff['id'];
            
        } catch (\Exception $e) {
            Log::error('Staff auto-assignment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            
            throw new \Exception('Unable to assign staff: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate that the selected time slot is still available with enhanced logic
     */
    private function validateTimeSlotAvailability(array $data, $tenant): void
    {
        try {
            $availabilityService = app(AvailabilityService::class);
            
            Log::info('Final time slot availability validation', [
                'date' => $data['appointment_date'],
                'time' => $data['start_time'],
                'service_id' => $data['service_id'],
                'staff_id' => $data['staff_id'],
                'branch_id' => $tenant->id
            ]);
            
            // Enhanced debugging before availability check
            Log::info('=== FINAL AVAILABILITY CHECK INPUT ===', [
                'raw_date' => $data['appointment_date'],
                'raw_time' => $data['start_time'],
                'date_type' => gettype($data['appointment_date']),
                'time_type' => gettype($data['start_time']),
                'service_id' => $data['service_id'],
                'branch_id' => $tenant->id,
                'staff_id' => $data['staff_id'],
                'current_nairobi_time' => \Carbon\Carbon::now('Africa/Nairobi')->format('Y-m-d H:i:s T')
            ]);
            
            // Check if the specific staff member (if assigned) is available
            $available = $availabilityService->isSpecificTimeSlotAvailable(
                $data['appointment_date'],
                $data['start_time'],
                $data['service_id'],
                $tenant->id,
                $data['staff_id']
            );
            
            Log::info('=== FINAL AVAILABILITY CHECK RESULT ===', [
                'is_available' => $available,
                'staff_id' => $data['staff_id'],
                'time_slot' => $data['start_time'],
                'date' => $data['appointment_date']
            ]);
            
            if (!$available) {
                // Get more detailed information about why it's not available
                $conflictingBookings = \App\Models\Booking::where('appointment_date', $data['appointment_date'])
                    ->where('branch_id', $tenant->id)
                    ->where('staff_id', $data['staff_id'])
                    ->where('start_time', '<=', $data['start_time'])
                    ->where('end_time', '>', $data['start_time'])
                    ->where('status', '!=', 'cancelled')
                    ->count();
                
                Log::warning('Time slot validation failed', [
                    'requested_time' => $data['start_time'],
                    'conflicting_bookings' => $conflictingBookings,
                    'staff_id' => $data['staff_id'],
                    'date' => $data['appointment_date']
                ]);
                
                if ($conflictingBookings > 0) {
                    throw new \Exception("This time slot is already booked. Please select a different time slot.");
                } else {
                    throw new \Exception('The selected time slot is no longer available. Please refresh and try again.');
                }
            }
            
            Log::info('✓ Time slot availability validation passed');
            
        } catch (\Exception $e) {
            Log::error('Time slot validation error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new \Exception('Availability check failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate end time based on service duration
     */
    private function calculateEndTime(array $data): array
    {
        try {
            if (isset($data['start_time']) && isset($data['service_id'])) {
                $service = Service::find($data['service_id']);
                if ($service) {
                    $startTime = Carbon::parse($data['appointment_date'] . ' ' . $data['start_time']);
                    $endTime = $startTime->addMinutes($service->duration ?? 60);
                    $data['end_time'] = $endTime->format('H:i');
                }
            }
            return $data;
        } catch (\Exception $e) {
            throw new \Exception('Failed to calculate appointment end time: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a new client user
     */
    private function createNewClient(array $data): int
    {
        try {
            Log::info('Attempting to create new client', [
                'provided_data' => [
                    'first_name' => $data['new_client_first_name'] ?? 'missing',
                    'last_name' => $data['new_client_last_name'] ?? 'missing',
                    'email' => $data['new_client_email'] ?? 'missing',
                    'phone' => $data['new_client_phone'] ?? 'missing',
                    'gender' => $data['new_client_gender'] ?? 'not provided',
                    'date_of_birth' => $data['new_client_date_of_birth'] ?? 'not provided',
                    'allergies' => $data['new_client_allergies'] ?? 'not provided',
                ]
            ]);
            
            // Validate new client data
            $requiredClientFields = [
                'new_client_first_name' => 'First name is required for new clients.',
                'new_client_last_name' => 'Last name is required for new clients.',
                'new_client_email' => 'Email is required for new clients.',
                'new_client_phone' => 'Phone number is required for new clients.',
            ];
            
            $missingFields = [];
            foreach ($requiredClientFields as $field => $message) {
                if (empty($data[$field])) {
                    $missingFields[$field] = $message;
                }
            }
            
            if (!empty($missingFields)) {
                Log::error('Missing required client fields', ['missing_fields' => $missingFields]);
                throw ValidationException::withMessages($missingFields);
            }
            
            // Validate email format
            if (!filter_var($data['new_client_email'], FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'new_client_email' => 'Please enter a valid email address.'
                ]);
            }
            
            // Check if email already exists
            $existingUser = User::where('email', $data['new_client_email'])->first();
            if ($existingUser) {
                Log::warning('Email already exists', [
                    'email' => $data['new_client_email'],
                    'existing_user_id' => $existingUser->id
                ]);
                throw ValidationException::withMessages([
                    'new_client_email' => 'A user with this email already exists. Please use the existing client option or choose a different email.'
                ]);
            }
            
            // Prepare client data with formatted phone number
            $clientData = [
                'first_name' => trim($data['new_client_first_name']),
                'last_name' => trim($data['new_client_last_name']),
                'email' => strtolower(trim($data['new_client_email'])),
                'phone' => $this->formatKenyanPhone($data['new_client_phone']),
                'user_type' => 'client',
                'password' => Hash::make(Str::random(12)), // Generate random password
                'email_verified_at' => now(), // Auto-verify for spa clients
            ];
            
            // Add optional fields if provided
            if (!empty($data['new_client_gender'])) {
                $clientData['gender'] = $data['new_client_gender'];
            }
            
            if (!empty($data['new_client_date_of_birth'])) {
                $clientData['date_of_birth'] = $data['new_client_date_of_birth'];
            }
            
            if (!empty($data['new_client_allergies'])) {
                $clientData['allergies'] = trim($data['new_client_allergies']);
            }
            
            Log::info('Creating client with data', ['client_data' => collect($clientData)->except(['password'])->toArray()]);
            
            $client = User::create($clientData);
            
            if (!$client) {
                throw new \Exception('Failed to create client record in database.');
            }
            
            Log::info('Client created successfully', [
                'client_id' => $client->id,
                'client_name' => $client->first_name . ' ' . $client->last_name,
                'client_email' => $client->email
            ]);
            
            Notification::make()
                ->title('New Client Created')
                ->body("Client profile created for {$client->first_name} {$client->last_name}")
                ->success()
                ->send();
            
            return $client->id;
            
        } catch (ValidationException $e) {
            Log::error('Client validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Client creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw new \Exception('Failed to create new client: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean up new client fields from the data array
     */
    private function cleanupClientFields(array $data): array
    {
        $clientFields = [
            'new_client_first_name',
            'new_client_last_name',
            'new_client_email',
            'new_client_phone',
            'new_client_gender',
            'new_client_date_of_birth',
            'new_client_allergies'
        ];
        
        foreach ($clientFields as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }
    
    /**
     * Handle successful booking creation
     */
    protected function afterCreate(): void
    {
        $booking = $this->record;
        
        try {
            // Send success notification
            Notification::make()
                ->title('Booking Created Successfully!')
                ->body("Booking #{$booking->booking_reference} has been created for {$booking->client->first_name} {$booking->client->last_name}")
                ->success()
                ->duration(5000)
                ->send();
            
            // Log the successful booking creation
            Log::info('Booking created successfully', [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'client_id' => $booking->client_id,
                'staff_id' => $booking->staff_id,
                'service_id' => $booking->service_id,
                'appointment_date' => $booking->appointment_date,
                'start_time' => $booking->start_time,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in afterCreate hook', [
                'error' => $e->getMessage(),
                'booking_id' => $booking->id ?? null
            ]);
        }
    }
    
    /**
     * Handle form validation errors
     */
    protected function onValidationError(ValidationException $exception): void
    {
        $errors = $exception->errors();
        $errorMessages = [];
        
        foreach ($errors as $field => $messages) {
            $errorMessages[] = implode(' ', $messages);
        }
        
        $errorBody = !empty($errorMessages) 
            ? implode(' | ', array_slice($errorMessages, 0, 3)) // Show first 3 errors
            : 'Please check the form for errors and try again.';
        
        // Log validation errors for debugging
        Log::error('Form validation failed', [
            'errors' => $errors,
            'user_id' => auth()->id(),
            'url' => request()->url()
        ]);
        
        Notification::make()
            ->title('Validation Error')
            ->body($errorBody)
            ->danger()
            ->persistent()
            ->send();
        
        parent::onValidationError($exception);
    }
    
    /**
     * Debug helper: Check form state before validation
     */
    protected function beforeValidate(): void
    {
        $formData = $this->form->getState();
        
        Log::info('Form state before validation', [
            'form_data' => $formData,
            'has_client_id' => isset($formData['client_id']) && !empty($formData['client_id']),
            'client_id_value' => $formData['client_id'] ?? 'not set',
            'new_client_fields' => [
                'first_name' => $formData['new_client_first_name'] ?? 'not set',
                'last_name' => $formData['new_client_last_name'] ?? 'not set',
                'email' => $formData['new_client_email'] ?? 'not set',
                'phone' => $formData['new_client_phone'] ?? 'not set',
            ],
            'all_keys' => array_keys($formData),
            'url' => request()->url(),
            'user_id' => auth()->id()
        ]);
    }
    
    /**
     * Additional debug method to check wizard step data
     */
    protected function getFormData(): array
    {
        $data = parent::getFormData();
        
        Log::info('Raw form data from wizard', [
            'wizard_data' => $data,
            'data_keys' => array_keys($data),
            'client_related_fields' => array_filter($data, function($key) {
                return str_contains($key, 'client');
            }, ARRAY_FILTER_USE_KEY)
        ]);
        
        return $data;
    }
    
    /**
     * Validate all wizard steps before final submission
     */
    private function validateAllStepsBeforeSubmission(array $data): void
    {
        $errors = [];
        
        // Step 1: Service Selection
        if (empty($data['service_id'])) {
            $errors['service_id'] = 'Please complete Step 1: Select a service.';
        }
        
        // Step 2: Staff Selection (optional, but validate if service exists)
        if (!empty($data['service_id'])) {
            // Staff selection is optional, no validation needed
            Log::info('Step 2 validation: Staff selection is optional');
        }
        
        // Step 3: Date & Time
        if (empty($data['appointment_date'])) {
            $errors['appointment_date'] = 'Please complete Step 3: Select an appointment date.';
        }
        if (empty($data['start_time'])) {
            $errors['start_time'] = 'Please complete Step 3: Select a time slot.';
        }
        
        // Step 4: Client Information
        $hasExistingClient = !empty($data['client_id']);
        $hasCompleteNewClient = !empty($data['new_client_first_name']) && 
                               !empty($data['new_client_last_name']) && 
                               !empty($data['new_client_email']) && 
                               !empty($data['new_client_phone']);
        
        if (!$hasExistingClient && !$hasCompleteNewClient) {
            $errors['client_info'] = 'Please complete Step 4: Provide complete client information.';
        }
        
        // Step 5: Booking Details
        $finalRequiredFields = [
            'status' => 'booking status',
            'payment_method' => 'payment method',
            'total_amount' => 'total amount'
        ];
        
        foreach ($finalRequiredFields as $field => $label) {
            if (empty($data[$field])) {
                $errors[$field] = "Please complete Step 5: Select {$label}.";
            }
        }
        
        if (!empty($errors)) {
            Log::error('Wizard step validation failed before submission', ['errors' => $errors]);
            
            Notification::make()
                ->title('Incomplete Form Submission')
                ->body('Please complete all required steps before submitting the booking.')
                ->danger()
                ->persistent()
                ->send();
            
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
        
        Log::info('✓ All wizard steps validated successfully before submission');
    }
    
    /**
     * Handle form submission with enhanced validation
     */
    public function create(bool $another = false): void
    {
        try {
            Log::info('=== WIZARD FORM SUBMISSION INITIATED ===', [
                'timestamp' => now()->toISOString(),
                'another' => $another,
                'user_id' => auth()->id()
            ]);
            
            // Get current form state for validation
            $formData = $this->form->getState();
            
            // Perform comprehensive validation before proceeding
            $this->validateAllStepsBeforeSubmission($formData);
            
            // Test database connection
            try {
                \DB::connection()->getPdo();
                Log::info('✓ Database connection is active');
            } catch (\Exception $dbE) {
                Log::error('❌ Database connection failed', ['error' => $dbE->getMessage()]);
                throw new \Exception('Database connection failed: ' . $dbE->getMessage());
            }
            
            // Test model access
            try {
                $modelClass = static::getModel();
                Log::info('✓ Model accessible', ['model' => $modelClass]);
            } catch (\Exception $modelE) {
                Log::error('❌ Model access failed', ['error' => $modelE->getMessage()]);
                throw new \Exception('Model access failed: ' . $modelE->getMessage());
            }
            
            // Show progress notification
            Notification::make()
                ->title('Creating Booking...')
                ->body('Processing your booking request. Please wait.')
                ->info()
                ->send();
            
            parent::create($another);
            
            Log::info('=== WIZARD FORM SUBMISSION COMPLETED SUCCESSFULLY ===');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors specifically
            Log::error('=== WIZARD VALIDATION FAILED ===', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            
            // Let Filament handle validation errors normally
            throw $e;
            
        } catch (\Exception $e) {
            Log::error('=== WIZARD FORM SUBMISSION FAILED ===', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'another' => $another
            ]);
            
            // Show user-friendly error notification
            Notification::make()
                ->title('Booking Submission Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
            
            throw $e;
        }
    }
}