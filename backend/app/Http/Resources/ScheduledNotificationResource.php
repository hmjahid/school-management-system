<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduledNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'channels' => $this->channels,
            'recipients' => $this->recipients,
            'data' => $this->data,
            'schedule' => $this->schedule,
            'scheduled_at' => $this->scheduled_at->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'status' => $this->status,
            'error_message' => $this->when(
                $this->status === 'failed' || $this->status === 'cancelled',
                $this->error_message
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'links' => [
                'self' => route('api.notifications.scheduled.show', $this->id),
                'cancel' => route('api.notifications.scheduled.cancel', $this->id),
            ],
        ];
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => [
                'available_channels' => config('notifications.channels', []),
                'available_types' => array_keys(config('notifications.types', [])),
            ],
        ];
    }
}
