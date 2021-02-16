<?php

namespace MorningTrain\Laravel\Fields\Fields;

use Illuminate\Http\Request;

class StaticField extends Field
{
    public function value($value)
    {
        $this->defaultValue = $value;

        return $this;
    }

    protected function checkRequest(Request $request)
    {
        return true;
    }
}
