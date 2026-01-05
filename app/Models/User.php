<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'sanctum';

    protected $fillable = [
        'fname',
        'mname',
        'lname',
        'email',
        'password',
        'avatar',
        'verification_code',
        'is_active',
        'mobile',
        'phone',
        'birth_date',
        'address_street',
        'address_city',
        'address_municipality',
        'address_province',
        'address_zip',
        'ecredits',
        'provider',
        'provider_id',
        'social_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date'        => 'date',
        'is_active'         => 'boolean',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->fname} {$this->mname} {$this->lname}");
    }

}
