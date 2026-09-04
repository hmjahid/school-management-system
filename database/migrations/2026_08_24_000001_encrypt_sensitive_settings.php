<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $websiteFields = [
        'bkash_merchant_number', 'bkash_api_key', 'bkash_api_secret',
        'bkash_username', 'bkash_password', 'bkash_app_key', 'bkash_app_secret',
        'twilio_sid', 'twilio_auth_token', 'twilio_from_number',
    ];

    private array $gatewayFields = [
        'api_key', 'api_secret', 'api_username', 'api_password',
    ];

    public function up(): void
    {
        $this->reencrypt('website_settings', $this->websiteFields);
        $this->reencrypt('payment_gateways', $this->gatewayFields);
    }

    public function down(): void
    {
        $this->decrypt('website_settings', $this->websiteFields);
        $this->decrypt('payment_gateways', $this->gatewayFields);
    }

    private function reencrypt(string $table, array $fields): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $fields = array_values(array_filter($fields, fn ($f) => Schema::hasColumn($table, $f)));
        if ($fields === []) {
            return;
        }

        foreach (DB::table($table)->select(array_merge(['id'], $fields))->get() as $row) {
            $update = [];
            foreach ($fields as $f) {
                $value = $row->{$f};
                if ($value === null) {
                    continue;
                }
                if ($value === '') {
                    $update[$f] = null;

                    continue;
                }
                // Already encrypted (Laravel ciphertext is base64 JSON starting with "eyJ").
                if (str_starts_with((string) $value, 'eyJ')) {
                    continue;
                }
                $update[$f] = Crypt::encryptString($value);
            }
            if ($update !== []) {
                DB::table($table)->where('id', $row->id)->update($update);
            }
        }
    }

    private function decrypt(string $table, array $fields): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $fields = array_values(array_filter($fields, fn ($f) => Schema::hasColumn($table, $f)));
        if ($fields === []) {
            return;
        }

        foreach (DB::table($table)->select(array_merge(['id'], $fields))->get() as $row) {
            $update = [];
            foreach ($fields as $f) {
                $value = $row->{$f};
                if ($value === null || $value === '') {
                    continue;
                }
                if (str_starts_with((string) $value, 'eyJ')) {
                    $update[$f] = Crypt::decryptString($value);
                }
            }
            if ($update !== []) {
                DB::table($table)->where('id', $row->id)->update($update);
            }
        }
    }
};
