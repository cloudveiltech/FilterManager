<?php

namespace App\Traits;

trait HasRoles
{
    /**
     * Check if user has a role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Get the roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany('App\Role');
    }
}