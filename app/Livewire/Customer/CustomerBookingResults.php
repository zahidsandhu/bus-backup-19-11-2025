<?php

namespace App\Livewire\Customer;

use App\Models\Terminal;
use App\Models\TimetableStop;
use App\Models\Trip;
use App\Services\RouteDetectorService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CustomerBookingResults extends Component
{
    public $fromTerminalId;

    public $toTerminalId;

    public $travelDate;

    public $routeId;

    public $fromStopId;

    public $toStopId;

    public $direction;

    public $trips = [];

    public function mount(
        ?int $fromTerminalId = null,
        ?int $toTerminalId = null,
        ?string $date = null
    ): void {
        $this->fromTerminalId = $fromTerminalId ?? (int) request('from_terminal_id');
        $this->toTerminalId = $toTerminalId ?? (int) request('to_terminal_id');
        $this->travelDate = $date ?? (string) request('date');

        if (! $this->fromTerminalId || ! $this->toTerminalId || ! $this->travelDate) {
            throw ValidationException::withMessages([
                'search' => 'Missing search parameters. Please start again.',
            ]);
        }

        $detector = app(RouteDetectorService::class);
        $segment = $detector->detectRoute($this->fromTerminalId, $this->toTerminalId);

        $this->routeId = $segment['route_id'];
        $this->fromStopId = $segment['from_stop_id'];
        $this->toStopId = $segment['to_stop_id'];
        $this->direction = $segment['direction'];

        $this->loadTrips();
    }

    public function loadTrips(): void
    {
        $date = Carbon::parse($this->travelDate)->format('Y-m-d');

        $fromTerminalId = $this->fromTerminalId;
        $toTerminalId = $this->toTerminalId;
        $routeId = $this->routeId;

        $query = Trip::query()
            ->select([
                'trips.id',
                'trips.timetable_id',
                'trips.route_id',
                'trips.departure_date',
                'trips.departure_datetime',
                'trips.status',
                'ts_from.departure_time as departure_time',
                'ts_to.arrival_time as arrival_time',
            ])
            ->join('timetables', 'trips.timetable_id', '=', 'timetables.id')
            ->join('timetable_stops as ts_from', function ($join) use ($fromTerminalId) {
                $join->on('ts_from.timetable_id', '=', 'timetables.id')
                    ->where('ts_from.terminal_id', $fromTerminalId)
                    ->where('ts_from.online_time_table', true);
            })
            ->join('timetable_stops as ts_to', function ($join) use ($toTerminalId) {
                $join->on('ts_to.timetable_id', '=', 'timetables.id')
                    ->where('ts_to.terminal_id', $toTerminalId)
                    ->where('ts_to.online_time_table', true);
            })
            ->where('timetables.route_id', $routeId)
            ->whereDate('trips.departure_date', $date)
            ->whereIn('trips.status', ['scheduled', 'active'])
            ->whereColumn('ts_from.sequence', '<', 'ts_to.sequence')
            ->orderBy('ts_from.departure_time');

        $this->trips = $query->get()->map(function ($trip) {
            return [
                'id' => $trip->id,
                'departure_time' => $trip->departure_time,
                'arrival_time' => $trip->arrival_time,
            ];
        })->toArray();
    }

    public function selectTrip(int $tripId): void
    {
        redirect()->route('customer.book.seat-select', [
            'trip' => $tripId,
            'from_stop_id' => $this->fromStopId,
            'to_stop_id' => $this->toStopId,
            'from_terminal_id' => $this->fromTerminalId,
            'to_terminal_id' => $this->toTerminalId,
            'date' => $this->travelDate,
        ]);
    }

    public function getFromTerminalProperty(): ?Terminal
    {
        return Terminal::find($this->fromTerminalId);
    }

    public function getToTerminalProperty(): ?Terminal
    {
        return Terminal::find($this->toTerminalId);
    }

    public function render()
    {
        return view('livewire.customer.customer-booking-results', [
            'fromTerminal' => $this->fromTerminal,
            'toTerminal' => $this->toTerminal,
        ]);
    }
}


