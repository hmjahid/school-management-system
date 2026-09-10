<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\LicenseManager;
use Tests\FakeDatabase;
use Tests\TestCase;

class LicenseManagerTest extends TestCase
{
    private FakeDatabase $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new FakeDatabase();
    }

    private function manager(): LicenseManager
    {
        return new LicenseManager($this->db);
    }

    private function seedPlan(array $overrides = []): int
    {
        return $this->db->insert('plans', array_merge([
            'name'            => 'App Yearly',
            'slug'            => 'app-yearly',
            'product'         => 'app',
            'description'     => 'Yearly license',
            'price'           => 90.0,
            'currency'        => 'USD',
            'period'          => 'yearly',
            'max_activations' => 3,
            'active'          => 1,
            'created_at'      => date('Y-m-d H:i:s'),
        ], $overrides));
    }

    public function test_generates_valid_key_format(): void
    {
        $key = $this->manager()->generateKey();

        $this->assertMatchesRegularExpression('/^ESK-[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $key);
    }

    public function test_issue_creates_license_with_yearly_expiry(): void
    {
        $planId = $this->seedPlan();
        $manager = $this->manager();

        $result = $manager->issue(7, $planId, 'app');

        $this->assertSame('active', $result['license']['status']);
        $license = $result['license'];
        $this->assertSame('app', $license['product']);
        $this->assertSame(7, (int) $license['customer_id']);
        $this->assertSame(3, (int) $license['max_activations']);
        $this->assertNotNull($license['expires_at']);
        $this->assertSame(
            date('Y-m-d', strtotime('+1 year')),
            date('Y-m-d', strtotime($license['expires_at']))
        );
    }

    public function test_issue_throws_for_missing_plan(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->manager()->issue(1, 999, 'app');
    }

    public function test_issue_supports_injected_key_and_payment_link(): void
    {
        $planId = $this->seedPlan(['period' => 'one-time']);
        $paymentId = $this->db->insert('payments', ['id' => 400, 'status' => 'pending']);
        $this->db->tables['payments'] = [['id' => 400, 'status' => 'pending']];

        $result = $this->manager()->issue(2, $planId, 'theme', [
            'license_key' => 'ESK-AAAA-AAAA-AAAA-AAAA',
            'payment'     => $paymentId,
        ]);

        $this->assertSame('ESK-AAAA-AAAA-AAAA-AAAA', $result['license']['license_key']);
        $this->assertSame($paymentId, $result['payment_id']);
        $this->assertNull($result['license']['expires_at'], 'one-time license should never expire');
    }

    public function test_expiry_for_periods(): void
    {
        $manager = $this->manager();

        $this->assertNull($manager->expiryFor(['period' => 'one-time'], '2026-01-01 00:00:00'));
        $this->assertNull($manager->expiryFor(['period' => 'lifetime'], '2026-01-01 00:00:00'));
        $this->assertSame('2026-02-01', date('Y-m-d', strtotime((string) $manager->expiryFor(['period' => 'monthly'], '2026-01-01 00:00:00'))));
        $this->assertSame('2027-01-01', date('Y-m-d', strtotime((string) $manager->expiryFor(['period' => 'yearly'], '2026-01-01 00:00:00'))));
    }

    public function test_is_expired_handles_lifetime_and_past_dates(): void
    {
        $manager = $this->manager();

        $this->assertFalse($manager->isExpired(['expires_at' => null]));
        $this->assertFalse($manager->isExpired(['expires_at' => date('Y-m-d H:i:s', strtotime('+1 day'))]));
        $this->assertTrue($manager->isExpired(['expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]));
    }

    public function test_activate_success_and_reactivation(): void
    {
        $planId = $this->seedPlan();
        $manager = $this->manager();
        $license = $manager->issue(3, $planId, 'app')['license'];

        $result = $manager->activate($license['license_key'], 'School.Example.com', 'DEVICE-1');
        $this->assertSame('ok', $result['status']);

        $activationId = $result['data']['activation_id'];
        $activation = $this->db->fetch('SELECT * FROM license_activations WHERE id = ?', [$activationId]);
        $this->assertSame('school.example.com', $activation['domain'], 'domain should be normalized to lowercase');

        // Same domain + machine → idempotent, no new row.
        $again = $manager->activate($license['license_key'], 'school.example.com', 'DEVICE-1');
        $this->assertSame($activationId, $again['data']['activation_id']);

        $count = $this->db->count('license_activations', 'license_id = ?', [(int) $license['id']]);
        $this->assertSame(1, $count);
    }

    public function test_activate_restores_deactivated_row(): void
    {
        $planId = $this->seedPlan();
        $manager = $this->manager();
        $license = $manager->issue(4, $planId, 'app')['license'];

        $result = $manager->activate($license['license_key'], 'school.example.com', 'M1');
        $manager->deactivate($license['license_key'], 'school.example.com', 'M1');

        $restored = $manager->activate($license['license_key'], 'school.example.com', 'M1');
        $this->assertSame('ok', $restored['status']);
        $this->assertSame($result['data']['activation_id'], $restored['data']['activation_id']);
        $this->assertSame(1, $this->db->count('license_activations', 'license_id = ?', [(int) $license['id']]));
    }

    public function test_activate_rejects_unknown_key(): void
    {
        $result = $this->manager()->activate('ESK-0000-0000-0000-0000', 'x.com');

        $this->assertSame('error', $result['status']);
        $this->assertSame('invalid_license', $result['code']);
    }

    public function test_activate_rejects_suspended_and_expired(): void
    {
        $planId = $this->seedPlan(['period' => 'one-time']);
        $manager = $this->manager();
        $license = $manager->issue(5, $planId, 'app')['license'];

        $this->db->update('licenses', ['status' => 'suspended'], 'id = ?', [(int) $license['id']]);
        $result = $manager->activate($license['license_key'], 'x.com');
        $this->assertSame('license_suspended', $result['code']);

        $this->db->update('licenses', ['status' => 'active', 'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))], 'id = ?', [(int) $license['id']]);
        $expired = $manager->activate($license['license_key'], 'x.com');
        $this->assertSame('license_expired', $expired['code']);
    }

    public function test_activate_enforces_max_activations(): void
    {
        $planId = $this->seedPlan(['max_activations' => 2]);
        $manager = $this->manager();
        $license = $manager->issue(6, $planId, 'app')['license'];

        $first = $manager->activate($license['license_key'], 'a.com', 'DEV-A');
        $this->assertSame('ok', $first['status']);

        $second = $manager->activate($license['license_key'], 'b.com', 'DEV-B');
        $this->assertSame('ok', $second['status']);

        $third = $manager->activate($license['license_key'], 'c.com', 'DEV-C');
        $this->assertSame('error', $third['status']);
        $this->assertSame('max_activations_reached', $third['code']);
    }

    public function test_validate_ok_for_active_domain(): void
    {
        $planId = $this->seedPlan();
        $manager = $this->manager();
        $license = $manager->issue(8, $planId, 'app')['license'];
        $manager->activate($license['license_key'], 'school.example.com');

        $result = $manager->validate($license['license_key'], 'school.example.com');

        $this->assertSame('ok', $result['status']);
        $this->assertTrue($result['data']['activated_on_this']);
        $this->assertSame('app', $result['data']['product']);

        $notThere = $manager->validate($license['license_key'], 'other.example.com');
        $this->assertSame('ok', $notThere['status'], 'license valid even if not activated on this domain');
        $this->assertFalse($notThere['data']['activated_on_this']);
    }

    public function test_deactivate_removes_active_activation(): void
    {
        $planId = $this->seedPlan();
        $manager = $this->manager();
        $license = $manager->issue(9, $planId, 'app')['license'];
        $manager->activate($license['license_key'], 'school.example.com', 'DEV-9');

        $result = $manager->deactivate($license['license_key'], 'SCHOOL.EXAMPLE.COM', 'DEV-9');

        $this->assertSame('ok', $result['status']);
        $this->assertSame(0, $manager->activeActivationCount((int) $license['id']));
    }

    public function test_renew_fails_for_missing_license(): void
    {
        $result = $this->manager()->renew(999);

        $this->assertSame('error', $result['status']);
        $this->assertSame('license_not_found', $result['code']);
    }
}