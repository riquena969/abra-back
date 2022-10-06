<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'title',
        'notes',
        'all_day',
        'start',
        'end',
        'seller_id',
        'updated_by',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
