<?php

namespace MorningTrain\Laravel\Fields;

use MorningTrain\Laravel\Fields\Fields\BelongsToMany;
use MorningTrain\Laravel\Fields\Fields\EnumField;
use MorningTrain\Laravel\Fields\Fields\Field as BaseField;
use MorningTrain\Laravel\Fields\Fields\RelationshipField;
use MorningTrain\Laravel\Fields\Fields\StaticField;

class Field
{

    use Illuminate\Support\Traits\Macroable;

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
     * @param null|mixed $value
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

    /**
     * @param string $name
     * @param bool $string
     * @return BelongsToMany
     */
    public static function belongsToMany(string $name = null, bool $strict = false)
    {
        return new BelongsToMany($name, $strict);
    }

    /**
     * @param string $name
     * @param bool $string
     * @return RelationshipField
     */
    public static function relationship(string $name = null, bool $strict = false)
    {
        return new RelationshipField($name, $strict);
    }

    /**
     * @param string $name
     * @param $from
     * @return EnumField
     */
    public static function enum(string $name = null, $from = null)
    {
        $field = new EnumField($name);

        if($from !== null) {
            $field->from($from);
        }

        return $field;
    }

}
