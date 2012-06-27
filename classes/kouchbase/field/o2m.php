<?php defined('SYSPATH') or die('No direct script access.');

class Kouchbase_Field_O2M extends Kouchbase_Field_ForeignKey {

    public $empty = TRUE;
    public $default = array();
    public $editable = FALSE;

    public function value($value)
    {
        if (empty($value) AND $this->empty)
		{
			return array();
		}
		elseif (is_object($value))
		{
		    $model = Couhana::factory($this->model);

		    // Assume this is a Database_Result object
		    $value = $value->as_array();
		}
		else
		{
		    // Value must always be an array
		    $value = (array) $value;
		}

		if ($value)
		{
		    // Combine the value to make a mirrored array
		    $value = array_combine($value, $value);

		    foreach ($value as $id)
		    {
		        // Convert the value to the proper type
		        $value[$id] = parent::value($id);
		    }
		}
		return $value;
    }
}