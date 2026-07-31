<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
    ];

    public function getSlugAttribute(): string
    {
        return $this->name;
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'application_role');
    }
}
