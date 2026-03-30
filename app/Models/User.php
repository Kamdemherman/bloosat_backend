<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'role_id', 'name', 'email', 'password', 'phone', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['is_active' => 'boolean'];

    public function role()    { return $this->belongsTo(Role::class); }
    public function clients() { return $this->hasMany(Client::class, 'commercial_id'); }
    public function logs()    { return $this->hasMany(SystemLog::class); }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->role?->permissions ?? [];
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }
}
