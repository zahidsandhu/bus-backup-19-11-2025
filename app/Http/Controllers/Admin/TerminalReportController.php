<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatusEnum;
use App\Enums\ChannelEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\TerminalEnum;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\GeneralSetting;
use App\Models\Route;
use App\Models\Terminal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class TerminalReportController extends Controller
{
    public function index(): View
    {
        $this->authorize('view terminal reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAllReports = $user->can('view all booking reports');
        $hasTerminalAssigned = (bool) $user->terminal_id;

        if ($canViewAllReports) {
            $terminals = Terminal::where('status', TerminalEnum::ACTIVE->value)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
            $canSelectTerminal = true;
        } else {
            abort_if(! $hasTerminalAssigned, 403, 'You do not have access to any terminal reports.');

            $terminals = Terminal::where('id', $user->terminal_id)
                ->where('status', TerminalEnum::ACTIVE->value)
                ->get(['id', 'name', 'code']);
            $canSelectTerminal = false;
        }

        if ($canViewAllReports) {
            $bookedByUserIds = Booking::whereNotNull('booked_by_user_id')->distinct()->pluck('booked_by_user_id');
            $userIds = Booking::whereNotNull('user_id')->distinct()->pluck('user_id');
            $allUserIds = $bookedByUserIds->merge($userIds)->unique();

            $users = User::whereIn('id', $allUserIds)
                ->whereDoesntHave('roles', function ($q) {
                    $q->where('name', 'Customer');
                })
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();
        } else {
            $users = User::query()
                ->where('id', $user->id)
                ->select('id', 'name', 'email')
                ->get();
        }

        $bookingStatuses = BookingStatusEnum::cases();
        $paymentStatuses = PaymentStatusEnum::cases();
        $channels = ChannelEnum::cases();
        $paymentMethods = PaymentMethodEnum::options();

        return view('admin.terminal-reports.index', [
            'terminals' => $terminals,
            'users' => $users,
            'canSelectTerminal' => $canSelectTerminal,
            'canViewAllReports' => $canViewAllReports,
            'selectedUserId' => $canViewAllReports ? null : $user->id,
            'bookingStatuses' => $bookingStatuses,
            'paymentStatuses' => $paymentStatuses,
            'channels' => $channels,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function getRoutes(Request $request): JsonResponse
    {
        $this->authorize('view terminal reports');

        $request->validate([
            'terminal_id' => 'required|exists:terminals,id',
        ]);

        $terminal = Terminal::findOrFail($request->terminal_id);
        $routes = $terminal->routes()
            ->where('routes.status', \App\Enums\RouteStatusEnum::ACTIVE->value)
            ->orderBy('routes.name')
            ->select('routes.id', 'routes.name', 'routes.code')
            ->get();

        return response()->json([
            'success' => true,
            'routes' => $routes->map(function ($route) {
                return [
                    'id' => $route->id,
                    'name' => $route->name,
                    'code' => $route->code,
                ];
            }),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('view terminal reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAllReports = $user->can('view all booking reports');
        $hasTerminalAssigned = (bool) $user->terminal_id;

        $validationRules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'route_id' => 'nullable|exists:routes,id',
        ];

        if ($canViewAllReports) {
            $validationRules['terminal_id'] = 'required|exists:terminals,id';
            $validationRules['user_id'] = 'nullable|exists:users,id';
        } else {
            $validationRules['terminal_id'] = 'nullable';
            $validationRules['user_id'] = 'nullable';
        }

        $validated = $request->validate($validationRules);

        if ($canViewAllReports) {
            $terminalId = $validated['terminal_id'];
            if (! $terminalId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Terminal ID is required',
                ], 400);
            }
        } else {
            abort_if(! $hasTerminalAssigned, 403, 'You do not have access to any terminal reports.');

            if ($request->filled('terminal_id') && (int) $request->input('terminal_id') !== $user->terminal_id) {
                abort(403, 'You are not allowed to access this terminal.');
            }

            if ($request->filled('user_id') && (int) $request->input('user_id') !== $user->id) {
                abort(403, 'You are not allowed to view other user reports.');
            }

            $terminalId = $user->terminal_id;
        }

        $terminal = Terminal::findOrFail($terminalId);

        // Build start datetime
        $startDate = Carbon::parse($validated['start_date']);
        if ($request->filled('start_time')) {
            $startDate->setTimeFromTimeString($request->start_time);
        } else {
            $startDate->startOfDay();
        }

        // Build end datetime
        $endDate = Carbon::parse($validated['end_date']);
        if ($request->filled('end_time')) {
            $endDate->setTimeFromTimeString($request->end_time);
        } else {
            $endDate->endOfDay();
        }

        // Get bookings where from_stop or to_stop is at this terminal
        $bookings = $this->getBookingsForTerminal(
            $terminalId,
            $startDate,
            $endDate,
            $canViewAllReports ? ($validated['user_id'] ?? null) : $user->id,
            $validated['route_id'] ?? null
        );

        // Get expenses for trips from/to this terminal
        $expenses = $this->getExpensesForTerminal($terminalId, $startDate, $endDate);

        // Calculate statistics
        $stats = $this->calculateStats($bookings, $expenses);

        // Get summary stats for quick display
        $summary = [
            'cash_in_hand' => $stats['cash']['cash_in_hand'],
            'total_expenses' => $stats['expenses']['total_expenses'],
            'net_balance' => $stats['cash']['net_balance'],
            'total_revenue' => $stats['revenue']['total_revenue'],
        ];

        return response()->json([
            'success' => true,
            'terminal' => [
                'id' => $terminal->id,
                'name' => $terminal->name,
                'code' => $terminal->code,
            ],
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'stats' => $stats,
            'summary' => $summary,
            'bookings' => $bookings->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'from_terminal' => $booking->fromStop?->terminal?->name ?? 'N/A',
                    'to_terminal' => $booking->toStop?->terminal?->name ?? 'N/A',
                    'channel' => $booking->channel,
                    'status' => $booking->status,
                    'payment_status' => $booking->payment_status,
                    'payment_method' => $booking->payment_method,
                    'total_fare' => (float) $booking->total_fare,
                    'discount_amount' => (float) $booking->discount_amount,
                    'tax_amount' => (float) $booking->tax_amount,
                    'final_amount' => (float) $booking->final_amount,
                    'passengers_count' => $booking->passengers->count(),
                    'seats_count' => $booking->seats->count(),
                    'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
                    'user' => $booking->user?->name ?? 'N/A',
                ];
            }),
            'expenses' => $expenses->map(function ($expense) {
                return [
                    'id' => $expense->id,
                    'expense_type' => $expense->expense_type->getLabel(),
                    'amount' => (float) $expense->amount,
                    'from_terminal' => $expense->fromTerminal?->name ?? 'N/A',
                    'to_terminal' => $expense->toTerminal?->name ?? 'N/A',
                    'description' => $expense->description,
                    'expense_date' => $expense->expense_date?->format('Y-m-d') ?? 'N/A',
                    'trip_id' => $expense->trip_id,
                    'user' => $expense->user?->name ?? 'N/A',
                    'created_at' => $expense->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    private function getBookingsForTerminal(int $terminalId, Carbon $startDate, Carbon $endDate, ?int $userId = null, ?int $routeId = null): \Illuminate\Database\Eloquent\Collection
    {
        // ✅ Only get bookings that START from this terminal (from_terminal_id)
        // This matches the passenger filtering logic - terminal staff sees bookings from their terminal
        $query = Booking::query()
            ->whereHas('fromStop', function ($query) use ($terminalId) {
                $query->where('terminal_id', $terminalId);
            })
            ->where('status', BookingStatusEnum::CONFIRMED->value)
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Filter by route if provided
        if ($routeId) {
            $query->whereHas('trip', function ($query) use ($routeId) {
                $query->where('route_id', $routeId);
            });
        }

        // Filter by user if provided
        if ($userId) {
            $query->where('booked_by_user_id', $userId);
        }

        return $query->with([
            'fromStop.terminal',
            'toStop.terminal',
            'seats',
            'passengers',
            'user',
            'bookedByUser',
            'trip.route',
        ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getExpensesForTerminal(int $terminalId, Carbon $startDate, Carbon $endDate)
    {
        // ✅ Get expenses where FROM terminal matches (terminal-wise expense tracking)
        // This ensures expenses are tracked terminal-wise as requested
        return Expense::query()
            ->where('from_terminal_id', $terminalId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->with(['fromTerminal', 'toTerminal', 'trip', 'user'])
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getBookingsData(Request $request)
    {
        $this->authorize('view terminal reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAllReports = $user->can('view all booking reports');
        $hasTerminalAssigned = (bool) $user->terminal_id;

        $validationRules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'route_id' => 'nullable|exists:routes,id',
        ];

        if ($canViewAllReports) {
            $validationRules['terminal_id'] = 'required|exists:terminals,id';
        } else {
            $validationRules['terminal_id'] = 'nullable';
        }

        $validated = $request->validate($validationRules);

        if ($canViewAllReports) {
            $terminalId = $validated['terminal_id'];
            if (! $terminalId) {
                return response()->json(['error' => 'Terminal ID is required'], 400);
            }
        } else {
            abort_if(! $hasTerminalAssigned, 403, 'You do not have access to any terminal reports.');
            $terminalId = $user->terminal_id;
        }

        // Build start datetime
        $startDate = Carbon::parse($validated['start_date']);
        if ($request->filled('start_time')) {
            $startDate->setTimeFromTimeString($request->start_time);
        } else {
            $startDate->startOfDay();
        }

        // Build end datetime
        $endDate = Carbon::parse($validated['end_date']);
        if ($request->filled('end_time')) {
            $endDate->setTimeFromTimeString($request->end_time);
        } else {
            $endDate->endOfDay();
        }

        $query = Booking::query()
            ->whereHas('fromStop', function ($q) use ($terminalId) {
                $q->where('terminal_id', $terminalId);
            })
            ->where('status', BookingStatusEnum::CONFIRMED->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with([
                'fromStop.terminal',
                'toStop.terminal',
                'seats',
                'passengers',
                'user',
                'bookedByUser',
                'trip.route',
            ]);

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('booked_by_user_id', $request->user_id);
        }

        // Status filter is not needed as we only show confirmed bookings
        // But allow override if explicitly requested
        if ($request->filled('status') && $request->status !== BookingStatusEnum::CONFIRMED->value) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('is_advance')) {
            $query->where('is_advance', $request->is_advance === '1');
        }

        return DataTables::of($query)
            ->addColumn('booking_number', function (Booking $booking) {
                return '<span class="badge bg-primary">#'.$booking->booking_number.'</span>';
            })
            ->addColumn('created_at', function (Booking $booking) {
                return $booking->created_at->format('d M Y, H:i');
            })
            ->addColumn('route', function (Booking $booking) {
                $from = $booking->fromStop?->terminal?->code ?? 'N/A';
                $to = $booking->toStop?->terminal?->code ?? 'N/A';

                return '<strong>'.$from.' → '.$to.'</strong>';
            })
            ->addColumn('passengers', function (Booking $booking) {
                $passengerNames = $booking->passengers->pluck('name')->join(', ');

                if (empty($passengerNames)) {
                    return '<span class="text-muted small">No passengers</span>';
                }

                return '<div class="text-nowrap small">'.$passengerNames.'</div>';
            })
            ->addColumn('seats', function (Booking $booking) {
                $seatNumbers = $booking->seats->whereNull('cancelled_at')->pluck('seat_number')->join(', ');

                return '<span class="badge bg-info">'.$seatNumbers.'</span>';
            })
            ->addColumn('channel', function (Booking $booking) {
                try {
                    $channel = ChannelEnum::from($booking->channel ?? '');

                    return '<span class="badge '.$channel->getBadge().'"><i class="'.$channel->getIcon().'"></i> '.$channel->getLabel().'</span>';
                } catch (\ValueError $e) {
                    return '<span class="badge bg-secondary">'.($booking->channel ?? 'N/A').'</span>';
                }
            })
            ->addColumn('status', function (Booking $booking) {
                try {
                    $status = BookingStatusEnum::from($booking->status ?? '');

                    return '<span class="badge '.$status->getBadge().'"><i class="'.$status->getIcon().'"></i> '.$status->getLabel().'</span>';
                } catch (\ValueError $e) {
                    return '<span class="badge bg-secondary">'.ucfirst($booking->status ?? 'Unknown').'</span>';
                }
            })
            ->addColumn('is_advance', function (Booking $booking) {
                if ($booking->is_advance) {
                    return '<span class="badge bg-success"><i class="bx bx-check"></i> Yes</span>';
                }

                return '<span class="badge bg-secondary"><i class="bx bx-x"></i> No</span>';
            })
            ->addColumn('payment_method', function (Booking $booking) {
                try {
                    $method = PaymentMethodEnum::from($booking->payment_method ?? '');

                    return '<span class="badge '.$method->getBadge().'"><i class="'.$method->getIcon().'"></i> '.$method->getLabel().'</span>';
                } catch (\ValueError $e) {
                    return '<span class="badge bg-secondary">'.ucfirst($booking->payment_method ?? 'N/A').'</span>';
                }
            })
            ->addColumn('payment_status', function (Booking $booking) {
                try {
                    $paymentStatus = PaymentStatusEnum::from($booking->payment_status ?? '');

                    return '<span class="badge '.$paymentStatus->getBadge().'"><i class="'.$paymentStatus->getIcon().'"></i> '.$paymentStatus->getLabel().'</span>';
                } catch (\ValueError $e) {
                    return '<span class="badge bg-secondary">'.ucfirst($booking->payment_status ?? 'Unknown').'</span>';
                }
            })
            ->addColumn('amount', function (Booking $booking) {
                return '<strong>PKR '.number_format($booking->final_amount, 0).'</strong>';
            })
            ->addColumn('booked_by', function (Booking $booking) {
                $employee = $booking->bookedByUser;
                if ($employee) {
                    return '<div class="text-nowrap">
                        <div class="fw-semibold small">'.$employee->name.'</div>
                        <small class="text-muted">'.$employee->email.'</small>
                    </div>';
                }

                return '<span class="text-muted small">N/A</span>';
            })
            ->rawColumns(['booking_number', 'route', 'passengers', 'seats', 'channel', 'status', 'is_advance', 'payment_method', 'payment_status', 'amount', 'booked_by'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $this->authorize('view terminal reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAllReports = $user->can('view all booking reports');
        $hasTerminalAssigned = (bool) $user->terminal_id;

        $validationRules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];

        if ($canViewAllReports) {
            $validationRules['terminal_id'] = 'required|exists:terminals,id';
        }

        $validated = $request->validate($validationRules);

        if ($canViewAllReports) {
            $terminalId = $validated['terminal_id'];
        } else {
            abort_if(! $hasTerminalAssigned, 403);
            $terminalId = $user->terminal_id;
        }

        $terminal = Terminal::findOrFail($terminalId);

        // Build start datetime
        $startDate = Carbon::parse($validated['start_date']);
        if ($request->filled('start_time')) {
            $startDate->setTimeFromTimeString($request->start_time);
        } else {
            $startDate->startOfDay();
        }

        // Build end datetime
        $endDate = Carbon::parse($validated['end_date']);
        if ($request->filled('end_time')) {
            $endDate->setTimeFromTimeString($request->end_time);
        } else {
            $endDate->endOfDay();
        }

        $query = Booking::query()
            ->whereHas('fromStop', function ($q) use ($terminalId) {
                $q->where('terminal_id', $terminalId);
            })
            ->where('status', BookingStatusEnum::CONFIRMED->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with([
                'fromStop.terminal',
                'toStop.terminal',
                'seats',
                'passengers',
                'user',
                'bookedByUser',
                'trip.route',
                'trip.originStop',
            ]);

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('booked_by_user_id', $request->user_id);
        }

        // Status filter is not needed as we only show confirmed bookings
        // But allow override if explicitly requested
        if ($request->filled('status') && $request->status !== BookingStatusEnum::CONFIRMED->value) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('is_advance')) {
            $query->where('is_advance', $request->is_advance === '1');
        }

        if ($request->filled('route_id')) {
            $query->whereHas('trip', function ($q) use ($request) {
                $q->where('route_id', $request->route_id);
            });
        }

        $bookings = $query->orderBy('trip_id')
            ->orderBy('created_at')
            ->get();

        $expenses = $this->getExpensesForTerminal($terminalId, $startDate, $endDate);
        $stats = $this->calculateStats($bookings, $expenses);

        $format = $request->get('format', 'pdf');

        if ($format === 'excel') {
            // Excel export would go here - for now return PDF
            return $this->exportPdf($terminal, $startDate, $endDate, $bookings, $expenses, $stats);
        }

        return $this->exportPdf($terminal, $startDate, $endDate, $bookings, $expenses, $stats);
    }

    private function exportPdf($terminal, $startDate, $endDate, $bookings, $expenses, $stats)
    {
        $filename = 'terminal-report-'.$terminal->code.'-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.pdf';

        // Prepare seat-level data for the report
        $seatRows = [];
        foreach ($bookings as $booking) {
            $activeSeats = $booking->seats->whereNull('cancelled_at')->sortBy('seat_number');
            $passengers = $booking->passengers->sortBy('id');

            // Get departure time from trip's origin stop or departure_datetime
            $departureTime = null;
            if ($booking->trip->originStop && $booking->trip->originStop->departure_at) {
                $departureTime = Carbon::parse($booking->trip->originStop->departure_at);
            } elseif ($booking->trip->departure_datetime) {
                $departureTime = Carbon::parse($booking->trip->departure_datetime);
            }

            $fromTerminal = $booking->fromStop->terminal ?? null;
            $toTerminal = $booking->toStop->terminal ?? null;
            $routeCode = ($fromTerminal?->code ?? '').'-'.($toTerminal?->code ?? '');
            $time = $departureTime ? $departureTime->format('h:i A') : 'N/A';
            $date = $booking->trip->departure_date?->format('Y-m-d') ?? $booking->created_at->format('Y-m-d');
            $dateFormatted = Carbon::parse($date)->format('d-m-Y');
            $routeTimeKey = $routeCode.' '.$time;

            foreach ($activeSeats as $index => $seat) {
                $passenger = $passengers[$index] ?? $passengers[0] ?? null;

                $seatRows[] = [
                    'date' => $date,
                    'date_formatted' => $dateFormatted,
                    'route_time' => $routeTimeKey,
                    'time' => $time,
                    'from_terminal_code' => $fromTerminal?->code ?? 'N/A',
                    'seat_number' => $seat->seat_number,
                    'passenger_name' => $passenger?->name ?? '-',
                    'passenger_cnic' => $passenger?->cnic ?? '-',
                    'passenger_phone' => $passenger?->phone ?? '-',
                    'booked_by' => $booking->bookedByUser?->name ?? 'N/A',
                    'to_terminal_code' => $toTerminal?->code ?? 'N/A',
                    'fare' => $seat->final_amount ?? 0,
                    'is_advance' => $booking->is_advance ?? false,
                ];
            }
        }

        // Categorize dates and group: Date -> Past/Present/Future -> Route/Time -> Seats
        $today = Carbon::today()->format('Y-m-d');

        $groupedSeats = collect($seatRows)
            ->groupBy(function ($seat) use ($today) {
                $seatDate = $seat['date'];
                if ($seatDate < $today) {
                    return 'past';
                } elseif ($seatDate === $today) {
                    return 'present';
                } else {
                    return 'future';
                }
            })
            ->map(function ($timeCategoryGroup, $timeCategory) {
                return $timeCategoryGroup
                    ->groupBy('date')
                    ->map(function ($dateGroup) {
                        return $dateGroup
                            ->sortBy('route_time')
                            ->groupBy('route_time')
                            ->map(function ($routeGroup) {
                                return $routeGroup->sortBy(function ($seat) {
                                    return (int) $seat['seat_number'];
                                })->values();
                            });
                    });
            });

        // Order: past, present, future
        $orderedGroupedSeats = collect(['past', 'present', 'future'])
            ->filter(fn ($cat) => isset($groupedSeats[$cat]))
            ->mapWithKeys(fn ($cat) => [$cat => $groupedSeats[$cat]])
            ->toArray();

        $generalSettings = GeneralSetting::first();
        $companyName = $generalSettings?->company_name ?? 'Bashir Sons Travel';
        $companyInitials = collect(explode(' ', $companyName))
            ->map(fn ($word) => strtoupper($word[0] ?? ''))
            ->join('. ') ?: 'B. S';
        $companyTagline = $generalSettings?->tagline ?? 'Daewoo Bus Service';

        $data = [
            'terminal' => $terminal,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'bookings' => $bookings,
            'grouped_seats' => $orderedGroupedSeats,
            'expenses' => $expenses,
            'stats' => $stats,
            'company_name' => $companyName,
            'company_initials' => $companyInitials,
            'company_tagline' => $companyTagline,
            'generated_at' => Carbon::now()->format('d M Y, H:i'),
        ];

        // TODO: Install barryvdh/laravel-dompdf package for PDF export
        // $pdf = Pdf::loadView('admin.terminal-reports.export-advance-booking', $data)
        //     ->setPaper('a4', 'landscape')
        //     ->setOption('enable-local-file-access', true);
        // return $pdf->download($filename);

        // Temporary: Return view for now until PDF package is installed
        return view('admin.terminal-reports.export-advance-booking', $data);
    }

    private function calculateStats($bookings, $expenses): array
    {
        $totalBookings = $bookings->count();
        $totalRevenue = $bookings->sum('final_amount');

        // Calculate advance vs regular bookings
        $advanceBookings = $bookings->where('is_advance', true)->count();
        $regularBookings = $bookings->where('is_advance', false)->count();
        $advanceRevenue = $bookings->where('is_advance', true)->sum('final_amount');
        $regularRevenue = $bookings->where('is_advance', false)->sum('final_amount');

        // Calculate cash in hand (sum of final_amount for cash payments that are paid and confirmed)
        $cashInHand = $bookings
            ->where('payment_method', PaymentMethodEnum::CASH->value)
            ->where('payment_status', PaymentStatusEnum::PAID->value)
            ->where('status', BookingStatusEnum::CONFIRMED->value)
            ->sum('final_amount');

        $totalExpenses = $expenses->sum('amount');
        $netBalance = $cashInHand - $totalExpenses;

        // Payment method breakdown with detailed information
        $paymentMethods = [];
        foreach (PaymentMethodEnum::cases() as $method) {
            $methodBookings = $bookings->where('payment_method', $method->value);
            $methodAmount = $methodBookings->sum('final_amount');
            $methodCount = $methodBookings->count();

            // For cash, also calculate paid and confirmed amount separately
            $paidAmount = 0;
            if ($method === PaymentMethodEnum::CASH) {
                $paidAmount = $methodBookings
                    ->where('payment_status', PaymentStatusEnum::PAID->value)
                    ->where('status', BookingStatusEnum::CONFIRMED->value)
                    ->sum('final_amount');
            }

            $paymentMethods[$method->value] = [
                'label' => $method->getLabel(),
                'count' => $methodCount,
                'amount' => (float) $methodAmount,
                'paid_amount' => (float) $paidAmount, // Only relevant for cash
            ];
        }

        // Channel breakdown
        $channels = $bookings->groupBy('channel')->map(function ($group) {
            return [
                'count' => $group->count(),
                'amount' => $group->sum('final_amount'),
            ];
        });

        return [
            'bookings' => [
                'total' => $totalBookings,
                'advance' => $advanceBookings,
                'regular' => $regularBookings,
            ],
            'revenue' => [
                'total_revenue' => (float) $totalRevenue,
                'advance_revenue' => (float) $advanceRevenue,
                'regular_revenue' => (float) $regularRevenue,
            ],
            'cash' => [
                'cash_in_hand' => (float) $cashInHand,
                'net_balance' => (float) $netBalance,
            ],
            'expenses' => [
                'total_expenses' => (float) $totalExpenses,
            ],
            'payment_methods' => $paymentMethods,
            'channels' => $channels,
        ];
    }

    public function cancellationReport(): View
    {
        $this->authorize('view terminal reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAllReports = $user->can('view all booking reports');
        $hasTerminalAssigned = (bool) $user->terminal_id;

        if ($canViewAllReports) {
            $terminals = Terminal::where('status', TerminalEnum::ACTIVE->value)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
            $canSelectTerminal = true;
        } else {
            abort_if(! $hasTerminalAssigned, 403, 'You do not have access to any terminal reports.');

            $terminals = Terminal::where('id', $user->terminal_id)
                ->where('status', TerminalEnum::ACTIVE->value)
                ->get(['id', 'name', 'code']);
            $canSelectTerminal = false;
        }

        if ($canViewAllReports) {
            $users = User::whereHas('bookedBookings')
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        } else {
            $users = User::where('id', $user->id)
                ->get(['id', 'name', 'email']);
        }

        $paymentMethods = collect(PaymentMethodEnum::cases())->map(function ($method) {
            return [
                'value' => $method->value,
                'label' => $method->getLabel(),
            ];
        })->toArray();

        $channels = ChannelEnum::cases();

        return view('admin.terminal-reports.cancellation-report', [
            'terminals' => $terminals,
            'canSelectTerminal' => $canSelectTerminal,
            'canViewAllReports' => $canViewAllReports,
            'users' => $users,
            'selectedUserId' => $canViewAllReports ? null : $user->id,
            'paymentMethods' => $paymentMethods,
            'channels' => $channels,
        ]);
    }

    public function getCancellationData(Request $request): JsonResponse
    {
        $this->authorize('view terminal reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAllReports = $user->can('view all booking reports');
        $hasTerminalAssigned = (bool) $user->terminal_id;

        $validationRules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'route_id' => 'nullable|exists:routes,id',
        ];

        if ($canViewAllReports) {
            $validationRules['terminal_id'] = 'required|exists:terminals,id';
            $validationRules['cancelled_by_user_id'] = 'nullable|exists:users,id';
        } else {
            $validationRules['terminal_id'] = 'nullable';
            $validationRules['cancelled_by_user_id'] = 'nullable';
        }

        $validated = $request->validate($validationRules);

        if ($canViewAllReports) {
            $terminalId = $validated['terminal_id'];
            if (! $terminalId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Terminal ID is required',
                ], 400);
            }
        } else {
            abort_if(! $hasTerminalAssigned, 403, 'You do not have access to any terminal reports.');

            if ($request->filled('terminal_id') && (int) $request->input('terminal_id') !== $user->terminal_id) {
                abort(403, 'You are not allowed to access this terminal.');
            }

            if ($request->filled('cancelled_by_user_id') && (int) $request->input('cancelled_by_user_id') !== $user->id) {
                abort(403, 'You are not allowed to view other user reports.');
            }

            $terminalId = $user->terminal_id;
        }

        $terminal = Terminal::findOrFail($terminalId);

        // Build date range
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        // Get cancelled bookings
        $query = Booking::query()
            ->whereHas('fromStop', function ($q) use ($terminalId) {
                $q->where('terminal_id', $terminalId);
            })
            ->whereNotNull('cancelled_at')
            ->whereBetween('cancelled_at', [$startDate, $endDate])
            ->with([
                'fromStop.terminal',
                'toStop.terminal',
                'seats',
                'passengers',
                'user',
                'bookedByUser',
                'cancelledByUser',
                'trip.route',
            ]);

        // Apply filters
        if ($request->filled('cancelled_by_user_id')) {
            $query->where('cancelled_by_user_id', $request->cancelled_by_user_id);
        }

        if ($request->filled('route_id')) {
            $query->whereHas('trip.route', function ($q) use ($request) {
                $q->where('id', $request->route_id);
            });
        }

        $cancelledBookings = $query->get();

        // Calculate statistics
        $stats = $this->calculateCancellationStats($cancelledBookings);

        // Get cancellation reasons
        $cancellationReasons = $cancelledBookings
            ->whereNotNull('cancellation_reason')
            ->groupBy('cancellation_reason')
            ->map(function ($group) {
                return [
                    'reason' => $group->first()->cancellation_reason,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->values();

        return response()->json([
            'success' => true,
            'terminal' => [
                'id' => $terminal->id,
                'name' => $terminal->name,
                'code' => $terminal->code,
            ],
            'stats' => $stats,
            'cancellation_reasons' => $cancellationReasons,
        ]);
    }

    public function getCancellationBookingsData(Request $request)
    {
        $this->authorize('view terminal reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAllReports = $user->can('view all booking reports');
        $hasTerminalAssigned = (bool) $user->terminal_id;

        $validationRules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'route_id' => 'nullable|exists:routes,id',
        ];

        if ($canViewAllReports) {
            $validationRules['terminal_id'] = 'required|exists:terminals,id';
        } else {
            $validationRules['terminal_id'] = 'nullable';
        }

        $validated = $request->validate($validationRules);

        if ($canViewAllReports) {
            $terminalId = $validated['terminal_id'];
            if (! $terminalId) {
                return response()->json(['error' => 'Terminal ID is required'], 400);
            }
        } else {
            abort_if(! $hasTerminalAssigned, 403, 'You do not have access to any terminal reports.');
            $terminalId = $user->terminal_id;
        }

        // Build date range
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        $query = Booking::query()
            ->whereHas('fromStop', function ($q) use ($terminalId) {
                $q->where('terminal_id', $terminalId);
            })
            ->whereNotNull('cancelled_at')
            ->whereBetween('cancelled_at', [$startDate, $endDate])
            ->with([
                'fromStop.terminal',
                'toStop.terminal',
                'seats',
                'passengers',
                'user',
                'bookedByUser',
                'cancelledByUser',
                'trip.route',
            ]);

        // Apply filters
        if ($request->filled('cancelled_by_user_id')) {
            $query->where('cancelled_by_user_id', $request->cancelled_by_user_id);
        }

        if ($request->filled('cancelled_by_type')) {
            $query->where('cancelled_by_type', $request->cancelled_by_type);
        }

        if ($request->filled('route_id')) {
            $query->whereHas('trip.route', function ($q) use ($request) {
                $q->where('id', $request->route_id);
            });
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('is_advance')) {
            $query->where('is_advance', $request->is_advance === '1');
        }

        return DataTables::of($query)
            ->addColumn('booking_number', function (Booking $booking) {
                return '<span class="badge bg-danger">#'.$booking->booking_number.'</span>';
            })
            ->addColumn('created_at', function (Booking $booking) {
                return $booking->created_at->format('d M Y, H:i');
            })
            ->addColumn('cancelled_at', function (Booking $booking) {
                return $booking->cancelled_at ? $booking->cancelled_at->format('d M Y, H:i') : '-';
            })
            ->addColumn('route', function (Booking $booking) {
                $from = $booking->fromStop?->terminal?->code ?? 'N/A';
                $to = $booking->toStop?->terminal?->code ?? 'N/A';

                return '<strong>'.$from.' → '.$to.'</strong>';
            })
            ->addColumn('passengers', function (Booking $booking) {
                $passengerNames = $booking->passengers->pluck('name')->join(', ');

                if (empty($passengerNames)) {
                    return '<span class="text-muted small">No passengers</span>';
                }

                return '<div class="text-nowrap small">'.$passengerNames.'</div>';
            })
            ->addColumn('seats', function (Booking $booking) {
                $seatNumbers = $booking->seats->pluck('seat_number')->join(', ');

                return '<span class="badge bg-secondary">'.$seatNumbers.'</span>';
            })
            ->addColumn('channel', function (Booking $booking) {
                try {
                    $channel = ChannelEnum::from($booking->channel ?? '');

                    return '<span class="badge '.$channel->getBadge().'"><i class="'.$channel->getIcon().'"></i> '.$channel->getLabel().'</span>';
                } catch (\ValueError $e) {
                    return '<span class="badge bg-secondary">'.($booking->channel ?? 'N/A').'</span>';
                }
            })
            ->addColumn('is_advance', function (Booking $booking) {
                if ($booking->is_advance) {
                    return '<span class="badge bg-success"><i class="bx bx-check"></i> Yes</span>';
                }

                return '<span class="badge bg-secondary"><i class="bx bx-x"></i> No</span>';
            })
            ->addColumn('payment_method', function (Booking $booking) {
                try {
                    $method = PaymentMethodEnum::from($booking->payment_method ?? '');

                    return '<span class="badge '.$method->getBadge().'"><i class="'.$method->getIcon().'"></i> '.$method->getLabel().'</span>';
                } catch (\ValueError $e) {
                    return '<span class="badge bg-secondary">'.ucfirst($booking->payment_method ?? 'Unknown').'</span>';
                }
            })
            ->addColumn('amount', function (Booking $booking) {
                return '<strong class="text-danger">PKR '.number_format($booking->final_amount, 0).'</strong>';
            })
            ->addColumn('cancelled_by', function (Booking $booking) {
                $cancelledBy = $booking->cancelledByUser?->name ?? 'System';
                $cancelledByType = ucfirst($booking->cancelled_by_type ?? 'unknown');

                return '<div class="small">
                    <strong>'.$cancelledBy.'</strong><br>
                    <span class="badge bg-warning">'.$cancelledByType.'</span>
                </div>';
            })
            ->addColumn('cancellation_reason', function (Booking $booking) {
                $reason = $booking->cancellation_reason ?? 'No reason provided';

                if (strlen($reason) > 50) {
                    return '<span class="small" title="'.$reason.'">'.substr($reason, 0, 50).'...</span>';
                }

                return '<span class="small">'.$reason.'</span>';
            })
            ->rawColumns(['booking_number', 'route', 'passengers', 'seats', 'channel', 'is_advance', 'payment_method', 'amount', 'cancelled_by', 'cancellation_reason'])
            ->make(true);
    }

    public function exportCancellationReport(Request $request)
    {
        $this->authorize('view terminal reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAllReports = $user->can('view all booking reports');
        $hasTerminalAssigned = (bool) $user->terminal_id;

        $validationRules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'route_id' => 'nullable|exists:routes,id',
        ];

        if ($canViewAllReports) {
            $validationRules['terminal_id'] = 'required|exists:terminals,id';
            $validationRules['cancelled_by_user_id'] = 'nullable|exists:users,id';
        } else {
            $validationRules['terminal_id'] = 'nullable';
            $validationRules['cancelled_by_user_id'] = 'nullable';
        }

        $validated = $request->validate($validationRules);

        if ($canViewAllReports) {
            $terminalId = $validated['terminal_id'];
        } else {
            abort_if(! $hasTerminalAssigned, 403, 'You do not have access to any terminal reports.');
            $terminalId = $user->terminal_id;
        }

        $terminal = Terminal::findOrFail($terminalId);

        // Build date range
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        // Get cancelled bookings with all filters
        $query = Booking::query()
            ->whereHas('fromStop', function ($q) use ($terminalId) {
                $q->where('terminal_id', $terminalId);
            })
            ->whereNotNull('cancelled_at')
            ->whereBetween('cancelled_at', [$startDate, $endDate])
            ->with([
                'fromStop.terminal',
                'toStop.terminal',
                'seats',
                'passengers',
                'user',
                'bookedByUser',
                'cancelledByUser',
                'trip.route',
            ]);

        // Apply all filters
        if ($request->filled('cancelled_by_user_id')) {
            $query->where('cancelled_by_user_id', $request->cancelled_by_user_id);
        }

        if ($request->filled('cancelled_by_type')) {
            $query->where('cancelled_by_type', $request->cancelled_by_type);
        }

        if ($request->filled('route_id')) {
            $query->whereHas('trip.route', function ($q) use ($request) {
                $q->where('id', $request->route_id);
            });
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('is_advance')) {
            $query->where('is_advance', $request->is_advance === '1');
        }

        $cancelledBookings = $query->get();
        $stats = $this->calculateCancellationStats($cancelledBookings);

        // Get cancellation reasons
        $cancellationReasons = $cancelledBookings
            ->whereNotNull('cancellation_reason')
            ->groupBy('cancellation_reason')
            ->map(function ($group) {
                return [
                    'reason' => $group->first()->cancellation_reason,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        $generalSettings = GeneralSetting::first();

        $data = [
            'terminal' => $terminal,
            'cancelledBookings' => $cancelledBookings,
            'stats' => $stats,
            'cancellationReasons' => $cancellationReasons,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generalSettings' => $generalSettings,
            'filters' => $request->all(),
        ];

        // TODO: Install barryvdh/laravel-dompdf package for PDF export
        // $pdf = Pdf::loadView('admin.terminal-reports.export-cancellation-pdf', $data);
        // $pdf->setPaper('A4', 'landscape');
        // $filename = 'cancellation-report-'.$terminal->code.'-'.date('Y-m-d').'.pdf';
        // return $pdf->download($filename);

        // Temporary: Return view for now until PDF package is installed
        return view('admin.terminal-reports.export-cancellation-pdf', $data);
    }

    private function calculateCancellationStats($cancelledBookings)
    {
        $totalCancellations = $cancelledBookings->count();
        $totalRefundAmount = $cancelledBookings->sum('final_amount');
        $totalCancelledSeats = $cancelledBookings->sum(function ($booking) {
            return $booking->seats->count();
        });
        $totalCancelledPassengers = $cancelledBookings->sum('total_passengers');

        // Group by cancelled_by_type
        $byCancelledType = $cancelledBookings->groupBy('cancelled_by_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'amount' => $group->sum('final_amount'),
            ];
        });

        // Group by payment method
        $byPaymentMethod = $cancelledBookings->groupBy('payment_method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'amount' => $group->sum('final_amount'),
            ];
        });

        return [
            'total_cancellations' => $totalCancellations,
            'total_refund_amount' => $totalRefundAmount,
            'total_cancelled_seats' => $totalCancelledSeats,
            'total_cancelled_passengers' => $totalCancelledPassengers,
            'by_cancelled_type' => $byCancelledType,
            'by_payment_method' => $byPaymentMethod,
        ];
    }

    public function employeeBookingsReport(): View
    {
        $this->authorize('view terminal reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAllReports = $user->can('view all booking reports');
        $hasTerminalAssigned = (bool) $user->terminal_id;

        // Get terminals
        if ($canViewAllReports) {
            $terminals = Terminal::where('status', TerminalEnum::ACTIVE->value)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
            $canSelectTerminal = true;
        } else {
            abort_if(! $hasTerminalAssigned, 403, 'You do not have access to any terminal reports.');

            $terminals = Terminal::where('id', $user->terminal_id)
                ->where('status', TerminalEnum::ACTIVE->value)
                ->get(['id', 'name', 'code']);
            $canSelectTerminal = false;
        }

        // Get employees
        $employeeRole = Role::where('name', 'Employee')->first();
        if ($canViewAllReports) {
            $employees = User::whereHas('roles', function ($query) use ($employeeRole) {
                $query->where('role_id', $employeeRole->id);
            })
                ->with('terminal')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'terminal_id']);
        } else {
            $employees = User::whereHas('roles', function ($query) use ($employeeRole) {
                $query->where('role_id', $employeeRole->id);
            })
                ->where('terminal_id', $user->terminal_id)
                ->with('terminal')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'terminal_id']);
        }

        $bookingStatuses = BookingStatusEnum::cases();
        $paymentStatuses = PaymentStatusEnum::cases();
        $channels = ChannelEnum::cases();
        $paymentMethods = PaymentMethodEnum::options();

        return view('admin.terminal-reports.employee-bookings-report', [
            'terminals' => $terminals,
            'employees' => $employees,
            'canSelectTerminal' => $canSelectTerminal,
            'canViewAllReports' => $canViewAllReports,
            'selectedEmployeeId' => $canViewAllReports ? null : ($user->isEmployee() ? $user->id : null),
            'bookingStatuses' => $bookingStatuses,
            'paymentStatuses' => $paymentStatuses,
            'channels' => $channels,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function getEmployeeBookingsData(Request $request): JsonResponse
    {
        $this->authorize('view terminal reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAllReports = $user->can('view all booking reports');
        $hasTerminalAssigned = (bool) $user->terminal_id;

        $validationRules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'employee_id' => 'nullable|exists:users,id',
        ];

        if ($canViewAllReports) {
            $validationRules['terminal_id'] = 'nullable|exists:terminals,id';
        } else {
            $validationRules['terminal_id'] = 'nullable';
        }

        $validated = $request->validate($validationRules);

        // Get employee role
        $employeeRole = Role::where('name', 'Employee')->first();

        // Build query for employees
        $employeeQuery = User::whereHas('roles', function ($query) use ($employeeRole) {
            $query->where('role_id', $employeeRole->id);
        });

        if ($canViewAllReports) {
            if ($request->filled('terminal_id')) {
                $employeeQuery->where('terminal_id', $validated['terminal_id']);
            }
            if ($request->filled('employee_id')) {
                $employeeQuery->where('id', $validated['employee_id']);
            }
        } else {
            abort_if(! $hasTerminalAssigned, 403, 'You do not have access to any terminal reports.');
            $employeeQuery->where('terminal_id', $user->terminal_id);
            if ($request->filled('employee_id') && (int) $request->input('employee_id') !== $user->id) {
                abort(403, 'You are not allowed to view other employee reports.');
            }
        }

        $employees = $employeeQuery->with('terminal')->get();

        // Build date range
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        // Get bookings for each employee
        $employeeStats = [];
        foreach ($employees as $employee) {
            $bookings = Booking::where('booked_by_user_id', $employee->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with([
                    'trip.route',
                    'fromStop.terminal',
                    'toStop.terminal',
                    'seats',
                    'passengers',
                ]);

            // Apply additional filters if provided
            if ($request->filled('status')) {
                $bookings->where('status', $request->status);
            }
            if ($request->filled('payment_status')) {
                $bookings->where('payment_status', $request->payment_status);
            }
            if ($request->filled('payment_method')) {
                $bookings->where('payment_method', $request->payment_method);
            }
            if ($request->filled('channel')) {
                $bookings->where('channel', $request->channel);
            }
            if ($request->filled('is_advance')) {
                $bookings->where('is_advance', $request->is_advance);
            }

            $bookingsCollection = $bookings->get();

            // Calculate daily and monthly stats
            $dailyStats = $bookingsCollection->groupBy(function ($booking) {
                return $booking->created_at->format('Y-m-d');
            })->map(function ($dayBookings) {
                return [
                    'date' => $dayBookings->first()->created_at->format('Y-m-d'),
                    'count' => $dayBookings->count(),
                    'amount' => $dayBookings->sum('final_amount'),
                ];
            })->values();

            $monthlyStats = $bookingsCollection->groupBy(function ($booking) {
                return $booking->created_at->format('Y-m');
            })->map(function ($monthBookings) {
                return [
                    'month' => $monthBookings->first()->created_at->format('Y-m'),
                    'count' => $monthBookings->count(),
                    'amount' => $monthBookings->sum('final_amount'),
                ];
            })->values();

            $employeeStats[] = [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'terminal' => $employee->terminal ? [
                        'id' => $employee->terminal->id,
                        'name' => $employee->terminal->name,
                        'code' => $employee->terminal->code,
                    ] : null,
                ],
                'total_bookings' => $bookingsCollection->count(),
                'total_amount' => $bookingsCollection->sum('final_amount'),
                'daily_stats' => $dailyStats,
                'monthly_stats' => $monthlyStats,
            ];
        }

        return response()->json([
            'success' => true,
            'employee_stats' => $employeeStats,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);
    }

    public function getEmployeeBookingsTableData(Request $request)
    {
        $this->authorize('view terminal reports');

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $canViewAllReports = $user->can('view all booking reports');
        $hasTerminalAssigned = (bool) $user->terminal_id;

        $validationRules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'employee_id' => 'nullable|exists:users,id',
        ];

        if ($canViewAllReports) {
            $validationRules['terminal_id'] = 'nullable|exists:terminals,id';
        } else {
            $validationRules['terminal_id'] = 'nullable';
        }

        $validated = $request->validate($validationRules);

        // Get employee role
        $employeeRole = Role::where('name', 'Employee')->first();

        // Build query for bookings - only bookings created by employees
        $query = Booking::query()
            ->whereHas('bookedByUser', function ($q) use ($employeeRole) {
                $q->whereHas('roles', function ($roleQuery) use ($employeeRole) {
                    $roleQuery->where('role_id', $employeeRole->id);
                });
            })
            ->with([
                'trip.route',
                'fromStop.terminal',
                'toStop.terminal',
                'seats',
                'passengers',
                'bookedByUser.terminal',
            ]);

        // Build date range
        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();
        $query->whereBetween('created_at', [$startDate, $endDate]);

        // Apply filters
        if ($canViewAllReports) {
            if ($request->filled('terminal_id')) {
                $query->where('terminal_id', $validated['terminal_id']);
            }
            if ($request->filled('employee_id')) {
                $query->where('booked_by_user_id', $validated['employee_id']);
            }
        } else {
            abort_if(! $hasTerminalAssigned, 403, 'You do not have access to any terminal reports.');
            $query->where('terminal_id', $user->terminal_id);
            if ($request->filled('employee_id') && (int) $request->input('employee_id') !== $user->id) {
                abort(403, 'You are not allowed to view other employee reports.');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }
        if ($request->filled('is_advance')) {
            $query->where('is_advance', $request->is_advance);
        }

        return DataTables::eloquent($query)
            ->addColumn('booking_number_formatted', function ($booking) {
                return '#' . str_pad($booking->booking_number, 6, '0', STR_PAD_LEFT);
            })
            ->addColumn('date_time', function ($booking) {
                return $booking->created_at->format('d M Y h:i A');
            })
            ->addColumn('route_info', function ($booking) {
                if ($booking->trip && $booking->trip->route) {
                    return $booking->trip->route->name . ($booking->trip->route->code ? ' (' . $booking->trip->route->code . ')' : '');
                }
                return 'N/A';
            })
            ->addColumn('from_to', function ($booking) {
                $from = $booking->fromStop && $booking->fromStop->terminal ? $booking->fromStop->terminal->name : 'N/A';
                $to = $booking->toStop && $booking->toStop->terminal ? $booking->toStop->terminal->name : 'N/A';
                return $from . ' → ' . $to;
            })
            ->addColumn('passengers_count', function ($booking) {
                return $booking->total_passengers ?? $booking->passengers->count();
            })
            ->addColumn('seats_info', function ($booking) {
                return $booking->seats->pluck('seat_number')->join(', ') ?: 'N/A';
            })
            ->addColumn('employee_info', function ($booking) {
                if ($booking->bookedByUser) {
                    $terminal = $booking->bookedByUser->terminal;
                    $terminalInfo = $terminal ? $terminal->name . ($terminal->code ? ' (' . $terminal->code . ')' : '') : 'No Terminal';
                    return '<div>
                        <div class="fw-bold">' . e($booking->bookedByUser->name) . '</div>
                        <small class="text-muted">' . e($terminalInfo) . '</small>
                    </div>';
                }
                return 'N/A';
            })
            ->addColumn('status_badge', function ($booking) {
                $statusEnum = BookingStatusEnum::tryFrom($booking->status);
                $statusLabel = $statusEnum ? $statusEnum->getLabel() : $booking->status;
                $statusColor = match ($booking->status) {
                    'confirmed' => 'success',
                    'cancelled' => 'danger',
                    'hold' => 'warning',
                    default => 'secondary',
                };
                return '<span class="badge bg-' . $statusColor . '">' . e($statusLabel) . '</span>';
            })
            ->addColumn('payment_status_badge', function ($booking) {
                $paymentStatusEnum = PaymentStatusEnum::tryFrom($booking->payment_status);
                $paymentStatusLabel = $paymentStatusEnum ? $paymentStatusEnum->getLabel() : $booking->payment_status;
                $paymentStatusColor = match ($booking->payment_status) {
                    'paid' => 'success',
                    'pending' => 'warning',
                    'refunded' => 'info',
                    default => 'secondary',
                };
                return '<span class="badge bg-' . $paymentStatusColor . '">' . e($paymentStatusLabel) . '</span>';
            })
            ->addColumn('amount_formatted', function ($booking) {
                return 'PKR ' . number_format($booking->final_amount, 2);
            })
            ->editColumn('is_advance', function ($booking) {
                return $booking->is_advance ? '<span class="badge bg-info">Yes</span>' : '<span class="badge bg-secondary">No</span>';
            })
            ->rawColumns(['employee_info', 'status_badge', 'payment_status_badge', 'is_advance'])
            ->make(true);
    }
}
