<?php

namespace MorningTrain\Laravel\Fields\Contracts;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface FieldContract
{
    /**
     * Update times
     */
    const BEFORE_SAVE = 'before';
    const AFTER_SAVE = 'after';
    const BOTH = 'both';

    /**
     * @param Model $model
     * @return mixed
     */
    public function getValidationRules(Model $model);

    /**
     * @param Model $model
     * @param Request $request
     * @param string $timeline
     * @return mixed
     */
    public function update(Model $model, Request $request, string $timeline);

}
