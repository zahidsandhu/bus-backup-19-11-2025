<div>
    <form wire:submit.prevent="search">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">From Terminal</label>
                <select class="form-select" wire:model="fromTerminalId">
                    <option value="">Select Origin</option>
                    @foreach ($terminals as $terminal)
                        <option value="{{ $terminal['id'] }}">
                            {{ $terminal['city'] }} - {{ $terminal['name'] }} ({{ $terminal['code'] }})
                        </option>
                    @endforeach
                </select>
                @error('from_terminal_id')
                <small class="text-danger d-block">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">To Terminal</label>
                <select class="form-select" wire:model="toTerminalId">
                    <option value="">Select Destination</option>
                    @foreach ($terminals as $terminal)
                        <option value="{{ $terminal['id'] }}">
                            {{ $terminal['city'] }} - {{ $terminal['name'] }} ({{ $terminal['code'] }})
                        </option>
                    @endforeach
                </select>
                @error('to_terminal_id')
                <small class="text-danger d-block">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Travel Date</label>
                <input type="date"
                       class="form-control"
                       wire:model="travelDate"
                       min="{{ $minDate }}"
                       max="{{ $maxDate }}">
                @error('travel_date')
                <small class="text-danger d-block">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg">
                Search Trips
            </button>
        </div>
    </form>
</div>


