<?php defined('SYSPATH') or die('No direct script access.');

class Kouchbase_Validation_Exception extends Validation_Exception {
	/**
	 * @var  string  Name of model
	 */
	public $model;

	public function __construct($model, Validation $array, $message = 'Failed to validate array', array $values = NULL, $code = 0)
	{
		$this->model = $model;

		parent::__construct($array, $message, $values, $code);
	}
}
