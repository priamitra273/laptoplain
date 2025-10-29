<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class MenuStoreRequest extends FormRequest
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
        $available_routes = collect(Route::getRoutes()->getRoutesByName())->keys()->all();

        return [
            'label' => 'required|string|max:255',
            'parent_uuid' => 'nullable|uuid:4|exists:App\Models\Menu,uuid',
            'icon' => 'required|string|max:255',
            'route_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn() => !empty($this->parent_uuid)),
                Rule::in($available_routes),
                Rule::unique('menus', 'route_name')->withoutTrashed()
            ],
            'sequence_number' => 'nullable|numeric',
            'is_active' => 'required|boolean',
        ];
    }
}
