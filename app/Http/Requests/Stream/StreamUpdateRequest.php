<?php

namespace App\Http\Requests\Stream;

use Illuminate\Foundation\Http\FormRequest;

class StreamUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cctv_name' => 'required|string|max:255',
            'ip_flussonic' => 'required|ip',
            'ip_static' => 'required|ip',
            'link_embed' => 'required|url',
            'link_embed_nonrelay' => 'required|url',
            'link_rtsp' => 'required|string|max:255|regex:/^rtsp:\/\/([a-zA-Z0-9\-_]+(:[a-zA-Z0-9\-_]+)?@)?[a-zA-Z0-9\-_]+(\.[a-zA-Z0-9\-_]+)*(:[0-9]+)?(\/[a-zA-Z0-9\-_]+)*$/'
        ];
    }

    public function messages(): array
    {
        return [
            'link_rtsp.regex' => 'The :attribute is invalid format'
        ];
    }
}
