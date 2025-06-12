<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SavedItem;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'event_datetime',
        'location',
        'organizer',
        'description',
        'speaker_names',
        'image_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function savedBy()
    {
        return $this->morphMany(SavedItem::class, 'saveable');
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }
    
    public function attendees()
    {
        return $this->belongsToMany(User::class, 'event_registrations');
    }
}
