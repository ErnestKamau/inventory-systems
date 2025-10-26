<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $tables = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'phone_number',
        'gender',
        'role',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at'=> 'datetime',
            'password' => 'hashed',
        ];
    }

    //All orders for this user
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    //query scope to filter only admins
    //User::admins()->get()
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    // query scope to filter only customers
    // Usage: User::customers()->get()
    public function scopeCustomers($query)
    {
        return $query->where('role', 'customer');
    }


    /* ========================================
     * ACCESSORS (Computed Properties)
     * ======================================== */

    /**
     * Check if user is an admin
     *
     * Usage: $user->is_admin
     */
    public function getIsAdminAttribute()
    {
        return $this->role === 'admin';
    }

    public function getIsCustomerAttribute()
    {
        return $this->role === 'customer';
    }

    /**
     * Get total number of orders placed
     *
     * Usage: $user->total_orders
     */
    public function getTotalOrdersAttribute()
    {
        return $this->orders()->count();
    }

    /* ========================================
     * MUTATORS (Auto-formatting on Save)
     * ======================================== */

    /**
     * Hash password before saving
     *
     * Auto-triggered when: $user->password = 'plaintext'
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password']=bcrypt($value);
    }

    public function setPhoneNumberAttribute($value)
    {
        $clean = preg_replace('/[^0-9]/', '', $value);

        // Convert local numbers (e.g., 0712...) to 254 format
        if (str_starts_with($clean, '0')) {
            $clean = '254' . substr($clean, 1);
        }

        $this->attributes['phone_number'] = $clean;
    }

    /* ========================================
     * HELPER METHODS
     * ======================================== */

    /**
     * Override default __toString
     */
    public function __toString()
    {
        return "{$this->username} ({$this->phone_number})";
    }



}
