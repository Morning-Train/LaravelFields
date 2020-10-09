<?php

namespace MorningTrain\Laravel\Fields\Fields;

use App\Support\Eloquent\Model;
use Illuminate\Http\Request;

class ValidatorField extends Field
{

    public function __construct(string $name = null, $rules = null)
    {
        parent::__construct($name);

        if($rules !== null) {
            $this->validates($rules);
        }

    }

    public function update(Model $model, Request $request, string $timeline)
    {
        /// Do not update...
    }

}
