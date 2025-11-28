<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HolidayController extends Controller
{
    /**
     * Display a listing of the holidays.
     */
    public function index(): View
    {
        $holidays = Holiday::query()
            ->orderByDesc('start_date')
            ->paginate(20);

        return view('admin.holidays.index', compact('holidays'));
    }

    /**
     * Show the form for creating a new holiday.
     */
    public function create(): View
    {
        return view('admin.holidays.create');
    }

    /**
     * Store a newly created holiday in storage.
     */
    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        Holiday::create($data);

        return redirect()
            ->route('admin.holidays.index')
            ->with('success', 'Holiday created successfully.');
    }

    /**
     * Show the form for editing the specified holiday.
     */
    public function edit(Holiday $holiday): View
    {
        return view('admin.holidays.edit', compact('holiday'));
    }

    /**
     * Update the specified holiday in storage.
     */
    public function update(UpdateHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $holiday->update($data);

        return redirect()
            ->route('admin.holidays.index')
            ->with('success', 'Holiday updated successfully.');
    }

    /**
     * Remove the specified holiday from storage.
     */
    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return redirect()
            ->route('admin.holidays.index')
            ->with('success', 'Holiday deleted successfully.');
    }
}
