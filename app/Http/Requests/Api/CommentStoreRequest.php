<?php

namespace App\Http\Requests\Api;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

class CommentStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'text'      => ['required'],
            'post_id'   => ['required', 'exists:posts,id'],
            'parent_id' => [
                function ($attribute, $value, $fail) {
                    if ($value != 0) {
                        if (!Comment::find($value)) {
                            $fail('The ' . $attribute . ' is not exists in database.');
                        }
                    }
                },
            ],
        ];
    }
}
