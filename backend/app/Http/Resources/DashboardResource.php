<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
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
            'totals' => $this->resource['totals'] ?? [],
            'monthly_data' => $this->resource['monthly_data'] ?? [],
            'class_distribution' => $this->resource['class_distribution'] ?? [],
            'recent_activity' => $this->resource['recent_activity'] ?? [],
            'upcoming_events' => $this->resource['upcoming_events'] ?? [],
            'pending_assignments' => $this->resource['pending_assignments'] ?? [],
            'meta' => [
                'last_updated' => now()->toDateTimeString(),
                'status' => 'success'
            ]
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function withResponse($request, $response)
    {
        $response->header('X-Dashboard-Version', '1.0.0');
    }
}
