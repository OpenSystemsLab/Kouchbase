<?php defined('SYSPATH') or die('No direct script access.');

abstract class Kouchbase_Field_Core {
	/**
	 * @var bool Allow `empty()` values to be used. Default is `FALSE`.
	 */
	public $empty = FALSE;

	/**
	 * @var bool A primary key field. Multiple primary keys (composite key) can be specified. Default is `FALSE`.
	 */
	public $primary = FALSE;

	/**
	 * @var bool This field must have a unique value within the model table. Default is `FALSE`.
	 */
	public $unique = FALSE;

	/**
	 * @var bool Convert all `empty()` values to `NULL`. Default is `FALSE`.
	 */
	public $null = FALSE;

	/**
	 * @var bool Show the field in forms. Default is `TRUE`.
	 */
	public $editable = TRUE;

	/**
	 * @var string Human readable label. Default will be the field name converted with `Inflector::humanize()`.
	 */
	public $label;

	/**
	 * @var string Description of the field. Default is `''` (an empty string).
	 */
	public $description = '';

	 /**
	 * @var array {@link HTML} html attribute for the field.
	 */
	public $attributes = NULL;

	/**
	 * @var bool The column is present in the database table. Default: TRUE
	 */
	public $in_db = TRUE;

	/**
	 * @var array {@link Validate} filters for this field.
	 */
	public $filters = array();

	/**
	 * @var array {@link Validate} rules for this field.
	 */
	public $rules = array();

	/**
	 * @var string Default value for this field. Default is `''` (an empty string).
	 */
	public $default = '';

	public static function factory($type)
	{

	}

	public function __construct(array $options = NULL)
	{
		if ( ! empty($options))
		{
			$options = array_intersect_key($options, get_object_vars($this));

			foreach ($options as $key => $value)
			{
				$this->$key = $value;
			}
		}
	}

	public function value($value)
	{
		if ($this->null AND empty($value))
		{
			// Empty values are converted to NULLs
			$value = NULL;
		}

		return $value;
	}
}