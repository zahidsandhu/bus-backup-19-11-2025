<?php

namespace App\Services;

use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class RouteDetectorService
{
    /**
     * Detect a route and concrete RouteStop segment between two terminals.
     *
     * @return array{route_id:int, from_stop_id:int, to_stop_id:int, direction:string}
     */
    public function detectRoute(int $fromTerminalId, int $toTerminalId): array
    {
        if ($fromTerminalId === $toTerminalId) {
            throw ValidationException::withMessages([
                'to_terminal_id' => 'Origin and destination terminals must be different.',
            ]);
        }

        // 1) Try direct / forward route: from sequence < to sequence
        $forward = $this->findSegment(
            $fromTerminalId,
            $toTerminalId,
            fn (Builder $q) => $q->whereColumn('route_stops.sequence', '<', 'rs_to.sequence')
        );

        if ($forward) {
            return [
                'route_id' => (int) $forward->route_id,
                'from_stop_id' => (int) $forward->from_stop_id,
                'to_stop_id' => (int) $forward->to_stop_id,
                'direction' => 'forward',
            ];
        }

        // 2) Try reverse on same routes: from sequence > to sequence
        $reverse = $this->findSegment(
            $fromTerminalId,
            $toTerminalId,
            fn (Builder $q) => $q->whereColumn('route_stops.sequence', '>', 'rs_to.sequence')
        );

        if ($reverse) {
            return [
                'route_id' => (int) $reverse->route_id,
                'from_stop_id' => (int) $reverse->from_stop_id,
                'to_stop_id' => (int) $reverse->to_stop_id,
                'direction' => 'reverse',
            ];
        }

        // 3) Fallback using return routes (is_return_of)
        $returnRoute = $this->findUsingReturnRoute($fromTerminalId, $toTerminalId);

        if ($returnRoute) {
            return $returnRoute;
        }

        throw ValidationException::withMessages([
            'route' => 'No route found between selected terminals.',
        ]);
    }

    private function findSegment(int $fromTerminalId, int $toTerminalId, callable $directionConstraint)
    {
        $query = RouteStop::query()
            ->from('route_stops')
            ->join('route_stops as rs_to', function ($join) use ($toTerminalId) {
                $join->on('rs_to.route_id', '=', 'route_stops.route_id')
                    ->where('rs_to.terminal_id', $toTerminalId);
            })
            ->join('routes', 'routes.id', '=', 'route_stops.route_id')
            ->where('route_stops.terminal_id', $fromTerminalId)
            ->whereHas('route', function (Builder $q) {
                $q->where('status', 'active');
            })
            ->when(true, $directionConstraint)
            ->selectRaw('route_stops.route_id, route_stops.id as from_stop_id, rs_to.id as to_stop_id, route_stops.sequence as from_seq, rs_to.sequence as to_seq')
            ->orderBy('route_stops.sequence');
        return $query->first();
    }

    /**
     * Attempt detection using a route that is the return of another route.
     *
     * @return array|null
     */
    private function findUsingReturnRoute(int $fromTerminalId, int $toTerminalId): ?array
    {
        $route = Route::query()
            ->whereNotNull('is_return_of')
            ->where('status', 'active')
            ->whereHas('routeStops', function (Builder $q) use ($fromTerminalId) {
                $q->where('terminal_id', $fromTerminalId);
            })
            ->whereHas('routeStops', function (Builder $q) use ($toTerminalId) {
                $q->where('terminal_id', $toTerminalId);
            })
            ->with(['routeStops' => fn ($q) => $q->orderBy('sequence')])
            ->first();

        if (! $route) {
            return null;
        }

        /** @var Route $route */
        $fromStop = $route->routeStops->firstWhere('terminal_id', $fromTerminalId);
        $toStop = $route->routeStops->firstWhere('terminal_id', $toTerminalId);

        if (! $fromStop || ! $toStop) {
            return null;
        }

        $direction = $fromStop->sequence < $toStop->sequence ? 'forward' : 'reverse';

        return [
            'route_id' => (int) $route->id,
            'from_stop_id' => (int) $fromStop->id,
            'to_stop_id' => (int) $toStop->id,
            'direction' => $direction,
        ];
    }
}


