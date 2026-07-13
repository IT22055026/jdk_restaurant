<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['name', 'description', 'icon', 'route'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_module');
    }
}
