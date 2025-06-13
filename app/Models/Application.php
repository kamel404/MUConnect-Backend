<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\SectionRequest;

class Application extends Model
{
protected $fillable = ['request_id', 'user_id', 'status', 'reason'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

public function request() {
    return $this->belongsTo(SectionRequest::class, 'request_id');
}
    
}
