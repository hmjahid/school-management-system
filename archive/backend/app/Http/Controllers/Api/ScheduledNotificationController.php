<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduledNotificationResource;
use App\Models\ScheduledNotification;
use App\Services\Notification\ScheduledNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ScheduledNotificationController extends Controller
{
    /**
     * The scheduled notification service.
     *
     * @var \App\Services\Notification\ScheduledNotificationService
     */
    protected $scheduledNotificationService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\Notification\ScheduledNotificationService  $scheduledNotificationService
     * @return void
     */
    public function __construct(ScheduledNotificationService $scheduledNotificationService)
    {
        $this->middleware('auth:api');
        $this->scheduledNotificationService = $scheduledNotificationService;
    }

    /**
     * Display a listing of the scheduled notifications.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ScheduledNotification::query();
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('scheduled_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->where('scheduled_at', '<=', $request->end_date);
        }
        
        // For non-admin users, only show their own scheduled notifications
        if (!auth()->user()->hasRole('admin')) {
            $query->where('created_by', auth()->id());
        }
        
        // Pagination
        $perPage = $request->per_page ?? 15;
        $notifications = $query->orderBy('scheduled_at', 'desc')->paginate($perPage);
        
        return ScheduledNotificationResource::collection($notifications);
    }

    /**
     * Store a newly created scheduled notification in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $this->validateScheduledNotification($request);
        
        $notification = $this->scheduledNotificationService->schedule(
            $validated['name'],
            $validated['type'],
            $validated['channels'],
            $validated['recipients'],
            $validated['data'] ?? [],
            $validated['schedule'],
            auth()->id()
        );
        
        return new ScheduledNotificationResource($notification);
    }

    /**
     * Display the specified scheduled notification.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $notification = $this->getUserNotification($id);
        return new ScheduledNotificationResource($notification);
    }

    /**
     * Update the specified scheduled notification in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $notification = $this->getUserNotification($id);
        
        // Only allow updating pending notifications
        if ($notification->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending notifications can be updated.'
            ], 422);
        }
        
        $validated = $this->validateScheduledNotification($request, $notification->id);
        
        $notification->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'channels' => $validated['channels'],
            'recipients' => $validated['recipients'],
            'data' => $validated['data'] ?? [],
            'schedule' => $validated['schedule'],
            'scheduled_at' => $this->scheduledNotificationService->calculateScheduledAt($validated['schedule']),
        ]);
        
        return new ScheduledNotificationResource($notification);
    }

    /**
     * Cancel the specified scheduled notification.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $notification = $this->getUserNotification($id);
        
        if ($notification->cancel()) {
            return response()->json([
                'message' => 'Scheduled notification has been cancelled.'
            ]);
        }
        
        return response()->json([
            'message' => 'Unable to cancel the scheduled notification.'
        ], 422);
    }

    /**
     * Get scheduled notification statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function stats()
    {
        if (!auth()->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }
        
        return response()->json($this->scheduledNotificationService->getStats());
    }

    /**
     * Get the scheduled notification for the authenticated user.
     *
     * @param  int  $id
     * @return \App\Models\ScheduledNotification
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function getUserNotification($id)
    {
        $query = ScheduledNotification::where('id', $id);
        
        if (!auth()->user()->hasRole('admin')) {
            $query->where('created_by', auth()->id());
        }
        
        return $query->firstOrFail();
    }

    /**
     * Validate the scheduled notification request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|null  $ignoreId
     * @return array
     */
    protected function validateScheduledNotification(Request $request, $ignoreId = null)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'channels' => 'required|array',
            'channels.*' => 'in:mail,sms,push,database',
            'recipients' => 'required|array',
            'recipients.*.id' => 'required|integer',
            'recipients.*.type' => 'required|string|in:user,role,class,all',
            'data' => 'nullable|array',
            'schedule' => 'required|array',
            'schedule.type' => 'required|string|in:once,daily,weekly,monthly,custom',
            'schedule.datetime' => 'required_if:schedule.type,once|date_format:Y-m-d H:i:s',
            'schedule.timezone' => 'required_with:schedule.datetime|timezone',
            'schedule.interval' => 'required_if:schedule.type,custom|integer|min:1',
            'schedule.unit' => 'required_if:schedule.type,custom|in:minute,hour,day,week,month',
        ];
        
        return $request->validate($rules);
    }
}
