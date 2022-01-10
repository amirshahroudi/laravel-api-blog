<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\File;

class ProfileUpdateRequest extends FormRequest
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
            'name'              => ['required', 'string', 'max:255'],
            'profile_image_url' => ['required', 'string',
                                    function ($attribute, $value, $fail) {
                                        if (!File::exists(public_path('profiles/' . File::basename($value)))) {
                                            $fail('The ' . $attribute . ' is not exists.');
                                        }
                                    },
            ],
        ];
    }
}
