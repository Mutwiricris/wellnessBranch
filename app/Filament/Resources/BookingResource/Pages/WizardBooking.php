<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use App\Services\AvailabilityService;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class WizardBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;
    
    protected static string $view = 'filament.resources.booking-resource.pages.wizard-booking';
    
    protected static ?string $title = 'New Booking - Step by Step';
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Service Selection')
                        ->description('Choose the service you want to book')
                        ->schema([
                            Forms\Components\Section::make('Select Service')
                                ->description('Choose from our available services')
                                ->schema([
                                    Forms\Components\Select::make('service_id')
                                        ->label('Service')
                                        ->options(function () {
                                            $tenant = \Filament\Facades\Filament::getTenant();
                                            return Service::whereHas('branches', function (Builder $query) use ($tenant) {
                                                $query->where('branch_id', $tenant->id);
                                            })->get()->mapWithKeys(function ($service) {
                                                return [$service->id => $service->name . ' - KES ' . number_format($service->price, 2) . ' (' . $service->duration . ' mins)'];
                                            });
                                        })
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                $service = Service::find($state);
                                                if ($service) {
                                                    $set('total_amount', $service->price);
                                                }
                                            }
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Select the service you would like to book'),
                                        
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Placeholder::make('service_price')
                                                ->label('Service Price')
                                                ->content(function (Forms\Get $get) {
                                                    $serviceId = $get('service_id');
                                                    if ($serviceId) {
                                                        $service = Service::find($serviceId);
                                                        return $service ? 'KES ' . number_format($service->price, 2) : 'Not available';
                                                    }
                                                    return 'Select a service';
                                                }),
                                                
                                            Forms\Components\Placeholder::make('service_duration')
                                                ->label('Duration')
                                                ->content(function (Forms\Get $get) {
                                                    $serviceId = $get('service_id');
                                                    if ($serviceId) {
                                                        $service = Service::find($serviceId);
                                                        return $service ? $service->duration . ' minutes' : 'Not available';
                                                    }
                                                    return 'Select a service';
                                                }),
                                        ]),
                                        
                                    Forms\Components\Textarea::make('service_description')
                                        ->label('Service Description')
                                        ->content(function (Forms\Get $get) {
                                            $serviceId = $get('service_id');
                                            if ($serviceId) {
                                                $service = Service::find($serviceId);
                                                return $service?->description ?? 'No description available';
                                            }
                                            return 'Select a service to see description';
                                        })
                                        ->disabled()
                                        ->rows(3),
                                ])
                        ]),

                    Wizard\Step::make('Staff Selection')
                        ->description('Choose your preferred staff member')
                        ->schema([
                            Forms\Components\Section::make('Select Staff Member')
                                ->description('Choose from available staff for your selected service')
                                ->schema([
                                    Forms\Components\Select::make('staff_id')
                                        ->label('Staff Member')
                                        ->options(function (Forms\Get $get) {
                                            $tenant = \Filament\Facades\Filament::getTenant();
                                            $serviceId = $get('service_id');
                                            
                                            $query = Staff::whereHas('branches', function (Builder $query) use ($tenant) {
                                                $query->where('branch_id', $tenant->id);
                                            })->where('status', 'active');
                                            
                                            if ($serviceId) {
                                                $query->whereHas('services', function (Builder $query) use ($serviceId) {
                                                    $query->where('service_id', $serviceId);
                                                });
                                            }
                                            
                                            return $query->get()->mapWithKeys(function ($staff) {
                                                return [$staff->id => $staff->name . ' - ' . $staff->specialization];
                                            });
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Choose your preferred staff member or leave blank for auto-assignment'),
                                        
                                    Forms\Components\Placeholder::make('staff_info')
                                        ->label('Staff Information')
                                        ->content(function (Forms\Get $get) {
                                            $staffId = $get('staff_id');
                                            if ($staffId) {
                                                $staff = Staff::find($staffId);
                                                if ($staff) {
                                                    return "Specialization: {$staff->specialization}\nPhone: {$staff->phone}\nEmail: {$staff->email}";
                                                }
                                            }
                                            return 'Select a staff member to see their information';
                                        }),
                                ])
                        ]),

                    Wizard\Step::make('Date & Time')
                        ->description('Select your preferred appointment date and time')
                        ->schema([
                            Forms\Components\Section::make('Schedule Appointment')
                                ->description('Choose your preferred date and time slot')
                                ->schema([
                                    Forms\Components\DatePicker::make('appointment_date')
                                        ->label('Appointment Date')
                                        ->required()
                                        ->minDate(now())
                                        ->maxDate(now()->addMonths(3))
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $set('start_time', null);
                                            $set('end_time', null);
                                        })
                                        ->helperText('Select a date within the next 3 months'),
                                        
                                    Forms\Components\Select::make('start_time')
                                        ->label('Available Time Slots')
                                        ->options(function (Forms\Get $get) {
                                            $date = $get('appointment_date');
                                            $serviceId = $get('service_id');
                                            $staffId = $get('staff_id');
                                            
                                            if (!$date || !$serviceId) {
                                                return [];
                                            }
                                            
                                            $availabilityService = app(AvailabilityService::class);
                                            $tenant = \Filament\Facades\Filament::getTenant();
                                            
                                            return $availabilityService->getAvailableTimeSlots(
                                                $date,
                                                $serviceId,
                                                $tenant->id,
                                                $staffId
                                            );
                                        })
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                                            if ($state && $get('service_id')) {
                                                $service = Service::find($get('service_id'));
                                                if ($service) {
                                                    $endTime = Carbon::parse($state)->addMinutes($service->duration ?? 60);
                                                    $set('end_time', $endTime->format('H:i'));
                                                }
                                            }
                                        })
                                        ->helperText('Available time slots based on staff availability'),
                                        
                                    Forms\Components\TextInput::make('end_time')
                                        ->label('End Time')
                                        ->disabled()
                                        ->helperText('Automatically calculated based on service duration'),
                                        
                                    Forms\Components\Placeholder::make('booking_summary')
                                        ->label('Booking Summary')
                                        ->content(function (Forms\Get $get) {
                                            $serviceId = $get('service_id');
                                            $staffId = $get('staff_id');
                                            $date = $get('appointment_date');
                                            $startTime = $get('start_time');
                                            $endTime = $get('end_time');
                                            
                                            if ($serviceId && $date && $startTime) {
                                                $service = Service::find($serviceId);
                                                $staff = $staffId ? Staff::find($staffId) : null;
                                                
                                                $summary = "Service: " . $service->name . "\n";
                                                $summary .= "Staff: " . ($staff ? $staff->name : 'Auto-assigned') . "\n";
                                                $summary .= "Date: " . Carbon::parse($date)->format('l, F j, Y') . "\n";
                                                $summary .= "Time: " . $startTime . ($endTime ? ' - ' . $endTime : '') . "\n";
                                                $summary .= "Duration: " . $service->duration . " minutes\n";
                                                $summary .= "Price: KES " . number_format($service->price, 2);
                                                
                                                return $summary;
                                            }
                                            
                                            return 'Complete the form to see booking summary';
                                        }),
                                ])
                        ]),

                    Wizard\Step::make('Client Information')
                        ->description('Provide client details for the booking')
                        ->schema([
                            Forms\Components\Section::make('Client Details')
                                ->description('Select existing client or create new one')
                                ->schema([
                                    Forms\Components\Select::make('client_id')
                                        ->label('Existing Client')
                                        ->relationship('client', 'first_name')
                                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->first_name . ' ' . $record->last_name . ' (' . $record->email . ')')
                                        ->searchable(['first_name', 'last_name', 'email', 'phone'])
                                        ->preload()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                // Clear new client fields when existing client is selected
                                                $set('new_client_first_name', null);
                                                $set('new_client_last_name', null);
                                                $set('new_client_email', null);
                                                $set('new_client_phone', null);
                                            }
                                        })
                                        ->helperText('Search and select an existing client'),
                                        
                                    Forms\Components\Fieldset::make('New Client')
                                        ->label('Or Create New Client')
                                        ->schema([
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\TextInput::make('new_client_first_name')
                                                        ->label('First Name')
                                                        ->reactive()
                                                        ->afterStateUpdated(function ($state, callable $set) {
                                                            if ($state) {
                                                                $set('client_id', null);
                                                            }
                                                        }),
                                                        
                                                    Forms\Components\TextInput::make('new_client_last_name')
                                                        ->label('Last Name')
                                                        ->reactive()
                                                        ->afterStateUpdated(function ($state, callable $set) {
                                                            if ($state) {
                                                                $set('client_id', null);
                                                            }
                                                        }),
                                                        
                                                    Forms\Components\TextInput::make('new_client_email')
                                                        ->label('Email')
                                                        ->email()
                                                        ->reactive()
                                                        ->afterStateUpdated(function ($state, callable $set) {
                                                            if ($state) {
                                                                $set('client_id', null);
                                                            }
                                                        }),
                                                        
                                                    Forms\Components\TextInput::make('new_client_phone')
                                                        ->label('Phone')
                                                        ->tel()
                                                        ->reactive()
                                                        ->afterStateUpdated(function ($state, callable $set) {
                                                            if ($state) {
                                                                $set('client_id', null);
                                                            }
                                                        }),
                                                        
                                                    Forms\Components\Select::make('new_client_gender')
                                                        ->label('Gender')
                                                        ->options([
                                                            'male' => 'Male',
                                                            'female' => 'Female',
                                                            'other' => 'Other',
                                                            'prefer_not_to_say' => 'Prefer not to say'
                                                        ])
                                                        ->native(false),
                                                        
                                                    Forms\Components\DatePicker::make('new_client_dob')
                                                        ->label('Date of Birth')
                                                        ->maxDate(now()),
                                                ])
                                        ]),
                                        
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Booking Notes')
                                        ->rows(3)
                                        ->helperText('Any special requests or notes for this booking'),
                                        
                                    Forms\Components\Section::make('Payment Information')
                                        ->schema([
                                            Forms\Components\Grid::make(3)
                                                ->schema([
                                                    Forms\Components\TextInput::make('total_amount')
                                                        ->label('Total Amount')
                                                        ->numeric()
                                                        ->prefix('KES')
                                                        ->step(0.01)
                                                        ->required()
                                                        ->disabled(),
                                                        
                                                    Forms\Components\Select::make('payment_method')
                                                        ->label('Payment Method')
                                                        ->options([
                                                            'cash' => 'Cash',
                                                            'mpesa' => 'M-Pesa',
                                                            'card' => 'Card',
                                                            'bank_transfer' => 'Bank Transfer'
                                                        ])
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
                                                ])
                                        ])
                                ])
                        ]),
                ])
                ->submitAction(new \Filament\Actions\Action('create'))
                ->skippable()
            ]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        $data['branch_id'] = $tenant->id;
        
        // Handle new client creation
        if (!$data['client_id'] && !empty($data['new_client_first_name'])) {
            $client = User::create([
                'first_name' => $data['new_client_first_name'],
                'last_name' => $data['new_client_last_name'],
                'email' => $data['new_client_email'],
                'phone' => $data['new_client_phone'],
                'gender' => $data['new_client_gender'] ?? null,
                'date_of_birth' => $data['new_client_dob'] ?? null,
                'password' => bcrypt('temp_password_' . rand(1000, 9999)),
                'user_type' => 'client'
            ]);
            
            $data['client_id'] = $client->id;
        }
        
        // Clean up new client fields
        unset($data['new_client_first_name'], $data['new_client_last_name'], 
              $data['new_client_email'], $data['new_client_phone'], 
              $data['new_client_gender'], $data['new_client_dob']);
        
        // Set default status
        $data['status'] = 'pending';
        
        // Generate booking reference
        $data['booking_reference'] = 'BK' . strtoupper(uniqid());
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}