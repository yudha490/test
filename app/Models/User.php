<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'points',
        'password',
        'phone_number',
        'birth_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date', // Cast birth_date to a date object
    ];

    /**
     * Get the user missions associated with the user.
     */
    public function userMissions()
    {
        return $this->hasMany(UserMission::class);
    }

    /**
     * Get the rewards associated with the user.
     */
    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    /**
     * Get the voucher exchanges associated with the user.
     */
    public function voucherExchanges()
    {
        return $this->hasMany(VoucherExchange::class);
    }
}
