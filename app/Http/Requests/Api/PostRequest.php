<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
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
            'title'       => ['required', 'string', 'min:5'],
            'description' => ['required', 'string', 'min:10'],
            'tags'        => ['required', 'array', 'exists:tags,id'],
            'categories'  => ['required', 'array', 'exists:categories,id'],
        ];
    }
}
