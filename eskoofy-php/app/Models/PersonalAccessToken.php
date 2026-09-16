<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

/**
 * Sanctum-style API access token (personal_access_tokens table).
 */
class PersonalAccessToken extends Model
{
    protected static string $table = 'personal_access_tokens';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'tokenable_type', 'tokenable_id', 'name', 'token', 'abilities',
        'last_used_at', 'expires_at',
    ];

    protected array $hidden = ['token'];

    public function tokenable()
    {
        return $this->morphTo();
    }

    /**
     * Create a new API access token for a user.
     *
     * @return array{plain_text_token: string, token: static}
     */
    public static function createToken(int $userId, string $name = 'auth_token', array $abilities = ['*'], ?string $expiresAt = null): array
    {
        $plainText = bin2hex(random_bytes(32));
        $hashed    = hash('sha256', $plainText);

        $token = static::create([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id'   => $userId,
            'name'           => $name,
            'token'          => $hashed,
            'abilities'      => json_encode($abilities),
            'last_used_at'   => null,
            'expires_at'     => $expiresAt,
        ]);

        return [
            'plain_text_token' => $plainText,
            'token'            => $token,
        ];
    }

    public static function findByToken(string $plainText): ?static
    {
        if ($plainText === '') {
            return null;
        }
        return static::query()->where('token', hash('sha256', $plainText))->first();
    }

    public function isValid(): bool
    {
        if ($this->expires_at !== null && strtotime((string) $this->expires_at) < time()) {
            return false;
        }
        return true;
    }

    public function touchLastUsed(): void
    {
        static::db()->update(static::$table, ['last_used_at' => date('Y-m-d H:i:s')], static::$primaryKey . ' = ?', [(int) $this->getKey()]);
    }

    public function tokenCan(string $ability): bool
    {
        $abilities = json_decode((string) ($this->attributes['abilities'] ?? '["*"]'), true);
        if (!is_array($abilities)) {
            $abilities = ['*'];
        }
        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }
}