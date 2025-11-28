@php
    if ($seat > $totalSeats) return;

    $seatData = $seatMap[$seat] ?? ['number' => $seat, 'status' => 'available'];
    $isSelected = isset($selectedSeats[$seat]);
    $seatGender = $isSelected
        ? $selectedSeats[$seat]['gender'] ?? null
        : $seatData['gender'] ?? null;

    $status = $seatData['status'] ?? 'available';
    $isLockedByOtherUser = isset($lockedSeats[$seat]) && $lockedSeats[$seat] != auth()->id();

    if ($isLockedByOtherUser) $status = 'held';
@endphp

<button type="button"
    wire:click="selectSeat({{ $seat }})"
    class="seat-btn
        @if ($status === 'booked')
            @if ($seatGender === 'male') seat-booked-male
            @elseif($seatGender === 'female') seat-booked-female
            @else seat-booked-male @endif
        @elseif($status === 'held' || $isLockedByOtherUser)
            seat-held
        @elseif($isSelected)
            seat-selected
        @else
            seat-available
        @endif"
    @if ($status === 'booked' || $status === 'held' || $isLockedByOtherUser) disabled @endif>

    {{ $seat }}

    @if ($isSelected && $seatGender)
        <span class="seat-gender-badge {{ $seatGender === 'male' ? 'male-badge' : 'female-badge' }}">
            {{ $seatGender === 'male' ? '👨' : '👩' }}
        </span>
    @endif

    @if ($isLockedByOtherUser)
        <span class="seat-locked-badge">🔒</span>
    @endif
</button>
