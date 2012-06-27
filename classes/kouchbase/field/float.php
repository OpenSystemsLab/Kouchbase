<?php defined('SYSPATH') or die('No direct script access.');

class Kouchbase_Field_Float extends Kouchbase_Field {

	public $places;

	public function value($value)
	{
		if ($this->null AND empty($value))
		{
			// Empty values are converted to NULLs
			$value = NULL;
		}
		else
		{
			if (is_string($value))
			{
				$locale = localeconv();

				// Locale-aware conversion from string to float:
				// - Remove the thousands separator
				// - Replace the decimal point with a period
				$value = str_replace(array($locale['thousands_sep'], $locale['decimal_point']), array('', '.'), $value);
			}

			$value = floatval($value);
		}

		return $value;
	}
}