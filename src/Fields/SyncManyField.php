<?php

namespace MorningTrain\Laravel\Fields\Fields;

use \Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SyncManyField extends Field
{
    protected $strict;
    protected $relation;
    protected $modifyValues;

    public function __construct(string $name, bool $strict = true)
    {
        parent::__construct($name);

        $this->strict = $strict;

        $this->updatesAt(Field::AFTER_SAVE);

        $this->updates(function(Model $model, $attr, $values){
            $values = $this->mapValues((array)$values);
            $this->sync($model, $values->all());
        });
    }

    protected function checkRequest(Request $request)
    {
        return $this->strict ?
            true :
            $request->has($this->getRequestName());
    }

    protected function sync(Model $model, array $values)
    {
        if ($this->relation !== null) {
            $model->{$this->relation}()->sync($values);
        }
    }

    protected function mapValues(array $values)
    {
        $values = collect($values);

        $processor = $this->modifyValues;

        if ($processor instanceof Closure) {
            return $processor($values);
        }

        return $values->pluck('id');
    }

    /*
     -------------------------------
     Setters
     -------------------------------
     */

    public function relation(string $relation) {
        $this->relation = $relation;

        return $this;
    }

    public function modify(Closure $modify)
    {
        $this->modifyValues = $modify;

        return $this;
    }
}
