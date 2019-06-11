<?php

namespace MorningTrain\Laravel\Fields\Fields;

class EnumField extends Field
{

    protected $enumOptions = [];

    public function options(array $options)
    {
        $this->enumOptions = $options;
        return $this;
    }

    public function from($enum)
    {
        $this->enumOptions = $enum::values();
        return $this;
    }

    protected $required = false;

    public function required(bool $required = true)
    {
        $this->required = $required;
        return $this;
    }

    protected $validatorName = null;

    public function validatorName(string $validatorName = null)
    {
        $this->validatorName = $validatorName;

        return $this;
    }

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $this->validates(function () {
            $rules = [];

            if ($this->required) {
                $rules[] = 'required';
            }

            if (!empty($this->enumOptions)) {
                $rules[] = 'in:' . implode(',', $this->enumOptions);
            }

            return empty($rules) ? null : implode('|', $rules);
        }, $this->validatorName);
    }

}
