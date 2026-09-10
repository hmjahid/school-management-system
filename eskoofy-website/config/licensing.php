<?php
declare(strict_types=1);

return [
    // International-standard licensing defaults (USD, UTC).
    'currency' => $_ENV['LICENSE_CURRENCY'] ?? 'USD',
    'key_prefix' => $_ENV['LICENSE_KEY_PREFIX'] ?? 'ESK',
    'key_chunks' => (int) ($_ENV['LICENSE_KEY_CHUNKS'] ?? 4),
    'key_length' => (int) ($_ENV['LICENSE_KEY_LENGTH'] ?? 4),
    'default_max_activations' => (int) ($_ENV['LICENSE_MAX_ACTIVATIONS'] ?? 3),
    'grace_days' => (int) ($_ENV['LICENSE_GRACE_DAYS'] ?? 7),
    'activity_log' => ($_ENV['LICENSE_ACTIVITY_LOG'] ?? 'true') === 'true',
];