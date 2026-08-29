<?php

namespace Tests\Unit\Models;

use App\Models\NotificationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function makeTemplate(array $attributes = []): NotificationTemplate
    {
        return NotificationTemplate::create(array_merge([
            'name' => 'Test Template',
            'key' => 'test_' . uniqid(),
            'subject' => 'Hello {{ $name }}',
            'content' => 'Dear {{ $name }}, welcome.',
            'sms_content' => 'Hi {{ $name }}',
            'in_app_content' => 'Welcome {{ $name }}',
            'variables' => ['name' => 'The recipient name'],
            'is_active' => true,
        ], $attributes));
    }

    /** @test */
    public function it_persists_required_columns_and_casts(): void
    {
        $template = $this->makeTemplate();

        $this->assertDatabaseHas('notification_templates', [
            'id' => $template->id,
            'key' => $template->key,
        ]);
        $this->assertIsArray($template->variables);
        $this->assertIsBool($template->is_active);
    }

    /** @test */
    public function it_renders_content_for_a_channel_with_data(): void
    {
        $template = $this->makeTemplate();

        $rendered = $template->getRenderedContent('email', ['name' => 'Asha']);

        $this->assertEquals('Dear Asha, welcome.', $rendered);
    }

    /** @test */
    public function it_renders_subject_with_data(): void
    {
        $template = $this->makeTemplate(['subject' => 'Hi {{ $name }}']);

        $this->assertEquals('Hi Asha', $template->getRenderedSubject(['name' => 'Asha']));
    }

    /** @test */
    public function get_by_key_returns_active_template(): void
    {
        $template = $this->makeTemplate(['key' => 'lookup_' . uniqid()]);

        $found = NotificationTemplate::getByKey($template->key);
        $this->assertNotNull($found);
        $this->assertEquals($template->id, $found->id);
    }

    /** @test */
    public function it_reports_validity_per_channel(): void
    {
        $template = $this->makeTemplate();

        $this->assertTrue($template->isValidForChannel('email'));
        $this->assertTrue($template->isValidForChannel('sms'));
        $this->assertFalse($template->isValidForChannel('unknown'));
    }

    /** @test */
    public function it_lists_available_variables_including_custom(): void
    {
        $template = $this->makeTemplate();

        $vars = $template->getAvailableVariables();
        $this->assertContains('user.name', $vars);
        $this->assertContains('name', $vars);
    }
}
