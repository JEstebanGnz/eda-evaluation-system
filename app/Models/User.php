<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_external_client'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->BelongsToMany(Role::class);
    }

    public function role()
    {
        $user = $this;
        $actualRole = session('role');
        //Check if is still valid
        $userRoles = $user->roles;

        foreach ($userRoles as $role) {
            if ($actualRole === $role->id) {
                return $role;
            }
        }

        //If all of this has happened and hasn't returned, then it is because it is an external client, so then we return that role
//        if(count($userRoles)> 0){
//            return $userRoles[0];
//        }

        return (object)[
            'name' => 'cliente externo',
            'customId' => 1
        ];
    }

    public function hasOneRole(): bool
    {
        return count($this->roles) === 1;
    }

    public function hasRole(string $roleName): bool
    {
        try {
            $roleNumber = Role::getRoleNumber($roleName);
        } catch (\RuntimeException $e) {
            return false;
        }
        return $this->role()->customId >= $roleNumber;
    }

    public function  functionaryProfiles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FunctionaryProfile::class);
    }

    public function  positions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Position::class);
    }

    public function  commitments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Commitment::class);
    }

    public function dependencies(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->BelongsToMany(Dependency::class);
    }

    public function assessments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function formAnswers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FormAnswer::class);
    }

    public function aggregateAssessmentResults(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AggregateAssessmentResult::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('administrador');
    }

}

