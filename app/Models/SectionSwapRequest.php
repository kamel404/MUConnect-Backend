<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionSwapRequest extends Model
{

    protected $fillable = ['request_id', 'applicant_id', 'message'];

    public function request()
    {
        return $this->belongsTo(SectionSwapRequest::class);
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }
}
