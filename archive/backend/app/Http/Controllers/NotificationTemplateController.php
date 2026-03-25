<?php

namespace App\Http\Controllers;

use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NotificationTemplateController extends Controller
{
    /**
     * Display a listing of the notification templates.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $templates = NotificationTemplate::query();
        
        // Filter by type if provided
        if ($type = $request->query('type')) {
            $templates->where('type', $type);
        }
        
        // Filter by channel if provided
        if ($channel = $request->query('channel')) {
            $templates->where('channel', $channel);
        }
        
        // Paginate results
        $perPage = $request->query('per_page', 15);
        $templates = $templates->paginate($perPage);
        
        return response()->json([
            'data' => $templates->items(),
            'meta' => [
                'total' => $templates->total(),
                'per_page' => $templates->perPage(),
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created notification template in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => [
                'required',
                'string',
                'max:100',
                Rule::in(array_keys(config('notifications.types', []))),
            ],
            'channel' => [
                'required',
                'string',
                'in:database,mail,sms,push',
            ],
            'subject' => [
                'required_if:channel,mail,push',
                'nullable',
                'string',
                'max:255',
            ],
            'content' => [
                'required',
                'string',
            ],
            'is_active' => [
                'boolean',
            ],
        ]);

        // Check if a template already exists for this type and channel
        $template = NotificationTemplate::where('type', $validated['type'])
            ->where('channel', $validated['channel'])
            ->first();
            
        if ($template) {
            return response()->json([
                'message' => 'A template already exists for this notification type and channel',
                'data' => $template,
            ], 409);
        }

        $template = NotificationTemplate::create($validated);

        return response()->json([
            'message' => 'Notification template created successfully',
            'data' => $template,
        ], 201);
    }

    /**
     * Display the specified notification template.
     *
     * @param  \App\Models\NotificationTemplate  $template
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(NotificationTemplate $template)
    {
        return response()->json([
            'data' => $template,
        ]);
    }

    /**
     * Update the specified notification template in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\NotificationTemplate  $template
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, NotificationTemplate $template)
    {
        $validated = $request->validate([
            'subject' => [
                'required_if:channel,mail,push',
                'nullable',
                'string',
                'max:255',
            ],
            'content' => [
                'required',
                'string',
            ],
            'is_active' => [
                'boolean',
            ],
        ]);

        $template->update($validated);

        return response()->json([
            'message' => 'Notification template updated successfully',
            'data' => $template,
        ]);
    }

    /**
     * Remove the specified notification template from storage.
     *
     * @param  \App\Models\NotificationTemplate  $template
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(NotificationTemplate $template)
    {
        $template->delete();

        return response()->json([
            'message' => 'Notification template deleted successfully',
        ]);
    }
    
    /**
     * Get the available notification types.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function types()
    {
        $types = array_map(function ($channels, $type) {
            return [
                'type' => $type,
                'default_channels' => $channels,
                'description' => $this->getNotificationTypeDescription($type),
            ];
        }, config('notifications.types', []), array_keys(config('notifications.types', [])));
        
        return response()->json([
            'data' => array_values($types),
        ]);
    }
    
    /**
     * Get the available variables for a notification type.
     *
     * @param  string  $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function variables(string $type)
    {
        $variables = $this->getNotificationVariables($type);
        
        return response()->json([
            'data' => $variables,
        ]);
    }
    
    /**
     * Preview a notification template with sample data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview(Request $request)
    {
        $request->validate([
            'type' => [
                'required',
                'string',
                Rule::in(array_keys(config('notifications.types', []))),
            ],
            'channel' => [
                'required',
                'string',
                'in:database,mail,sms,push',
            ],
            'content' => [
                'required',
                'string',
            ],
            'subject' => [
                'required_if:channel,mail,push',
                'nullable',
                'string',
                'max:255',
            ],
        ]);
        
        $type = $request->input('type');
        $channel = $request->input('channel');
        $content = $request->input('content');
        $subject = $request->input('subject');
        
        // Generate sample data for the preview
        $data = $this->generateSampleData($type);
        
        // Render the template with sample data
        $renderedContent = $this->renderTemplate($content, $data);
        
        $response = [
            'type' => $type,
            'channel' => $channel,
            'content' => $content,
            'rendered_content' => $renderedContent,
            'sample_data' => $data,
        ];
        
        if (in_array($channel, ['mail', 'push'])) {
            $renderedSubject = $this->renderTemplate($subject, $data);
            $response['subject'] = $subject;
            $response['rendered_subject'] = $renderedSubject;
        }
        
        return response()->json([
            'data' => $response,
        ]);
    }
    
    /**
     * Get the description for a notification type.
     *
     * @param  string  $type
     * @return string
     */
    protected function getNotificationTypeDescription(string $type): string
    {
        $descriptions = [
            // System Notifications
            'system.alert' => 'Important system alerts and notifications',
            'system.maintenance' => 'Scheduled maintenance and downtime notifications',
            'system.update' => 'System updates and new features',
            
            // User Account Notifications
            'user.registered' => 'New user registration',
            'user.verified' => 'Email verification successful',
            'user.password_reset' => 'Password reset request',
            'user.password_updated' => 'Password changed successfully',
            'user.profile_updated' => 'Profile information updated',
            
            // Add more descriptions as needed
        ];
        
        return $descriptions[$type] ?? 'No description available';
    }
    
    /**
     * Get the available variables for a notification type.
     *
     * @param  string  $type
     * @return array
     */
    protected function getNotificationVariables(string $type): array
    {
        $variables = [
            'app_name' => 'The name of the application',
            'app_url' => 'The base URL of the application',
            'user_name' => 'The full name of the user',
            'user_email' => 'The email address of the user',
            'user_id' => 'The ID of the user',
            'timestamp' => 'The current date and time',
            'date' => 'The current date',
            'time' => 'The current time',
        ];
        
        // Add type-specific variables
        $typeVariables = [
            'user.registered' => [
                'verification_url' => 'The URL for email verification',
            ],
            'user.password_reset' => [
                'reset_url' => 'The URL to reset the password',
                'expires' => 'The expiration time for the reset link',
            ],
            'course.enrolled' => [
                'course_name' => 'The name of the course',
                'course_url' => 'The URL to access the course',
                'instructor_name' => 'The name of the course instructor',
            ],
            // Add more type-specific variables as needed
        ];
        
        if (isset($typeVariables[$type])) {
            $variables = array_merge($variables, $typeVariables[$type]);
        }
        
        return $variables;
    }
    
    /**
     * Generate sample data for a notification type.
     *
     * @param  string  $type
     * @return array
     */
    protected function generateSampleData(string $type): array
    {
        $data = [
            'app_name' => config('app.name', 'School Management System'),
            'app_url' => config('app.url', 'https://schoolms.test'),
            'user_name' => 'John Doe',
            'user_email' => 'john.doe@example.com',
            'user_id' => 123,
            'timestamp' => now()->toDateTimeString(),
            'date' => now()->toDateString(),
            'time' => now()->toTimeString(),
        ];
        
        // Add type-specific sample data
        $typeData = [
            'user.registered' => [
                'verification_url' => url('/verify-email/abc123'),
            ],
            'user.password_reset' => [
                'reset_url' => url('/reset-password/abc123'),
                'expires' => now()->addHours(24)->toDateTimeString(),
            ],
            'course.enrolled' => [
                'course_name' => 'Introduction to Web Development',
                'course_url' => url('/courses/intro-to-web-dev'),
                'instructor_name' => 'Jane Smith',
            ],
            // Add more type-specific sample data as needed
        ];
        
        if (isset($typeData[$type])) {
            $data = array_merge($data, $typeData[$type]);
        }
        
        return $data;
    }
    
    /**
     * Render a template with the given data.
     *
     * @param  string  $template
     * @param  array  $data
     * @return string
     */
    protected function renderTemplate(string $template, array $data): string
    {
        $placeholders = [];
        $replacements = [];
        
        foreach ($data as $key => $value) {
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $placeholders[] = '{{' . $key . '}}';
                $replacements[] = (string) $value;
            }
        }
        
        return str_replace($placeholders, $replacements, $template);
    }
}
