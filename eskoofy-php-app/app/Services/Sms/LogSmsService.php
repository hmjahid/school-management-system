<?php
declare(strict_types=1);

namespace App\Services\Sms;

class LogSmsService extends AbstractSmsService
{
    protected function defaultConfig(): array
    {
        return [
            'driver'     => 'log',
            'log_file'   => $_ENV['SMS_LOG_FILE'] ?? null,
            'country_code' => $_ENV['SMS_COUNTRY_CODE'] ?? '1',
        ];
    }

    public function send(string $to, string $message, array $options = []): array
    {
        $this->writeLog([
            'to'       => $to,
            'message'  => $message,
            'options'  => $options,
            'provider' => 'log',
        ]);

        return [
            'success'    => true,
            'message_id' => 'log-' . date('YmdHis') . '-' . random_int(1000, 9999),
            'status'     => 'logged',
            'provider'   => 'log',
        ];
    }

    public function getBalance(): array
    {
        return ['amount' => 0.0, 'currency' => 'credit'];
    }

    public function getStatus(string $messageId): array
    {
        return [
            'status'       => 'logged',
            'message_id'   => $messageId,
            'provider'     => 'log',
            'preview_only' => true,
        ];
    }
}