<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomVerifyEmailNotification;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements MustVerifyEmail, JWTSubject
{

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'region',
        'registration_domain',
        'status_id',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'email_verified',
        'type',
        'phone',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'status',
        'phones',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get email verification status as boolean.
     *
     * @return bool
     */
    public function getEmailVerifiedAttribute(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Get user type from status relationship.
     *
     * @return string|null
     */
    public function getTypeAttribute(): ?string
    {
        return $this->status?->value;
    }

    /**
     * Get primary phone number.
     *
     * @return string|null
     */
    public function getPhoneAttribute(): ?string
    {
        $primaryPhone = $this->phones->where('is_primary', true)->first();
        return $primaryPhone?->value ?? $this->phones->first()?->value;
    }

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Автоматически заполняем поле key при создании пользователя
        static::creating(function ($user) {
            if (empty($user->key)) {
                $user->key = Str::ulid();
            }
        });
    }

    /**
     * Get the user's phones.
     */
    public function phones()
    {
        return $this->hasMany(UserPhone::class);
    }

    /**
     * Get the status that the user belongs to.
     */
    public function status()
    {
        return $this->belongsTo(UserStatus::class, 'status_id');
    }

    /**
     * Get the company that the user belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmailNotification);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'status_id' => $this->status_id,
            'type' => $this->type,
            'email_verified' => $this->email_verified,
        ];
    }
}
