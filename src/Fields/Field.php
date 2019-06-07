<?php

namespace MorningTrain\Laravel\Fields\Fields;


use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
        $this->validator = $validator;
        $this->validatorName = $validatorName;

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

    public function updates(Closure $closure, string $updateTime = null)
    {
        $this->update = $closure;

        if (is_string($updateTime)) {
            $this->updateTime = $updateTime;
        }

        return $this;
    }

    public function updatesAt(string $updateTime)
    {
        $this->updateTime = $updateTime;
        return $this;
    }

    protected function getUpdateTime()
    {
        return $this->updateTime;
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
        return $request->input($this->getRequestName());
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
        if (($this->getUpdateTime() !== $timeline) && ($this->getUpdateTime() !== static::BOTH)) {
            return;
        }

        if ($this->checkRequest($request)) {
            $this->performUpdate(
                $model,
                $request
            );
        }
    }

}
