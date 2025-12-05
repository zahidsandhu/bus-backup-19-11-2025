<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class RouteSearchRequest extends FormRequest
{
    public function rules()
    {
        return [
            'from_city_id' => 'required|integer|exists:cities,id',
            'to_city_id'   => 'required|integer|exists:cities,id',
        ];
    }
}
