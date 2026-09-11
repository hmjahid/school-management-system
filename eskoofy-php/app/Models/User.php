<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'name', 'email', 'phone', 'address', 'gender',
        'date_of_birth', 'photo', 'password', 'role_id',
        'email_verified_at', 'remember_token',
    ];

    protected array $hidden = ['password'];

    protected array $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function guardian()
    {
        return $this->hasOne(Guardian::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function schoolRole()
    {
        $role = $this->attributes['role'] ?? null;
        return $role ? Role::query()->where('name', $role)->first() : null;
    }

    public function can(string $ability, mixed $arguments = []): bool
    {
        return \App\Core\Gate::allows($ability, $arguments);
    }

    public function cannot(string $ability, mixed $arguments = []): bool
    {
        return !$this->can($ability, $arguments);
    }

    public function hasRole(string|array $roles): bool
    {
        $userRole = $this->attributes['role'] ?? \App\Core\Auth::role();
        foreach ((array) $roles as $role) {
            if ($userRole === $role) {
                return true;
            }
        }
        // Spatie-style: check model_has_roles join.
        try {
            foreach ((array) $roles as $role) {
                $row = static::db()->fetch(
                    "SELECT COUNT(*) AS cnt FROM model_has_roles mr
                     JOIN roles r ON r.id = mr.role_id
                     WHERE mr.model_id = ? AND r.name = ? AND mr.model_type = 'App\\\\Models\\\\User'",
                    [(int) $this->getKey(), $role]
                );
                if ((int) ($row['cnt'] ?? 0) > 0) {
                    return true;
                }
            }
        } catch (\Throwable) {
            //
        }
        return false;
    }

    public function hasAnyRole(string|array $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function hasAllRoles(string|array $roles): bool
    {
        foreach ((array) $roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }

    public function hasPermissionTo(string $permission): bool
    {
        return $this->can($permission);
    }

    public function unreadNotifications()
    {
        return new \App\Core\Support\Collection();
    }

    public function notifications()
    {
        return new \App\Core\Support\Collection();
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        $photo = $this->attributes['photo'] ?? null;
        if ($photo) {
            return url('storage/' . ltrim($photo, '/'));
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode((string) ($this->attributes['name'] ?? 'U')) . '&background=2563eb&color=fff';
    }
}
