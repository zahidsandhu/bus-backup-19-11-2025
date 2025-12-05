<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteStopResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "sequence" => $this->sequence,
            "terminal_id" => $this->terminal->id,
            "terminal_name" => $this->terminal->name,
            "city" => $this->terminal->city->name,
            "online_booking_allowed" => $this->online_booking_allowed,
            "online_time_table" => $this->online_time_table,
        ];
    }
}

