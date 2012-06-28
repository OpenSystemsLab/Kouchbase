# Kouchbase #


A ORM like library for [Couchbase](http://couchbase.com/ "Couchbase Server") & [Kohana](http://kohanaframework.org "Kohana PHP Framework") 3.2, impressed by [Sprig](http://github.com/sittercity/sprig "A database modeling system for the Kohana framework") and some other Kohana ORM modules.

**This project is under development, use it as your own risk!**

## Quick Start ##

Each model must:

- Extend the `Kouchbase` class
- Define a protected `_init()` method and set the field mappings

Example of a model:

    class Model_Player extends Kouchbase {

        protected function _init()
        {
            'first_name' => new Kouchbase_Field_Char(array(
                'min_length' => 2,
                'max_length' => 100,
                'label' => 'First Name',
            )),
            'last_name' => new Kouchbase_Field_Char(array(
                'min_length' => 2,
                'max_length' => 100,
                'label' => 'Last Name',
                'default' => ''
            )),
        }
    }

## Interacting with models ##

Loading models is done with the `Kouchbase::factory($name)` method:
    
    $player = Kouchbase::factory('player');

Loading models by calling new `Model_Player` will not work! You must use the `factory()` method.

## Data ##

Model data is read using object properties:

    $first_name = $player->first_name;
    $last_name  = $player->last_name;
Model data is changed the same way:

    $player->first_name = 'My First Name';

You can also use the `load_values()` method set many fields using an associative array:

    $player->load_values(array(
        'first_name' => 'FN',
    ));

## Create, Read, Update, and Delete (CRUD) ##

Reading records is done by setting the search values, then calling the `load()` method:

    $player = Kouchbase::factory('player');
    $player->load(5);

    if ($player->loaded())
    {
        // Do something with the player
    }

It is also possible to pre-populate the model using an array of values:

    $player = Kouchbase::factory('player', array('id' => 10))->load();

Creating new records & Update extsting record is done using the `save()` method:

    $player = Kouchbase::factory('player', array(
        'first_name'     => 'FN',
        'last_name'      => 'LN',
    ));

    // Create a new player
    $player->save();

If the model data does not satisfy the validation requirements, a `Validate_Exception` will be thrown. This exception should be caught and used to show the end user the error messages:

    try
    {
        // Create a new player
        $player->save();
    }
    catch (Validate_Exception $e)
    {
        // Get the errors using the Validate::errors() method
        $errors = $e->array->errors('player');
    }

Deleting a record is done using the `delete()` method:

    $player->delete();

## Field Object Reference ##

Accessing a field object is done using the `field()` method:

    $first_name = $player->field('first_name');
An array of fields can be accessed using the fields() method:

    $fields = $player->fields();

### Types of fields ###

KOuchbase offers most database column types as classes. Each field must extend the `Kouchbase_Field` class. Each field has the following properties:

`empty` : Allow `empty()` values to be used. Default is `FALSE`.

`unique` : This field must have a unique value within the model table. Default is `FALSE`.

`null` : Convert all `empty()` values to `NULL`. Default is `FALSE`.

`editable` : Show the field in forms. Default is `TRUE`.

`default` : Default value for this field. Default is '' (an empty string).

`label` : Human readable label. Default will be the field name converted with `Inflector::humanize()`.

`description` : Description of the field. Default is '' (an empty string).

`filters` : Validate filters for this field.

`rules` : Validate rules for this field.

#### Kouchbase_Field_Boolean #####

A boolean (TRUE/FALSE) field, representing by a checkbox.

Implies `empty = TRUE` and `default = FALSE`.

#### Kouchbase_Field_Char #####

A single line of text, represented by a text input.

Also has the `min_length` and `max_length` properties.


#### Kouchbase_Field_Float #####

A float or decimal number, represented by a text input.

Also has the `places` property.

#### Kouchbase_Field_Integer #####

An integer number, represented with a text input (or a select input, if the choices property is set).

Also has the `min_value` and `max_value` properties.

#### Kouchbase_Field_Text #####

A large block of text, represented by a textarea.

#### Kouchbase_Field_O2O ####

not implement, use `Kouchbase_Field_O2M` instead

#### Kouchbase_Field_M2O ####

A reference to another model by the child model `id` value.

Has the `model` property, the name of another Kouchbase model.

#### Kouchbase_Field_O2M #####

A reference to many other models by this model `id` value

Has the `model` property, the name of another Kouchbase model.

#### Kouchbase_Field_M2M #####
Not implement yet

## One To Many Relations ##
There's a few ways to add and remove one to many relations. The first is to use the raw field names:

    $player->plants = array(1, 2, 3);
This will completely overwrite all the relationships for this model. The only relations to this model will be 1, 2 and 3.

The second way is to use the `add_relation()` and `remove_relation()` methods:

    $player->add_relation('plants', 1);
You can also pass an object:

    $player->add_relation('plants', $plant);
Or an array of ids:
    $player->add_relation('plants', array(1, 2, 3);

Or an array of objects!

    $player->add_relation('plants', array($plant1, $plant2, $plant3);

### Removing Relations ###

To remove a relation, use the same techniques as above.

    $player->plants = array(1,2,3)
This will completely overwrite all the relationships for this model. The only relations to this model will be 1, 2 and 3.

Use `remove_relation()` instead of `add_relation()`:

    $player->remove_relation('plants', 1);
You can also pass an object:

    $player->remove_relation('plants', $plant);
Or an array of ids:

    $player->remove_relation('plants', array(1, 2, 3);
Or an array of objects!

    $player->remove_relation('plants', array($plant1, $plant2, $plant3);
