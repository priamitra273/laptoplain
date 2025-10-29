<?php

namespace App\Http\Requests\Stream;

use Illuminate\Foundation\Http\FormRequest;

class StreamImportRequest extends FormRequest
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
            'streams.*.cctv_name' => 'required|string|exists:cctvs,name',
            'streams.*.cctv_id' => 'nullable|integer|exists:cctvs,id',
            'streams.*.ip_flussonic' => 'nullable|ip',
            'streams.*.ip_static' => 'nullable|ip',
            'streams.*.link_embed' => 'nullable|url',
            'streams.*.link_embed_nonrelay' => 'nullable|url',
            'streams.*.link_rtsp' => 'required|string|max:255|regex:/^rtsp:\/\/([a-zA-Z0-9\-_]+(:[a-zA-Z0-9\-_]+)?@)?[a-zA-Z0-9\-_]+(\.[a-zA-Z0-9\-_]+)*(:[0-9]+)?(\/[a-zA-Z0-9\-_]+)*$/',
        ];
    }
}