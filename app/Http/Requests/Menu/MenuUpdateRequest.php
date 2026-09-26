<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

class MenuUpdateRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $available_routes = collect(Route::getRoutes()->getRoutesByName())->keys()->all();

        return [
            'label' => 'required|string|max:255',
            'parent_uuid' => 'nullable|uuid:4|exists:App\Models\Menu,uuid',
            'icon' => 'required|string|max:255|starts_with:i-lucide-',
            'route_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => ! empty($this->parent_uuid)),
                Rule::in($available_routes),
                Rule::unique('menus', 'route_name')
                    ->withoutTrashed()
                    ->ignore($this->menu->id),
            ],
            'sequence_number' => 'nullable|numeric',
            'is_active' => 'required|boolean',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        $this->merge([
            'parent_id' => $this->parent_uuid ? Menu::findByUuid($this->parent_uuid)->id : null,
        ]);
    }
}
