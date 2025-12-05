<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\Route;
use App\Http\Controllers\Controller;
use App\Http\Requests\RouteSearchRequest;
use App\Http\Resources\Customer\RouteResource;

class RouteController extends Controller
{
    public function search(RouteSearchRequest $request)
    {
        $validated = $request->validated();

        $from = $validated['from_city_id'];
        $to   = $validated['to_city_id'];

        // =============== 1. DIRECT MAIN ROUTE ===================
        $route = Route::where('from_city_id', $from)
                      ->where('to_city_id', $to)
                      ->first();

        // =============== 2. REVERSE MAIN ROUTE ==================
        if (!$route) {
            $route = Route::where('from_city_id', $to)
                          ->where('to_city_id', $from)
                          ->first();
        }

        // ============ 3. TO-CITY exists in ROUTE STOPS ==========
        if (!$route) {
            $route = Route::where('from_city_id', $from)
                          ->whereHas('stops.terminal', function ($q) use ($to) {
                              $q->where('city_id', $to);
                          })
                          ->first();
        }

        // ============ 4. BOTH cities are stops ==================
        if (!$route) {
            $route = Route::whereHas('stops.terminal', function ($q) use ($from) {
                                $q->where('city_id', $from);
                           })
                           ->whereHas('stops.terminal', function ($q) use ($to) {
                                $q->where('city_id', $to);
                           })
                           ->first();
        }

        // ============= 5. NO ROUTE FOUND ========================
        if (!$route) {
            return response()->json([
                "status" => false,
                "message" => "No route or stops found between these cities."
            ], 404);
        }

        // Load stops
        $route->load(['fromCity', 'toCity', 'routeStops.terminal.city']);

        // ================= SLICE STOPS ==========================

        $stops = $route->routeStops->values();

        $fromIndex = $stops->search(fn($s) => $s->terminal->city_id == $from);
        $toIndex   = $stops->search(fn($s) => $s->terminal->city_id == $to);

        if ($from == $route->from_city_id) $fromIndex = 0;
        if ($to == $route->to_city_id)     $toIndex   = $stops->count() - 1;

        if ($fromIndex === false || $toIndex === false) {
            $filteredStops = $stops;
        } else {
            $filteredStops = $stops->slice($fromIndex, ($toIndex - $fromIndex + 1));
        }

        // Inject filtered stops
        $route->filteredStops = $filteredStops->values();

        // Return API Resource
        return new RouteResource($route);
    }
}

