<?php

namespace App\Http\Resources;

use App\Support\PublicFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicCompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id'             => $this->id,
            'name'           => $this->name,
            'logo'           => PublicFile::url($this->logo),
            'governorate'    => new GovernorateResource($this->whenLoaded('governorate')),
            'likes_count'    => (int) ($this->likes_count ?? 0),
            'ratings_count'  => (int) ($this->ratings_count ?? 0),
            'average_rating' => $this->ratings_avg_rating !== null
                ? round((float) $this->ratings_avg_rating, 1)
                : null,
        ];

        if (isset($this->is_liked)) {
            $data['is_liked'] = (bool) $this->is_liked;
        }

        if (array_key_exists('my_rating', $this->resource->getAttributes())) {
            $data['my_rating'] = $this->my_rating !== null ? (int) $this->my_rating : null;
        }

        return $data;
    }
}
