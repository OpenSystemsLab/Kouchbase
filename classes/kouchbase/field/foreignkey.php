<?php defined('SYSPATH') or die('No direct script access.');

abstract class Kouchbase_Field_ForeignKey extends Kouchbase_Field_Char {

	public $null = TRUE;

	public $in_db = FALSE;

	public $model;

	public $foreign_key = NULL;

	public $primary_key = NULL;

	public function value($value)
	{
		if (is_object($value))
		{
			// Assume this is a Sprig object
			$value = $value->{$value->pk()};
		}

		return parent::value($value);
	}

}