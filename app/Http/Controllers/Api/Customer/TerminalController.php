<?php

namespace App\Http\Controllers\Api\Customer;

use Carbon\Carbon;
use App\Models\City;
use App\Models\Fare;
use App\Models\Trip;
use App\Enums\CityEnum;
use App\Models\Booking;
use App\Models\Terminal;
use App\Models\RouteStop;
use App\Enums\PlatformEnum;
use App\Enums\TerminalEnum;
use Illuminate\Http\Request;
use App\Models\TimetableStop;
use App\Helpers\HolidayHelper;
use App\Models\GeneralSetting;
use App\Services\BookingService;
use App\Services\DiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\TripFactoryService;
use Illuminate\Support\Facades\Auth;
use App\Services\AvailabilityService;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Customer\BookingResource;

class TerminalController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private AvailabilityService $availabilityService,
        private TripFactoryService $tripFactoryService,
        private DiscountService $discountService
    ) {
    }

    /**
     * Search available trips for the given criteria.
     */
    public function cities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required']
        ]);

        try {
            $cities = City::query()
                ->select('id', 'name', 'code', 'status')
                ->where('status', CityEnum::ACTIVE->value)
                ->get();

            $sortedCities = $cities->sortBy('name')->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'code' => $city->code,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'cities' => $sortedCities,
                ],
                'message' => 'Cities fetched successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    public function terminals(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required']
        ]);

        try {
            $terminals = Terminal::query()
                ->select('id', 'city_id', 'name', 'code', 'address', 'phone', 'email', 'landmark', 'latitude', 'longitude', 'status')
                ->where('status', TerminalEnum::ACTIVE->value)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'terminals' => $terminals,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    public function terminalsByCity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'city_id' => ['required', 'exists:cities,id']
        ]);

            $terminals = Terminal::query()
                ->select('id', 'city_id', 'name', 'code', 'address', 'phone', 'email', 'landmark', 'latitude', 'longitude', 'status')
                ->where('status', TerminalEnum::ACTIVE->value)
                ->where('city_id', $validated['city_id'])
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'terminals' => $terminals,
                ],
                'message' => 'Terminals fetched successfully',
            ]);
    }

    public function general_info_admin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required']
        ]);

            $general_info = GeneralSetting::query()
                ->select('id', 'company_name', 'email', 'phone', 'alternate_phone', 'address', 'city', 'state', 'country', 'postal_code', 'website_url', 'logo', 'favicon', 'tagline', 'facebook_url', 'instagram_url', 'twitter_url', 'linkedin_url', 'youtube_url', 'support_email', 'support_phone', 'business_hours', 'advance_booking_enable', 'mobile_wallet_tax', 'is_active')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'general_info' => $general_info,
                ],
                'message' => 'General info fetched successfully',
            ]);
    }
}
