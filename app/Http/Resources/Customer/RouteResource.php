<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "route_id" => $this->id,
            "from_city" => $this->fromCity->name,
            "to_city"   => $this->toCity->name,
            "direction" => $this->direction,
            "is_return_of" => $this->is_return_of,

            "stops" => RouteStopResource::collection($this->filteredStops),
        ];
    }
}
