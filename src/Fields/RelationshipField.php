<?php

namespace MorningTrain\Laravel\Fields\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use MorningTrain\Laravel\Fields\Contracts\FieldContract;

class RelationshipField extends FieldCollection
{
    protected $strict;
    protected $relation;

    public function __construct(string $name = null, bool $strict = false)
    {
        parent::__construct($name);

        $this->strict = $strict;
        $this->relation = Str::camel($name);

        $this->updatesAt(Field::BEFORE_SAVE);
    }
    
    protected $is_single = null;

    public function single($is_single = true)
    {
        $this->is_single = $is_single;

        return $this;
    }

    //////////////////////////////////
    /// Helpers to save order of items
    //////////////////////////////////

    protected $set_order_to = null;

    public function setOrderTo($set_order_to)
    {
        $this->set_order_to = $set_order_to;

        return $this;
    }

    protected function shouldSetOrder()
    {
        return $this->set_order_to !== null;
    }

    protected function setOrder(Model $model, $index)
    {
        $model->{$this->set_order_to} = $index;
    }

    //////////////////////////////////
    /// Setters
    //////////////////////////////////

    protected $fields = [];

    public function fields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    protected $resource;

    public function resource(string $resource)
    {
        $this->resource = $resource;

        return $this;
    }

    protected $remove_missing = false;

    public function removeMissing($remove_missing = true)
    {
        $this->remove_missing = $remove_missing;

        return $this;
    }

    protected $dbDelete = false;

    public function dbDelete($dbDelete = true)
    {
        $this->dbDelete = $dbDelete;

        return $this;
    }

    //////////////////////////////////
    /// Getters
    //////////////////////////////////

    protected function getCollection()
    {
        $required = $this->checkRequest(request());
        if (!$required) {
            return collect();
        }

        $collection = [];

        if (!empty($this->fields)) {
            $collection = $this->fields;

        } else {
            if (method_exists($this->resource, 'getFields')) {
                $collection = $this->resource::getFields();
            }
        }

        return collect($collection)
            ->map(function (FieldContract $field) {
                $field->requestName("{$this->getRequestName()}.{$field->getName()}");
                return $field;
            });
    }

    protected function getRelationUpdateTime(Model $model)
    {
        return $this->isSingleRelation($model) ?
            Field::BEFORE_SAVE :
            Field::AFTER_SAVE;
    }

    //////////////////////////////////
    /// Methods
    //////////////////////////////////

    protected function updateRelation(Model $model, Model $related, Relation $relation)
    {
        if ($relation instanceof BelongsTo) {
            $related->save();
            $model->{$relation->getForeignKey()} = $related->id;
        }

        if ($relation instanceof HasOneOrMany) {
            $related->{$relation->getForeignKeyName()} = $model->id;
            $related->save();
        }
    }

    protected function updateRelated(Model $model, Request $request, string $timeline, $value, int $index)
    {
        $relation_type = $model->{$this->relation}();
        $related_class = $relation_type->getRelated();
        $primary_key = (new $related_class())->getKeyName();

        $related = null;

        if (!empty($value) && isset($value[$primary_key])) {
            $related = $related_class::where($primary_key, $value[$primary_key])->first();
        }

        if ($related === null) {
            $related = new $related_class();
        }

        $fields = $this->getCollection();

        $fields->each(function (FieldContract $field) use ($related, $request, $index) {
            $base_name = $this->getRequestName();
            $old_request_name = explode($base_name, $field->getRequestPath());
            $new_request_name = join("{$base_name}.{$index}", $old_request_name);

            $field->requestName($new_request_name); //New request name

            $field->update($related, $request, Field::BEFORE_SAVE);
        });

        $this->updateRelation($model, $related, $relation_type);

        $fields->each(function (FieldContract $field) use ($related, $request) {
            $field->update($related, $request, Field::AFTER_SAVE);
        });

        if($this->shouldSetOrder()) {
            $this->setOrder($related, $index);
        }

        if ($related->isDirty()) {
            $related->save();
        }

        return $related;
    }

    protected function isSingleRelation(Model $model)
    {
        $relation = $model->{$this->relation}();

        return $relation instanceof BelongsTo;
    }

    protected function resolveRequestEntries(Model $model, Request $request)
    {
        $value = $this->getRequestValue($request);

        if ($this->isSingleRelation($model) || $this->is_single === true) {
            $value = [$value];

            $content = $request->all();
            Arr::set($content, $this->getRequestName(), $value);
            $request->merge($content);
        }

        return $value;
    }

    //////////////////////////////////
    /// Overwrites
    //////////////////////////////////

    protected function checkRequest(Request $request)
    {
        return $this->strict ?
            true :
            $request->has($this->getRequestName()) && is_array($this->getRequestValue($request));
    }

    public function update(Model $model, Request $request, string $timeline)
    {
        if (($this->getRelationUpdateTime($model) !== $timeline) && ($this->getRelationUpdateTime($model) !== Field::BOTH)) {
            return;
        }

        if ($this->checkRequest($request)) {

            $entries = $this->resolveRequestEntries($model, $request);

            $ids = [];

            $resolvedModel = $this->resolveModel($model, $this->getPropertyName());

            if (!empty($entries)) {
                foreach ($entries as $index => $entry) {

                    $related = $this->updateRelated($resolvedModel, $request, $timeline, $entry, $index);

                    // Update resolved model if not main one
                    if ($model !== $resolvedModel) {
                        $resolvedModel->save();
                    }

                    // Run after update
                    $this->runAfterUpdate($model);

                    $ids[] = $related->id;

                }
            }

            if ($this->remove_missing === true) {
                $this->dbDelete ?
                    $resolvedModel->{$this->relation}()->whereNotIn('id', $ids)->delete() :
                    $resolvedModel->{$this->relation}()->whereNotIn('id', $ids)->get()->each->delete();
            }

        }
    }

}
