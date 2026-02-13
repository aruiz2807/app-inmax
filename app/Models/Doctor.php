<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'specialty_id',
        'license',
        'university',
        'address',
    ];

    /**
     * The attributes that are appended when model is retrieved
     *
     * @var array<int, string>
     */
    protected $appends = [
        'name',
        'email',
        'phone',
        'specialty',
    ];

    /**
     * Get the doctors's name
     */
    protected function getNameAttribute()
    {
        $user = User::find($this->user_id);

        return empty($user) ? 'N/D' : $user->name;
    }

    /**
     * Get the doctors's email
     */
    protected function getEmailAttribute()
    {
        $user = User::find($this->user_id);

        return empty($user) ? 'N/D' : $user->email;
    }

    /**
     * Get the doctors's phone
     */
    protected function getPhoneAttribute()
    {
        $user = User::find($this->user_id);

        return empty($user) ? 'N/D' : $user->phone;
    }

        /**
     * Get the doctors's specialty
     */
    protected function getSpecialtyAttribute()
    {
        $specialty = Specialty::find($this->specialty_id);

        return empty($specialty) ? 'N/D' : $specialty->name;
    }
}
