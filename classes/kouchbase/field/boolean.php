<?php defined('SYSPATH') or die('No direct script access.');

class Kouchbase_Field_Boolean extends Kouchbase_Field {

	public $empty = TRUE;

	public $default = FALSE;

	public $filters = array('filter_var' => array(FILTER_VALIDATE_BOOLEAN));

	public $append_label = TRUE;

	public function value($value)
	{
		return (boolean) $value;
	}
}