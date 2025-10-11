<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;

class NotificationTemplate extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'key',
        'subject',
        'content',
        'sms_content',
        'in_app_content',
        'variables',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the rendered content for the given channel.
     *
     * @param  string  $channel
     * @param  array  $data
     * @return string|null
     */
    public function getRenderedContent(string $channel, array $data = []): ?string
    {
        $content = $this->getContentForChannel($channel);
        
        if (empty($content)) {
            return null;
        }

        try {
            return $this->renderContent($content, $data);
        } catch (\Exception $e) {
            \Log::error('Failed to render notification template', [
                'template_id' => $this->id,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Get the rendered subject with the given data.
     *
     * @param  array  $data
     * @return string
     */
    public function getRenderedSubject(array $data = []): string
    {
        return $this->renderContent($this->subject, $data);
    }

    /**
     * Get the content for the given channel.
     *
     * @param  string  $channel
     * @return string|null
     */
    protected function getContentForChannel(string $channel): ?string
    {
        $content = null;

        switch ($channel) {
            case 'email':
                $content = $this->content;
                break;
            case 'sms':
                $content = $this->sms_content;
                break;
            case 'in_app':
            case 'push':
                $content = $this->in_app_content;
                break;
        }

        return $content;
    }

    /**
     * Render the given content with the provided data.
     *
     * @param  string  $content
     * @param  array  $data
     * @return string
     */
    protected function renderContent(string $content, array $data = []): string
    {
        // Add template variables to the data array
        $data = array_merge($this->variables ?? [], $data);
        
        // Render the content using Laravel's Blade engine
        return Blade::render($content, $data);
    }

    /**
     * Get a template by its key.
     *
     * @param  string  $key
     * @return self|null
     */
    public static function getByKey(string $key): ?self
    {
        return static::where('key', $key)->where('is_active', true)->first();
    }

    /**
     * Get the available template variables as an array of strings.
     *
     * @return array
     */
    public function getAvailableVariables(): array
    {
        $defaultVariables = [
            // User variables
            'user.id',
            'user.name',
            'user.email',
            'user.phone',
            
            // System variables
            'app.name',
            'app.url',
            'current_date',
            'current_time',
            
            // Notification variables
            'notification.id',
            'notification.type',
            'notification.created_at',
        ];

        $customVariables = $this->variables ? array_keys($this->variables) : [];
        
        return array_merge($defaultVariables, $customVariables);
    }

    /**
     * Get the available template variables as a formatted string for display.
     *
     * @return string
     */
    public function getAvailableVariablesAsString(): string
    {
        return '{{ ' . implode(' }}, {{ ', $this->getAvailableVariables()) . ' }}';
    }

    /**
     * Check if the template is valid for the given channel.
     *
     * @param  string  $channel
     * @return bool
     */
    public function isValidForChannel(string $channel): bool
    {
        $content = $this->getContentForChannel($channel);
        return !empty($content);
    }

    /**
     * Get all active templates for the given channel.
     *
     * @param  string  $channel
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getTemplatesForChannel(string $channel)
    {
        return static::where('is_active', true)
            ->where(function ($query) use ($channel) {
                $query->whereNotNull(self::getContentColumnForChannel($channel));
            })
            ->get();
    }

    /**
     * Get the content column name for the given channel.
     *
     * @param  string  $channel
     * @return string
     */
    protected static function getContentColumnForChannel(string $channel): string
    {
        return [
            'email' => 'content',
            'sms' => 'sms_content',
            'in_app' => 'in_app_content',
            'push' => 'in_app_content',
        ][$channel] ?? 'content';
    }

    /**
     * Get the available notification channels.
     *
     * @return array
     */
    public static function getAvailableChannels(): array
    {
        return ['email', 'sms', 'in_app', 'push'];
    }

    /**
     * Get the default templates.
     *
     * @return array
     */
    public static function getDefaultTemplates(): array
    {
        return [
            [
                'name' => 'Refund Request Received',
                'key' => 'refund_request_received',
                'subject' => 'Refund Request Received',
                'content' => view('emails.refund-request-received', [
                    'user' => '{{ user }}',
                    'refund' => '{{ refund }}',
                    'amount' => '{{ amount }}',
                    'date' => '{{ date }}',
                ])->render(),
                'sms_content' => 'Your refund request for {{ amount }} has been received and is being processed.',
                'in_app_content' => 'Your refund request for {{ amount }} has been received and is being processed.',
                'variables' => [
                    'refund.id' => 'The ID of the refund',
                    'amount' => 'The refund amount',
                    'currency' => 'The currency of the refund',
                    'reason' => 'The reason for the refund',
                    'status' => 'The current status of the refund',
                    'date' => 'The date the refund was requested',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Refund Status Updated',
                'key' => 'refund_status_updated',
                'subject' => 'Refund Status Updated',
                'content' => view('emails.refund-status-updated', [
                    'user' => '{{ user }}',
                    'refund' => '{{ refund }}',
                    'status' => '{{ status }}',
                    'amount' => '{{ amount }}',
                    'date' => '{{ date }}',
                ])->render(),
                'sms_content' => 'Your refund status has been updated to: {{ status }}',
                'in_app_content' => 'Your refund status has been updated to: {{ status }}',
                'variables' => [
                    'refund.id' => 'The ID of the refund',
                    'old_status' => 'The previous status of the refund',
                    'new_status' => 'The new status of the refund',
                    'amount' => 'The refund amount',
                    'currency' => 'The currency of the refund',
                    'date' => 'The date the status was updated',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Refund Processed',
                'key' => 'refund_processed',
                'subject' => 'Refund Processed Successfully',
                'content' => view('emails.refund-processed', [
                    'user' => '{{ user }}',
                    'refund' => '{{ refund }}',
                    'amount' => '{{ amount }}',
                    'date' => '{{ date }}',
                ])->render(),
                'sms_content' => 'Your refund of {{ amount }} has been processed successfully.',
                'in_app_content' => 'Your refund of {{ amount }} has been processed successfully.',
                'variables' => [
                    'refund.id' => 'The ID of the refund',
                    'amount' => 'The refund amount',
                    'currency' => 'The currency of the refund',
                    'transaction_id' => 'The transaction ID for the refund',
                    'date' => 'The date the refund was processed',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Refund Failed',
                'key' => 'refund_failed',
                'subject' => 'Refund Processing Failed',
                'content' => view('emails.refund-failed', [
                    'user' => '{{ user }}',
                    'refund' => '{{ refund }}',
                    'amount' => '{{ amount }}',
                    'error' => '{{ error }}',
                    'date' => '{{ date }}',
                ])->render(),
                'sms_content' => 'We encountered an issue processing your refund. Please contact support.',
                'in_app_content' => 'We encountered an issue processing your refund: {{ error }}',
                'variables' => [
                    'refund.id' => 'The ID of the refund',
                    'amount' => 'The refund amount',
                    'currency' => 'The currency of the refund',
                    'error' => 'The error message',
                    'date' => 'The date the error occurred',
                ],
                'is_active' => true,
            ],
        ];
    }

    /**
     * Seed the notification templates.
     *
     * @return void
     */
    public static function seedTemplates(): void
    {
        foreach (self::getDefaultTemplates() as $template) {
            self::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
