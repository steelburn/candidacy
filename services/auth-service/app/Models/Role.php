<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'display_name', 'description'];

    // Many-to-many relationship with users
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    // Many-to-many relationship with permissions
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    // Check if role has a specific permission
    public function hasPermission($permission)
    {
        return $this->permissions()->where('name', $permission)->exists();
    }
}
