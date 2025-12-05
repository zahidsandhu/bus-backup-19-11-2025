<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'terminal_id',
        'sequence',
        'online_booking_allowed',
        'online_time_table',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'online_booking_allowed' => 'boolean',
        'online_time_table' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function terminal()
    {
        return $this->belongsTo(Terminal::class, 'terminal_id');
    }
}
