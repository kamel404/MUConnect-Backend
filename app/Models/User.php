<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Faculty;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens;

    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
        'avatar',
        'bio',
        'faculty_id',  // Add this
        'major_id',    // Add this
        'is_active',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'badges' => 'array',
        'is_active' => 'boolean',
    ];

    // Always eager load roles to prevent N+1 queries
    protected $with = ['roles'];

    // Add computed attributes to JSON responses
    protected $appends = ['primary_role', 'role_names'];

    public function getAvatarUrlAttribute()
    {
        return asset('storage/avatars/' . ($this->avatar ?? 'default.png'));
    }


    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function studyGroups()
    {
        return $this->belongsToMany(StudyGroup::class, 'study_group_user')->withTimestamps();
    }

    public function ledStudyGroups()
    {
        return $this->hasMany(StudyGroup::class, 'leader_id');
    }

    // Computed attributes
    public function getPrimaryRoleAttribute(): ?string
    {
        return $this->roles->first()?->name;
    }

    public function getRoleNames(): array
    {
        return $this->roles->pluck('name')->toArray();
    }

    /**
     * Get the role_names attribute for JSON serialization
     */
    public function getRoleNamesAttribute(): array
    {
        return $this->getRoleNames();
    }

    // Convenience methods (same performance as boolean columns)
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isModerator(): bool
    {
        return $this->hasRole('moderator');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function savedItems()
    {
        return $this->hasMany(SavedItem::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function registeredEvents()
    {
        return $this->belongsToMany(Event::class, 'event_registrations');
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function SectionRequests()
    {
        return $this->hasMany(SectionRequest::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
    
}
