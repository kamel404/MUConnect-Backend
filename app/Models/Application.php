<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\SectionRequest;

class Application extends Model
{
    protected $fillable = ['request_id', 'user_id', 'status', 'reason'];

    // Define allowed status values for PostgreSQL compatibility
    public static $statusOptions = ['pending', 'accepted', 'declined', 'cancelled'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function request() {
        return $this->belongsTo(SectionRequest::class, 'request_id');
    }

    // Add validation for status field
    public function setStatusAttribute($value)
    {
        if (!in_array($value, self::$statusOptions)) {
            throw new \InvalidArgumentException("Invalid status: {$value}");
        }
        $this->attributes['status'] = $value;
    }
}
