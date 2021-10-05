# Laravel Fields

## Install

Via Composer

``` bash
$ composer require morningtrain/laravel-fields
```

## Features
A field is at its core responsible for taking a request value 
and applying it to an eloquent model attribute.

### Applying fields
Having a field and a model instance, it can be applied like this:

```php
$field->update($model, $request, FieldContract::BEFORE_SAVE);
```
and the following for post-save. 

```php
$field->update($model, $request, FieldContract::AFTER_SAVE);
```
Some fields will know that it should run before saving the model (like setting a basic attribute value)
while some fields should be run after (attaching related models).

A more complete example would be:

```php
$request = request();

if (is_array($fields) && !empty($fields)) {

    foreach ($fields as $field) {
        $field->update($model, $request, FieldContract::BEFORE_SAVE);
    }

    $model->save();

    foreach ($fields as $field) {
        $field->update($model, $request, FieldContract::AFTER_SAVE);
    }

}
```

The example uses the following classes:

```php
use Illuminate\Database\Eloquent\Model;
use MorningTrain\Laravel\Fields\Contracts\FieldContract;
```


### Fields

#### Basic field

```php
Field::create('name_of_attribute')
```

#### Hidden (static) field

```php
Field::hidden('name_of_attribute', 'value to apply')
```

#### Belongs to many field

```php
Field::belongsToMany('name_in_request')
    ->relation('name_of_relation')
    ->removeMissing()
```


## Credits
This package is developed and actively maintained by [Morningtrain](https://morningtrain.dk).

<!-- language: lang-none -->
     _- _ -__ - -- _ _ - --- __ ----- _ --_  
    (         Morningtrain, Denmark         )
     `---__- --__ _ --- _ -- ___ - - _ --_ ´ 
         o                                   
        .  ____                              
      _||__|  |  ______   ______   ______ 
     (        | |      | |      | |      |
     /-()---() ~ ()--() ~ ()--() ~ ()--() 
    --------------------------------------

