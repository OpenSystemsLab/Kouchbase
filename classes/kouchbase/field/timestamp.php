<?php defined('SYSPATH') or die('No direct script access.');

class Kouchbase_Field_Timestamp extends Kouchbase_Field_Integer {

	public $auto_now_create = FALSE;

	public $auto_now_update = FALSE;

	public $default = NULL;

	public $format = 'Y-m-d G:i:s A';

	public function value($value)
	{
		if ($value AND is_string($value) AND ! ctype_digit($value))
		{
			$value = strtotime($value);
		}

		return parent::value($value);
	}
}