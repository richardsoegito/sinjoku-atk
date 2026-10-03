<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;

/**
 * @property int|string $id
 * @property string $name
 * @property string $guard_name
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, User> $users
 */
class Role extends \Spatie\Permission\Models\Role {}
