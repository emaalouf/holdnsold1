<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'reportable_type' => $this->reportable_type,
            'reportable_id' => $this->reportable_id,
            'reportable' => $this->when($this->reportable, function () {
                return $this->reportable_type === 'App\Models\User'
                    ? new UserResource($this->reportable)
                    : new AuctionResource($this->reportable);
            }),
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status,
            'admin_notes' => $this->when($request->user()->isAdmin(), $this->admin_notes),
            'resolver' => new UserResource($this->whenLoaded('resolver')),
            'resolved_at' => $this->resolved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 