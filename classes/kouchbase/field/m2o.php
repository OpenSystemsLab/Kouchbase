<?php defined('SYSPATH') or die('No direct script access.');

class Kouchbase_Field_M2O extends Kouchbase_Field_ForeignKey {

    public $empty = TRUE;
    public $default = array();
    public $editable = FALSE;

    public $in_db = TRUE;
}