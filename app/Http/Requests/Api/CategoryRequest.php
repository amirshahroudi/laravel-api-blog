<?php

namespace App\Http\Requests\Api;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
            'name'      => ['required'],
            'parent_id' => ['integer',
                            function ($attribute, $value, $fail) {
                                if ($value != 0) {
                                    if (!Category::find($value)) {
                                        $fail('The ' . $attribute . ' is not exists in database.');
                                    }
                                }
                            },
            ],
        ];
    }
}
