<?php

namespace MorningTrain\Laravel\Fields;

use MorningTrain\Laravel\Fields\Fields\Field as BaseField;
use MorningTrain\Laravel\Fields\Fields\StaticField;

class Field
{

    /**
     * @param null|string $name
     * @return BaseField
     */
    public static function create(string $name = null)
    {
        return new BaseField($name);
    }

    /**
     * @param string $name
     * @param null|any $value
     * @return StaticField
     */
    public static function hidden($name, $value = null)
    {
        $field = new StaticField($name);

        if ($value !== null) {
            $field->value($value);
        }

        return $field;
    }


}
