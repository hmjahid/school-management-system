<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\VariantRestoreReconciler;
use Tests\Fakes\FakeDatabase;
use Tests\TestCase;

class VariantRestoreReconcilerTest extends TestCase
{
    private FakeDatabase $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new FakeDatabase();
    }

    public function test_cross_variant_restore_reconciles_gateways_settings_and_bangla_content(): void
    {
        $this->db->seed('payment_gateways', [
            ['id' => 1, 'code' => 'bkash', 'name' => 'bKash', 'is_active' => 1, 'currency' => 'BDT'],
            ['id' => 2, 'code' => 'stripe', 'name' => 'Stripe', 'is_active' => 0, 'currency' => 'BDT'],
        ]);
        $this->db->seed('website_settings', [
            ['id' => 1, 'currency' => 'BDT', 'default_payment_method' => 'bkash', 'default_locale' => 'bn', 'school_name_bn' => 'উদাহরণ স্কুল'],
        ]);
        $this->db->seed('admission_settings', [
            ['id' => 1, 'closed_message_bn' => 'বন্ধ আছে', 'payment_number' => '01712345678'],
        ]);

        $notes = (new VariantRestoreReconciler($this->db))->reconcile('bd', [
            'website_settings' => ['currency', 'default_payment_method', 'default_locale', 'school_name_bn'],
            'admission_settings' => ['closed_message_bn', 'payment_number'],
        ], 'int');

        $this->assertNotEmpty($notes);

        $gateways = array_column($this->db->tables['payment_gateways'], null, 'code');
        $this->assertSame(0, $gateways['bkash']['is_active']);
        $this->assertSame(1, $gateways['stripe']['is_active']);
        $this->assertSame('USD', $gateways['stripe']['currency']);

        $settings = $this->db->tables['website_settings'][0];
        $this->assertSame('USD', $settings['currency']);
        $this->assertSame('stripe', $settings['default_payment_method']);
        $this->assertNull($settings['school_name_bn']);

        $admission = $this->db->tables['admission_settings'][0];
        $this->assertNull($admission['closed_message_bn']);
        $this->assertNull($admission['payment_number']);
    }

    public function test_same_variant_or_unknown_source_does_not_reconcile(): void
    {
        $this->db->seed('payment_gateways', [
            ['id' => 1, 'code' => 'bkash', 'is_active' => 1, 'currency' => 'BDT'],
        ]);

        $reconciler = new VariantRestoreReconciler($this->db);
        $this->assertSame([], $reconciler->reconcile('bd', [], 'bd'));
        $this->assertSame([], $reconciler->reconcile(null, [], 'int'));
    }
}