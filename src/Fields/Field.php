<?php

namespace MorningTrain\Laravel\Fields\Fields;


use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MorningTrain\Laravel\Fields\Contracts\FieldContract;
use MorningTrain\Laravel\Support\Traits\StaticCreate;

class Field implements FieldContract
{

    use StaticCreate;

    public function __construct(string $name = null)
    {
        $this->name = $name;
    }

    /*
     -------------------------------
     Names
     -------------------------------
     */

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @var string
     */
    protected $requestName;

    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function propertyName(string $name)
    {
        $this->propertyName = $name;
        return $this;
    }

    protected function getPropertyName()
    {
        return $this->propertyName ?: $this->name;
    }

    public function requestName(string $name)
    {
        $this->requestName = $name;
        return $this;
    }

    protected function getRequestName()
    {
        return $this->requestName ?: $this->name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRequestPath()
    {
        return $this->getRequestName();
    }

    /*
     -------------------------------
     Permissions
     -------------------------------
     */

    protected $permissions = [];

    public function can($permissions)
    {
        $this->permissions = (array)$permissions;

        return $this;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function checkPermissions(Model $model): bool
    {
        foreach ($this->permissions as $permission) {
            if (!Auth::check() || !Auth::user()->can($permission, $model)) {
                return false;
            }
        }

        return true;
    }

    /*
     -------------------------------
     Default value
     -------------------------------
     */

    /**
     * @var string
     */
    protected $defaultValue = null;

    public function defaultValue($value = null)
    {
        $this->defaultValue = $value;

        return $this;
    }

    /*
     -------------------------------
     Validation
     -------------------------------
     */

    /**
     * @var string|array|Closure
     */
    protected $validator;
    protected $validatorName;

    public function validates($validator, string $validatorName = null)
    {
        $this->validatorName = $validatorName ?? $this->getRequestName();

        if(is_array($validator) && count($validator) === count($validator, COUNT_RECURSIVE)) {
            $validator = [
                $this->validatorName => $validator
            ];
        }

        $this->validator = $validator;

        return $this;
    }

    protected function getValidator()
    {
        return $this->validator;
    }

    protected function processRules(Model $model, $rules)
    {
        return $rules;
    }

    protected function getValidatorRules(Model $model)
    {
        $validator = $this->getValidator();
        $rules     = $validator instanceof Closure ? $validator($model) : $validator;
        $name      = $this->validatorName ?? $this->getRequestName();

        return is_string($rules) ? [$name => $rules] : $rules;
    }

    public function getValidationRules(Model $model)
    {
        $rules = $this->getValidatorRules($model);

        return $this->processRules(
            $model,
            $rules
        );
    }

    /*
     -------------------------------
     Value processing
     -------------------------------
     */

    /**
     * @var Closure
     */
    protected $processor;

    public function processes(Closure $processor)
    {
        $this->processor = $processor;
        return $this;
    }

    /*
     -------------------------------
     Updates
     -------------------------------
     */

    /**
     * @var Closure
     */
    protected $update;

    /**
     * @var string
     */
    protected $updateTime = 'before';

    public function updates(Closure $closure, $updateTime = null)
    {
        $this->update = $closure;

        if (!is_null($updateTime)) {
            $this->updateTime = $updateTime;
        }

        return $this;
    }

    public function updatesAt($updateTime)
    {
        $this->updateTime = $updateTime;
        return $this;
    }

    public function updatesBefore()
    {
        $this->updatesAt(Field::BEFORE_SAVE);

        return $this;
    }

    public function updatesAfter()
    {
        $this->updatesAt(Field::AFTER_SAVE);

        return $this;
    }

    protected function getUpdateTime(Model $model)
    {
        $updateTime = $this->updateTime;

        return $updateTime instanceof Closure ?
            (string)$updateTime($model) :
            (string)$updateTime;
    }

    public function getUpdateMethod()
    {
        return $this->update ?: function (Model $model, string $property, $value) {
            $model->$property = $value;
        };
    }

    protected function checkRequest(Request $request)
    {
        return $request->has($this->getRequestName());
    }

    protected function getRequestValue(Request $request)
    {
        if ($request->hasFile($this->getRequestName())) {
            return $request->file($this->getRequestName(), $this->defaultValue);
        }

        return $request->input($this->getRequestName(), $this->defaultValue);
    }

    protected function processValue(Model $model, $value)
    {
        $processor = $this->processor;
        return $processor instanceof Closure ? $processor($value) : $value;
    }

    protected function performUpdate(Model $model, Request $request)
    {
        $update = $this->getUpdateMethod();

        return $update(
            $model,
            $this->getPropertyName(),
            $this->processValue($model, $this->getRequestValue($request)),
            $this
        );
    }

    public function update(Model $model, Request $request, string $timeline)
    {
        if (($this->getUpdateTime($model) !== $timeline) && ($this->getUpdateTime($model) !== static::BOTH)) {
            return;
        }

        if ($this->checkRequest($request) && $this->checkPermissions($model)) {
            $this->performUpdate(
                $model,
                $request
            );
        }
    }

}
