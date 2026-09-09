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
}
