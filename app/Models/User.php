<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $connection = 'mongodb';

    protected $collection = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'email_verified_at',
        'social_accounts',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'social_accounts', // Contains linked provider IDs — not needed by frontend
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
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'social_accounts'   => 'array',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getCachedPermissions());
    }

    public function getCachedPermissions(): array
    {
        if (!$this->id) {
            return [];
        }

        return Cache::remember('user_permissions_' . $this->id, 60, function () {
            $permissions = [];
            foreach ($this->roles as $role) {
                foreach ($role->permissions ?? [] as $permission) {
                    if ($extracted = $this->extractPermissionName($permission)) {
                        $permissions[] = $extracted;
                    }
                }
            }

            return array_values(array_unique($permissions));
        });
    }

    private function extractPermissionName(mixed $permission): ?string
    {
        if (is_string($permission)) {
            return $permission;
        }

        if (is_array($permission) && isset($permission['name'])) {
            return $permission['name'];
        }

        return null;
    }

    /**
     * Sync user roles and bust the permission cache so the change is immediately visible.
     */
    public function syncRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
        Cache::forget('user_permissions_' . $this->id);
    }
}
