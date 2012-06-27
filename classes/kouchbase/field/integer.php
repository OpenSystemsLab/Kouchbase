<?php defined('SYSPATH') or die('No direct script access.');

class Kouchbase_Field_Integer extends Kouchbase_Field {

	public $min_value;

	public $max_value;

	public function value($value)
	{
		$value = parent::value($value);

		if ($value === '' OR $value === NULL)
		{
			$value = NULL;
		}
		else
		{
			$value = (int) $value;
		}

		return $value;
	}

}