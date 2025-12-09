<?php

namespace App\Livewire\Customer;

use App\Models\GeneralSetting;
use App\Models\Terminal;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class CustomerBookingSearch extends Component
{
    public $fromTerminalId;

    public $toTerminalId;

    public $travelDate;

    public $terminals = [];

    public $minDate;

    public $maxDate;

    public function mount(): void
    {
        $generalSettings = GeneralSetting::first();

        $this->minDate = now()->format('Y-m-d');
        $this->maxDate = $generalSettings && $generalSettings->advance_booking_enable
            ? now()->addDays($generalSettings->advance_booking_days ?? 7)->format('Y-m-d')
            : $this->minDate;

        $this->travelDate = $this->minDate;

        $this->terminals = Terminal::query()
            ->with('city:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'city_id'])
            ->map(function ($terminal) {
                return [
                    'id' => $terminal->id,
                    'name' => $terminal->name,
                    'code' => $terminal->code,
                    'city' => $terminal->city?->name,
                ];
            })
            ->toArray();
    }

    public function search(): void
    {
        $data = [
            'from_terminal_id' => $this->fromTerminalId,
            'to_terminal_id' => $this->toTerminalId,
            'travel_date' => $this->travelDate,
        ];

        Validator::make($data, [
            'from_terminal_id' => ['required', 'integer', 'exists:terminals,id'],
            'to_terminal_id' => ['required', 'integer', 'different:from_terminal_id', 'exists:terminals,id'],
            'travel_date' => ['required', 'date', 'after_or_equal:'.$this->minDate, 'before_or_equal:'.$this->maxDate],
        ])->validate();

        redirect()->route('customer.book.results', [
            'from_terminal_id' => $this->fromTerminalId,
            'to_terminal_id' => $this->toTerminalId,
            'date' => $this->travelDate,
        ]);
    }

    public function render()
    {
        return view('livewire.customer.customer-booking-search');
    }
}


