<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Validator;
use Tests\TestCase;

class ValidatorTest extends TestCase
{
    public function test_passes_with_valid_data(): void
    {
        $v = new Validator(
            ['email' => 'user@example.com', 'name' => 'John'],
            ['email' => 'required|email', 'name' => 'required|min:2']
        );

        $this->assertFalse($v->fails());
        $this->assertEmpty($v->errors());
    }

    public function test_required_fails_on_missing_field(): void
    {
        $v = new Validator(['name' => ''], ['name' => 'required']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('name', $v->errors());
        $this->assertStringContainsString('required', $v->errors()['name']);
    }

    public function test_required_fails_on_null(): void
    {
        $v = new Validator([], ['name' => 'required']);

        $this->assertTrue($v->fails());
    }

    public function test_required_passes_on_zero(): void
    {
        $v = new Validator(['count' => 0], ['count' => 'required']);

        $this->assertFalse($v->fails());
    }

    public function test_email_validation(): void
    {
        $valid = new Validator(['email' => 'test@example.com'], ['email' => 'email']);
        $this->assertFalse($valid->fails());

        $invalid = new Validator(['email' => 'not-an-email'], ['email' => 'email']);
        $this->assertTrue($invalid->fails());
        $this->assertStringContainsString('email', $invalid->errors()['email']);
    }

    public function test_email_passes_when_empty_without_required(): void
    {
        $v = new Validator(['email' => ''], ['email' => 'email']);
        $this->assertFalse($v->fails());
    }

    public function test_min_validation(): void
    {
        $short = new Validator(['name' => 'Jo'], ['name' => 'min:3']);
        $this->assertTrue($short->fails());
        $this->assertStringContainsString('3', $short->errors()['name']);

        $ok = new Validator(['name' => 'John'], ['name' => 'min:3']);
        $this->assertFalse($ok->fails());
    }

    public function test_max_validation(): void
    {
        $long = new Validator(['name' => str_repeat('a', 256)], ['name' => 'max:255']);
        $this->assertTrue($long->fails());
        $this->assertStringContainsString('255', $long->errors()['name']);

        $ok = new Validator(['name' => 'John'], ['name' => 'max:255']);
        $this->assertFalse($ok->fails());
    }

    public function test_numeric_validation(): void
    {
        $valid = new Validator(['age' => '25'], ['age' => 'numeric']);
        $this->assertFalse($valid->fails());

        $validFloat = new Validator(['price' => '19.99'], ['price' => 'numeric']);
        $this->assertFalse($validFloat->fails());

        $invalid = new Validator(['age' => 'abc'], ['age' => 'numeric']);
        $this->assertTrue($invalid->fails());
        $this->assertStringContainsString('number', $invalid->errors()['age']);
    }

    public function test_in_validation(): void
    {
        $valid = new Validator(['status' => 'active'], ['status' => 'in:active,inactive,archived']);
        $this->assertFalse($valid->fails());

        $invalid = new Validator(['status' => 'deleted'], ['status' => 'in:active,inactive,archived']);
        $this->assertTrue($invalid->fails());
        $this->assertStringContainsString('active, inactive, archived', $invalid->errors()['status']);
    }

    public function test_date_validation(): void
    {
        $valid = new Validator(['dob' => '2000-01-15'], ['dob' => 'date']);
        $this->assertFalse($valid->fails());

        $invalid = new Validator(['dob' => 'not-a-date'], ['dob' => 'date']);
        $this->assertTrue($invalid->fails());
    }

    public function test_regex_validation(): void
    {
        $valid = new Validator(['phone' => '01712345678'], ['phone' => 'regex:/^01[3-9]\d{8}$/']);
        $this->assertFalse($valid->fails());

        $invalid = new Validator(['phone' => '123'], ['phone' => 'regex:/^01[3-9]\d{8}$/']);
        $this->assertTrue($invalid->fails());
    }

    public function test_confirmed_validation(): void
    {
        $data = ['password' => 'secret', 'password_confirmation' => 'secret'];
        $v = new Validator($data, ['password' => 'confirmed']);
        $this->assertFalse($v->fails());

        $mismatch = ['password' => 'secret', 'password_confirmation' => 'wrong'];
        $v2 = new Validator($mismatch, ['password' => 'confirmed']);
        $this->assertTrue($v2->fails());
        $this->assertStringContainsString('confirmation', $v2->errors()['password']);
    }

    public function test_pipe_delimited_rules(): void
    {
        $v = new Validator(
            ['email' => ''],
            ['email' => 'required|email']
        );
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('email', $v->errors());
    }

    public function test_validated_returns_only_rule_fields(): void
    {
        $v = new Validator(
            ['name' => 'John', 'extra' => 'ignored'],
            ['name' => 'required']
        );
        $validated = $v->validated();
        $this->assertArrayHasKey('name', $validated);
        $this->assertArrayNotHasKey('extra', $validated);
    }

    public function test_empty_data_fails_required(): void
    {
        $v = new Validator([], ['name' => 'required', 'email' => 'required']);
        $this->assertTrue($v->fails());
        $this->assertCount(2, $v->errors());
    }

    public function test_attribute_label_is_humanized(): void
    {
        $v = new Validator([], ['first_name' => 'required']);
        $this->assertStringContainsString('First name', $v->errors()['first_name']);
    }
}
