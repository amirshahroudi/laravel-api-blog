<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'title'         => $this->title,
            'description'   => $this->description,
            'created_at'    => (string)$this->created_at,
            'like_count'    => $this->like_count,
            'comment_count' => $this->comment_count,
            'tags'          => $this->tags->pluck('name')->toArray(),
            'categories'    => $this->categories->pluck('name')->toArray(),
        ];
    }
}
