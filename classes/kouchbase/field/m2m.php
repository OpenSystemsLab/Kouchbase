<?php defined('SYSPATH') or die('No direct script access.');

class Kouchbase_Field_M2M extends Kouchbase_Field_O2M {
    public $editable = TRUE;

    public $through;

    // This fk
    public $left_foreign_key = NULL;

    // other model's fk
    public $right_foreign_key = NULL;

    // Overload __construct to support legacy foreign_key fields
    public function __construct(array $options = NULL)
    {
        parent::__construct($options);

        if (isset($this->foreign_key))
            $this->left_foreign_key = $this->foreign_key;
    }
}