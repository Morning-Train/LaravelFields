<?php

namespace MorningTrain\Laravel\Fields\Fields;

class BelongsToMany extends RelationshipField
{

    public function __construct(string $name = null, bool $strict = false)
    {
        parent::__construct($name, $strict);

        $this->updatesAt(Field::AFTER_SAVE);
    }

}
