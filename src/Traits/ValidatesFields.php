<?php

namespace MorningTrain\Laravel\Fields\Traits;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use MorningTrain\Laravel\Fields\Contracts\FieldContract;

trait ValidatesFields
{

    use ValidatesRequests;

    protected function performValidation(Model $model, Request $request, bool $patch = false)
    {
        // Compute validation rules
        $rules = [];

        /** @var FieldContract $field */
        foreach ($this->fields as $field) {
            $rule = $field->getValidationRules($model);

            if (is_array($rule)) {
                $rules = array_merge($rules, $rule);
            }
        }

        // Convert validation rules if patch request
        if ($patch) {
            $rules = $this->getPatchValidationRules($rules);
        }
        
        // Validate
        try {
            $this->validate($request, $rules);
        } catch (ValidationException $exception) {
            throw new HttpResponseException(response()->json([
                "errors" => $exception->errors()
            ], 422));
        }
    }

    protected function getPatchValidationRules(array $rules)
    {
        $patch_rules = [];

        foreach ($rules as $prop => $rule) {
            $patch_rules[$prop] = "sometimes|$rule";
        }

        return $patch_rules;
    }

}
