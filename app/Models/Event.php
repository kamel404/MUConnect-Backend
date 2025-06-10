<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SavedItem;

class Event extends Model
{
    use HasFactory;

    //todo switch category to enum
    protected $fillable = [
        'user_id',
        'title',
        'category',
        'date',
        'time',
        'location',
        'organizer',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function savedBy()
    {
        return $this->morphMany(SavedItem::class, 'saveable');
    }
}
