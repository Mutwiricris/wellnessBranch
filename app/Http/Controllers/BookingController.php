<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Booking;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    protected $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function index()
    {
        Session::forget('booking_data');
        return redirect()->route('booking.branches');
    }

    public function branches()
    {
        $branches = Branch::active()->get();
        $bookingData = Session::get('booking_data', []);
        
        return view('booking.branches', compact('branches', 'bookingData'));
    }

    public function selectBranch(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|exists:branches,id'
            ]);

            $branch = Branch::findOrFail($request->branch_id);
            
            if ($branch->status !== 'active') {
                return back()->with('error', 'This branch is currently unavailable for bookings.');
            }

            $bookingData = Session::get('booking_data', []);
            $bookingData['branch_id'] = $request->branch_id;
            $bookingData['step'] = 2;
            Session::put('booking_data', $bookingData);

            return redirect()->route('booking.services')->with('success', "Great! You've selected {$branch->name}. Now choose your service.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', 'Please select a valid branch to continue.');
        } catch (\Exception $e) {
            Log::error('Branch selection failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'exception' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Unable to select branch. Please try again.');
        }
    }

    public function services()
    {
        try {
            $bookingData = Session::get('booking_data', []);
            
            if (!isset($bookingData['branch_id'])) {
                return redirect()->route('booking.branches')->with('info', 'Please select a branch first.');
            }

            $branch = Branch::findOrFail($bookingData['branch_id']);
            $services = Service::whereHas('branches', function($query) use ($bookingData) {
                $query->where('branch_id', $bookingData['branch_id']);
            })->with('category')->get()->groupBy('category.name');

            if ($services->isEmpty()) {
                return redirect()->route('booking.branches')->with('error', 'No services available at this branch. Please select another branch.');
            }

            return view('booking.services', compact('services', 'branch', 'bookingData'));

        } catch (\Exception $e) {
            Log::error('Services loading failed: ' . $e->getMessage(), [
                'booking_data' => $bookingData ?? null,
                'exception' => $e->getTraceAsString()
            ]);
            return redirect()->route('booking.branches')->with('error', 'Unable to load services. Please try selecting your branch again.');
        }
    }

    public function selectService(Request $request)
    {
        try {
            $request->validate([
                'service_id' => 'required|exists:services,id'
            ]);

            $service = Service::findOrFail($request->service_id);
            
            if ($service->status !== 'active') {
                return back()->with('error', 'This service is currently unavailable. Please select another service.');
            }

            $bookingData = Session::get('booking_data', []);
            
            // Verify service is available at selected branch
            if (isset($bookingData['branch_id'])) {
                $serviceAvailable = $service->branches()->where('branch_id', $bookingData['branch_id'])->exists();
                if (!$serviceAvailable) {
                    return back()->with('error', 'This service is not available at your selected branch.');
                }
            }

            $bookingData['service_id'] = $request->service_id;
            $bookingData['step'] = 3;
            Session::put('booking_data', $bookingData);

            return redirect()->route('booking.staff')->with('success', "Perfect! You've selected {$service->name}. Now choose your preferred staff member.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', 'Please select a valid service to continue.');
        } catch (\Exception $e) {
            Log::error('Service selection failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'booking_data' => Session::get('booking_data', []),
                'exception' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Unable to select service. Please try again.');
        }
    }

    public function staff()
    {
        try {
            $bookingData = Session::get('booking_data', []);
            
            if (!isset($bookingData['branch_id']) || !isset($bookingData['service_id'])) {
                return redirect()->route('booking.branches')->with('info', 'Please complete the previous steps first.');
            }

            $branch = Branch::findOrFail($bookingData['branch_id']);
            $service = Service::findOrFail($bookingData['service_id']);
            
            $staff = Staff::whereHas('branches', function($query) use ($bookingData) {
                $query->where('branch_id', $bookingData['branch_id']);
            })->whereHas('services', function($query) use ($bookingData) {
                $query->where('service_id', $bookingData['service_id']);
            })->with(['services' => function($query) use ($bookingData) {
                $query->where('service_id', $bookingData['service_id']);
            }])->get();

            return view('booking.staff', compact('staff', 'branch', 'service', 'bookingData'));

        } catch (\Exception $e) {
            Log::error('Staff loading failed: ' . $e->getMessage(), [
                'booking_data' => $bookingData ?? null,
                'exception' => $e->getTraceAsString()
            ]);
            return redirect()->route('booking.branches')->with('error', 'Unable to load staff information. Please start over.');
        }
    }

    public function selectStaff(Request $request)
    {
        try {
            $request->validate([
                'staff_id' => 'nullable|exists:staff,id'
            ]);

            $bookingData = Session::get('booking_data', []);
            
            if ($request->staff_id) {
                $staff = Staff::findOrFail($request->staff_id);
                $message = "Excellent! You've selected {$staff->first_name} {$staff->last_name}. Now choose your preferred date and time.";
            } else {
                $message = "No specific staff preference selected. We'll assign the best available therapist for you.";
            }

            $bookingData['staff_id'] = $request->staff_id;
            $bookingData['step'] = 4;
            Session::put('booking_data', $bookingData);

            return redirect()->route('booking.datetime')->with('success', $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', 'Please select a valid staff member or continue without selection.');
        } catch (\Exception $e) {
            Log::error('Staff selection failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'booking_data' => Session::get('booking_data', []),
                'exception' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Unable to process staff selection. Please try again.');
        }
    }

    public function datetime()
    {
        try {
            $bookingData = Session::get('booking_data', []);
            
            // Get default values if session data is missing
            $branch = isset($bookingData['branch_id']) ? Branch::find($bookingData['branch_id']) : Branch::first();
            $service = isset($bookingData['service_id']) ? Service::find($bookingData['service_id']) : Service::first();
            $staff = isset($bookingData['staff_id']) ? Staff::find($bookingData['staff_id']) : null;
            
            // If still no branch or service, redirect to branches
            if (!$branch || !$service) {
                return redirect()->route('booking.branches')->with('info', 'Please complete the previous booking steps.');
            }

            // Generate available dates for the next 60 days (excluding Sundays and past dates)
            $availableDates = [];
            $startDate = now('Africa/Nairobi')->startOfDay();
            
            for ($i = 0; $i < 90; $i++) {
                $date = $startDate->copy()->addDays($i);
                
                // Skip Sundays (assuming spa is closed on Sundays)
                // Skip past dates (only today and future dates)
                if ($date->dayOfWeek !== 0 && $date->gte(now('Africa/Nairobi')->startOfDay())) {
                    $availableDates[] = [
                        'date' => $date->format('Y-m-d'),
                        'formatted' => $date->format('M j'),
                        'day_name' => $date->format('l'),
                        'is_today' => $date->isToday(),
                        'is_tomorrow' => $date->isTomorrow(),
                        'year' => $date->format('Y')
                    ];
                }
                
                // Stop when we have 60 available dates
                if (count($availableDates) >= 60) {
                    break;
                }
            }

            if (empty($availableDates)) {
                return redirect()->route('booking.branches')->with('error', 'No available appointment dates found. Please try again later.');
            }

            return view('booking.datetime', compact('branch', 'service', 'staff', 'availableDates', 'bookingData'));

        } catch (\Exception $e) {
            Log::error('Datetime loading failed: ' . $e->getMessage(), [
                'booking_data' => $bookingData ?? null,
                'exception' => $e->getTraceAsString()
            ]);
            return redirect()->route('booking.branches')->with('error', 'Unable to load available dates. Please start over.');
        }
    }

    public function getTimeSlots(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date|after_or_equal:today',
            ]);

            $bookingData = Session::get('booking_data', []);
            
            // Validate that we have required booking data
            if (!isset($bookingData['service_id']) || !isset($bookingData['branch_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing booking information. Please start over.'
                ], 400);
            }

            $staffId = $bookingData['staff_id'] ?? null;
            
            // Use AvailabilityService to get real time slots
            $timeSlots = $this->availabilityService->getAvailableTimeSlots(
                $request->date,
                $bookingData['service_id'],
                $bookingData['branch_id'],
                $staffId
            );

            // Transform the data for frontend compatibility
            $formattedSlots = $timeSlots->map(function ($slot) {
                return [
                    'time' => $slot['time'],
                    'end_time' => $slot['end_time'] ?? null,
                    'available' => $slot['available'] ?? true,
                    'staff_id' => $slot['staff_id'] ?? null,
                    'staff_name' => $slot['staff_name'] ?? null,
                    'formatted_time' => $slot['formatted_time'] ?? null
                ];
            })->values();

            return response()->json([
                'success' => true,
                'timeSlots' => $formattedSlots,
                'staff_specific' => !is_null($staffId),
                'message' => $formattedSlots->isEmpty() ? 
                    'No available time slots for this date.' : 
                    'Time slots loaded successfully.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date selected.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Time slots loading failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'booking_data' => $bookingData ?? null,
                'exception' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unable to load available time slots. Please try again.'
            ], 500);
        }
    }

    public function selectDateTime(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date|after_or_equal:today',
                'time' => 'required|date_format:H:i'
            ]);

            // FIXED: Use proper timezone-aware validation instead of strtotime
            $timezone = 'Africa/Nairobi';
            $now = \Carbon\Carbon::now($timezone);
            $selectedDateTime = \Carbon\Carbon::parse($request->date . ' ' . $request->time, $timezone);
            
            // Check if the selected time is at least 1 hour from now
            if ($selectedDateTime->lte($now->copy()->addHour())) {
                return back()->with('error', 'Please select a time that is at least 1 hour from now.');
            }

            // Double-check availability using the AvailabilityService
            $bookingData = Session::get('booking_data', []);
            $isAvailable = $this->availabilityService->isSpecificTimeSlotAvailable(
                $request->date,
                $request->time,
                $bookingData['service_id'],
                $bookingData['branch_id'],
                $bookingData['staff_id'] ?? null
            );

            if (!$isAvailable) {
                return back()->with('error', 'Sorry, this time slot is no longer available. Please select another time.');
            }

            $bookingData['date'] = $request->date;
            $bookingData['time'] = $request->time;
            $bookingData['step'] = 5;
            Session::put('booking_data', $bookingData);

            $formattedDate = $selectedDateTime->format('l, F j, Y');
            $formattedTime = $selectedDateTime->format('g:i A');

            return redirect()->route('booking.client-info')->with('success', "Great! Your appointment is scheduled for {$formattedDate} at {$formattedTime}. Please provide your details.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', 'Please select a valid date and time for your appointment.');
        } catch (\Exception $e) {
            Log::error('DateTime selection failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'booking_data' => Session::get('booking_data', []),
                'exception' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Unable to save your appointment time. Please try again.');
        }
    }

    public function clientInfo()
    {
        try {
            $bookingData = Session::get('booking_data', []);
            
            if (!isset($bookingData['date']) || !isset($bookingData['time'])) {
                return redirect()->route('booking.branches')->with('info', 'Please complete all previous booking steps.');
            }

            $branch = Branch::findOrFail($bookingData['branch_id']);
            $service = Service::findOrFail($bookingData['service_id']);

            return view('booking.client-info', compact('branch', 'service', 'bookingData'));

        } catch (\Exception $e) {
            Log::error('Client info loading failed: ' . $e->getMessage(), [
                'booking_data' => $bookingData ?? null,
                'exception' => $e->getTraceAsString()
            ]);
            return redirect()->route('booking.branches')->with('error', 'Unable to load client information form. Please start over.');
        }
    }

    public function saveClientInfo(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string|min:2|max:50',
                'last_name' => 'required|string|min:2|max:50',
                'email' => 'required|email|max:100',
                'phone' => 'required|string|min:10|max:15',
                'allergies' => 'required|string|max:500',
                'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
                'date_of_birth' => 'nullable|date|before:today',
                'create_account' => 'nullable|in:accepted,active,no_creation'
            ], [
                'first_name.required' => 'First name is required',
                'first_name.min' => 'First name must be at least 2 characters',
                'last_name.required' => 'Last name is required',
                'last_name.min' => 'Last name must be at least 2 characters',
                'email.required' => 'Email address is required',
                'email.email' => 'Please enter a valid email address',
                'phone.required' => 'Phone number is required',
                'phone.min' => 'Phone number must be at least 10 characters',
                'allergies.required' => 'Please specify any allergies or write "None"',
                'date_of_birth.before' => 'Date of birth must be in the past'
            ]);

            $bookingData = Session::get('booking_data', []);
            $bookingData['client_info'] = $request->only([
                'first_name', 'last_name', 'email', 'phone', 'allergies', 
                'gender', 'date_of_birth', 'create_account'
            ]);
            $bookingData['step'] = 6;
            Session::put('booking_data', $bookingData);

            return redirect()->route('booking.payment')->with('success', 'Thank you! Your information has been saved. Please proceed to payment.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput()->with('error', 'Please correct the highlighted fields and try again.');
        } catch (\Exception $e) {
            Log::error('Client info saving failed: ' . $e->getMessage(), [
                'request_data' => $request->except(['password']),
                'booking_data' => Session::get('booking_data', []),
                'exception' => $e->getTraceAsString()
            ]);
            return back()->withInput()->with('error', 'Unable to save your information. Please try again.');
        }
    }

    public function payment()
    {
        try {
            $bookingData = Session::get('booking_data', []);
            
            if (!isset($bookingData['client_info'])) {
                return redirect()->route('booking.branches')->with('info', 'Please complete all previous booking steps.');
            }

            $branch = Branch::findOrFail($bookingData['branch_id']);
            $service = Service::findOrFail($bookingData['service_id']);
            $staff = isset($bookingData['staff_id']) ? Staff::find($bookingData['staff_id']) : null;

            return view('booking.payment', compact('branch', 'service', 'staff', 'bookingData'));

        } catch (\Exception $e) {
            Log::error('Payment page loading failed: ' . $e->getMessage(), [
                'booking_data' => $bookingData ?? null,
                'exception' => $e->getTraceAsString()
            ]);
            return redirect()->route('booking.branches')->with('error', 'Unable to load payment page. Please start over.');
        }
    }

    public function confirmBooking(Request $request)
    {
        try {
            $request->validate([
                'payment_method' => 'required|in:cash,mpesa,card'
            ], [
                'payment_method.required' => 'Please select a payment method',
                'payment_method.in' => 'Please select a valid payment method'
            ]);

            $bookingData = Session::get('booking_data', []);
            $bookingData['payment_method'] = $request->payment_method;

            // Validate that all required booking data is present
            $requiredFields = ['branch_id', 'service_id', 'date', 'time', 'client_info'];
            foreach ($requiredFields as $field) {
                if (!isset($bookingData[$field])) {
                    return back()->with('error', "Missing required booking information. Please start the booking process again.");
                }
            }

            // Validate client info has required fields
            $requiredClientFields = ['first_name', 'last_name', 'email', 'phone', 'allergies'];
            foreach ($requiredClientFields as $field) {
                if (!isset($bookingData['client_info'][$field]) || empty($bookingData['client_info'][$field])) {
                    return back()->with('error', "Missing required client information. Please complete all previous steps.");
                }
            }

            DB::beginTransaction();

            $clientData = $bookingData['client_info'];
            $clientData['user_type'] = 'client';
            $clientData['create_account_status'] = $clientData['create_account'] ?? 'no_creation';
            $clientData['name'] = $clientData['first_name'] . ' ' . $clientData['last_name'];
            unset($clientData['create_account']);

            // Password is nullable for guest bookings
            $clientData['password'] = null;

            $user = User::firstOrCreate(
                ['email' => $clientData['email']],
                $clientData
            );

            $service = Service::findOrFail($bookingData['service_id']);

            // Ensure staff_id is properly handled
            $staffId = !empty($bookingData['staff_id']) ? $bookingData['staff_id'] : null;
            
            // Calculate end time using Carbon with proper timezone
            $timezone = 'Africa/Nairobi';
            $startDateTime = \Carbon\Carbon::parse($bookingData['date'] . ' ' . $bookingData['time'], $timezone);
            $endTime = $startDateTime->addMinutes($service->duration ?? 60)->format('H:i');
            
            $booking = Booking::create([
                'booking_reference' => 'SPA-' . strtoupper(Str::random(8)),
                'branch_id' => $bookingData['branch_id'],
                'service_id' => $bookingData['service_id'],
                'client_id' => $user->id,
                'staff_id' => $staffId,
                'appointment_date' => $bookingData['date'],
                'start_time' => $bookingData['time'],
                'end_time' => $endTime,
                'total_amount' => $service->price,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'cash' ? 'pending' : 'pending',
                'status' => 'pending'
            ]);

            DB::commit();

            Session::forget('booking_data');
            
            return redirect()->route('booking.confirmation', $booking->booking_reference)
                ->with('success', 'Congratulations! Your spa appointment has been successfully booked.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', 'Please correct the highlighted fields and try again.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Booking creation failed: ' . $e->getMessage(), [
                'booking_data' => $bookingData ?? null,
                'client_data' => $clientData ?? null,
                'exception' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Unable to complete your booking. Please try again or contact support if the problem persists.');
        }
    }

    public function confirmation($reference)
    {
        try {
            $booking = Booking::where('booking_reference', $reference)
                ->with(['branch', 'service', 'client' => function($query) {
                    $query->select('id', 'first_name', 'last_name', 'email', 'phone');
                }, 'staff'])
                ->firstOrFail();

            return view('booking.confirmation', compact('booking'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('booking.branches')->with('error', 'Booking confirmation not found. Please try booking again.');
        } catch (\Exception $e) {
            Log::error('Confirmation page loading failed: ' . $e->getMessage(), [
                'reference' => $reference,
                'exception' => $e->getTraceAsString()
            ]);
            return redirect()->route('booking.branches')->with('error', 'Unable to load booking confirmation. Please contact support.');
        }
    }

    public function goBack()
    {
        $bookingData = Session::get('booking_data', []);
        $currentStep = $bookingData['step'] ?? 1;
        
        $routes = [
            2 => 'booking.branches',
            3 => 'booking.services',
            4 => 'booking.staff',
            5 => 'booking.datetime',
            6 => 'booking.client-info',
            7 => 'booking.payment'
        ];

        $previousStep = $currentStep - 1;
        if (isset($routes[$currentStep])) {
            $bookingData['step'] = $previousStep;
            Session::put('booking_data', $bookingData);
            return redirect()->route($routes[$currentStep]);
        }

        return redirect()->route('booking.branches');
    }
}