<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    protected $fillable = [
        'name',
        'description',
        'members',
        'logo',
        'upcoming_event',
    ];

    protected $casts = [
        'members' => 'integer',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'club_members', 'club_id', 'user_id')
            ->withPivot('name', 'picture')
            ->withTimestamps();
    }

    public function clubMembers()
    {
        return $this->hasMany(ClubMember::class);
    }

    public function upcomingEvent()
    {
        return $this->events()
            ->where('event_datetime', '>=', now())
            ->orderBy('event_datetime', 'asc')
            ->first();
    }

    public function getUpcomingEventAttribute()
    {
        return $this->events()
            ->where('event_datetime', '>=', now())
            ->orderBy('event_datetime', 'asc')
            ->first();
    }
}
