<?php

namespace App\Rules;

use App\Facades\Sqids;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class SqidExists implements ValidationRule
{
    public function __construct(
        protected string $model,
        protected string $field = 'id',
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $id = Sqids::decode($value);

            $modelClass = $this->model;

            if (! class_exists($modelClass) && ! is_subclass_of($modelClass, Model::class)) {
                $fail("The {$attribute} field is not valid.");
            }

            $model = $modelClass::where($this->field, $id)->first();

            if (! $model) {
                $fail("The {$attribute} field does not exist.");
            }
        } catch (\Throwable $th) {
            $fail("The {$attribute} field is not valid.");
        }
    }
}
