<?php defined('SYSPATH') or die('No direct script access.');


abstract class Kouchbase_Core {
    protected $_model;
    protected $_init;

    protected $_fields = array();
    protected $_values = array();
    protected $_related = array();

    protected $_loaded;

    /**
     * Couhana factory
     * @param string $name
     * @param array $values
     *
     * @return Kouchbase
     */
    public static function factory($name, array $values = array())
    {
        $model = 'Model_'.$name;
        $model = new $model;

        if ($values)
        {
            $model->load_values($values);
        }

        return $model;
    }

    /**
     * Constructor
     *
     * @return  void
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Returns the model name.
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->_model;
    }

    /**
     * Returns the attributes that should be serialized.
     *
     * @return  void
     */
    public function __sleep()
    {
        return array('_values');
    }

    /**
     * Restores model data
     *
     * @return  void
     */
    public function __wakeup()
    {
        $this->init();
    }

    /**
     * Checks if a field is set
     *
     * @return  boolean  field is set
     */
    public function __isset($name)
    {
        return isset($this->_fields[$name])
        ? isset($this->_values[$name])
        : isset($this->_related[$name]);
    }

    /**
     * Gets a field's value
     *
     * @param  string  field name
     *
     * @return  mixed
     */
    public function __get($name)
    {
    	if (!$this->_init)
    	{
    		$this->init();
    	}
    	if (isset($this->_fields[$name]))
    	{

    		if (isset($this->_related[$name]))
    		{
    			return $this->_related[$name];
    		}

    		$field = $this->_fields[$name];

    		$value = isset($this->_values[$name])
    		? $this->_values[$name]
    		: NULL;

    		if ($field instanceof Kouchbase_Field_ForeignKey)
    		{
    			if ( ! isset($this->_related[$name]))
    			{
    				$model = Kouchbase::factory($field->model);
    				if ($field instanceof Kouchbase_Field_O2M)
    				{

    					if ($field instanceof Kouchbase_Field_M2M)
    					{
    						throw new Kouchbase_Exception('M2M is not supported yet');
    					}
    					else
    					{

    						if (isset($value))
    						{
    							$related = $field->value($value);
    						}
    						else
    						{
    							// player1_plants
    							$key = $this->_model . $this->id . '_' . $name;
    							$related = KouchbaseDB::instance()->get($key);
    						}

    						if(!isset($related))
    						{
    							$related = array();
    						}

    						$this->_related[$name] = $related;



    						$value = array();

    						foreach($related as $id)
    						{
    							$value[] = Kouchbase::factory($field->model)->load($id);
    						}
    					}

    				}
    				elseif ($field instanceof Kouchbase_Field_M2O)
    				{
    					$value = isset($value)?$field->value($value):(isset($field->default)?$field->default:NULL);
    					return Kouchbase::factory($field->model)->load($value);

    				}
    				return $value;
    			}
    		}

    		if ( $value === NULL && isset($field->default))
    		{
    			$value = $field->default;
    		}

    		return $value;

    	}
    	else
    	{
    		throw new Kouchbase_Exception(':name model does not have a field :field',
    				array(':name' => get_class($this), ':field' => $name));
    	}
    }

    /**
     * Magic method for setting the value of a field.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return void
     */
    public function __set($name, $value)
    {
        if (!$this->_init)
        {
            $this->init();
        }
        if (isset($this->_fields[$name]))
        {
            $field = $this->_fields[$name];
            if($field instanceof Kouchbase_Field_M2O)
            {
                //$this->_related[$name] = $related;
            }
            elseif($field instanceof Kouchbase_Field_O2M)
            {
                foreach ($value as $key => $val)
                {
                    if ($val instanceof Couhana)
                    {
                        $value[$key] = $val->id;
                    }
                }

                $this->_related[$name] = $value;
                return;
            }
            elseif ($field instanceof Sprig_Field_ForeignKey)
            {
                throw new Kouchbase_Exception('Cannot change relationship of :model->:field using __set()',
                        array(':model' => $this->_model, ':field' => $name));
            }
            else
            {
                $value = $field->value($value);
            }

            // update object
            $this->_values[$name] = $value;

        }
        else
        {
            throw new Kouchbase_Exception(':name model does not have a field :field',
                    array(':name' => get_class($this), ':field' => $name));
        }
    }

    /**
     * Unset a field
     *
     * @param   string  field name
     * @return  void
     */
    public function __unset($name)
    {
        if ( ! $this->_init)
        {
            $this->init();
        }

        if ( isset($this->_fields[$name]))
        {
            unset($this->_values[$name]);
        }
        else
        {
            throw new Kouchbase_Exception(':name model does not have a field :field',
                    array(':name' => $this->_model, ':field' => $name));
        }
    }


    /**
     * Initialize the fields and add validation rules based on field properties.
     *
     * @return void
     */
    public function init()
    {
        if(!$this->_init)
        {
            $this->_fields['id'] = new Kouchbase_Field_Integer(array(
                    'label' => 'ID'
            ));

            $this->_init();
            $this->_init = true;
        }

        if (!$this->_model)
        {
            $this->_model = strtolower(substr(get_class($this), 6));
        }

        foreach ($this->_fields as $name => $field)
        {

            if ($field instanceof Kouchbase_Field_ForeignKey AND ! $field->model)
            {
                if ($field instanceof Kouchbase_Field_O2M)
                {
                    $field->model = Inflector::singular($name);
                }
                else
                {
                    $field->model = $name;
                }
            }

            if ( ! $field->label)
            {
                $field->label = Inflector::humanize($name);
            }

            if ($field->null)
            {
                // Fields that allow NULL values must accept empty values
                $field->empty = TRUE;
            }

            if ($field->editable)
            {
                if ( ! $field->empty AND ! isset($field->rules['not_empty']))
                {
                    // This field must not be empty
                    $field->rules[] = array('not_empty');
                }

                if ($field->unique)
                {
                    //Field must be a unique value
                    $field->rules[] = array(
                            array(':model','_is_unique'),
                            array(
                                    ':field',
                                    ':value'
                            )
                    );
                }

                if ( ! empty($field->min_length))
                {
                    $field->rules[] = array(
                            'min_length',
                            array(
                                    ':value',
                                    ':length' => $field->min_length,
                            )
                    );
                }

                if ( ! empty($field->max_length))
                {
                    $field->rules['max_length'] = array(
                            'max_length',
                            array(
                                    ':value',
                                    ':length' => $field->max_length
                            )
                    );
                }
            }

            if ($field instanceof Kouchbase_Field_M2O OR ! $field instanceof Kouchbase_Field_ForeignKey)
            {
                // Set the default value for any field that is stored in the database
                $this->_values[$name] = $field->value($field->default);
            }
            else
            {
                $this->_related[$name] = $field->value($field->default);
            }
        }
    }

    /**
     * Empties the model
     *
     * @return void
     */
    public function clear()
    {
        $this->_values = array();
        $this->_loaded = NULL;

        return $this;
    }



    /**
     * Return field data for a certain field
     *
     * @param   string         field name
     * @return  boolean|array  field data if field exists, otherwise FALSE
     */
    public function field($name)
    {
        return isset($this->_fields[$name])
        ? $this->_fields[$name]
        : FALSE;
    }

    /**
     * Return field data
     *
     * @return  array  field data
     */
    public function fields()
    {
        return $this->_fields;
    }

    /**
     * Load data from database
     * @param int $id
     * @return Couhana
     */
    public function load($id = NULL)
    {
        if(isset($id))
        {
            $this->id = $id;
        }

        if (!isset($this->id))
        {
        	throw new Kouchbase_Exception('Cannot load :model without the id.', array(':model' => $this->_model));
        }

        $values = KouchbaseDB::instance()->get($this->_model . $this->id);
        if(KouchbaseDB::instance()->get_result_code() != COUCHBASE_SUCCESS)
        {
        	throw new Kouchbase_Exception('Unable to load :key.', array(':key' => $this->_model . $this->id));
        }
		$this->load_values($values);

        return $this;
    }

    /**
     *  Save the record to the database.
     *
     * @return void
     */
    public function save()
    {
    	if(!isset($this->id))
    	{
	        // get next_id but do not update it yet
    	    $this->id = $this->get_next_id(false);
    	}

    	$data = $this->check($this->as_array());

        KouchbaseDB::instance()->set($this->_model . $this->id, $this->as_array());
        if(KouchbaseDB::instance()->get_result_code() != COUCHBASE_SUCCESS)
        {
        	throw new Kouchbase_Exception('Unable to save :model.', array(':model' => $this->_model));
        }

        foreach($this->_fields as $name => $value)
        {
        	$field = $this->_fields[$name];
        	if($field instanceof Kouchbase_Field_O2M)
        	{
        		$key = $this->_model . $this->id . '_' . $name;

        		KouchbaseDB::instance()->set($key, $value);
        		if(KouchbaseDB::instance()->get_result_code() != COUCHBASE_SUCCESS)
        		{
        			throw new Kouchbase_Exception('Unable to save :key.', array(':key' => $key));
        		}
        	}
        	elseif($field instanceof Kouchbase_Field_M2O)
        	{
        		// plant -> player
        		// $field->model = player
        		// $value = player id
        		// $this->_model = plant
        		if(is_object($value))
        		{
        			$value = $value->id;
        		}
        		
        		$key = $field->model . $value . '_' . Inflector::plural($this->_model);

        		$data = KouchbaseDB::instance()->get($key);
        		if(!$data)
        		{
        			$data = array();
        		}
        		if(!isset($data[$value]))
        		{
        			$data[] = value;
        		}
        		KouchbaseDB::instance()->set($key, $data);
        		if(KouchbaseDB::instance()->get_result_code() != COUCHBASE_SUCCESS)
        		{
        			throw new Kouchbase_Exception('Unable to save :key.', array(':key' => $key));
        		}

        	}
        }

        // update next_id
        $this->get_next_id();
        $this->increase_total_count();

        return $this;
    }

    /**
     * Delete the current record
     *
     * @return Kouchbase_Core
     */
    public function delete()
    {
        if (!isset($this->id))
        {
        	throw new Kouchbase_Exception('Cannot delete :model without the id.', array(':model' => $this->_model));
        }
        
        foreach($this->fields as $name => $value)
        {
        	$field = $this->_fields[$name];
            if($field instanceof Kouchbase_Field_O2M)
            {
            	$ids = $this->_related[$name];
                foreach($ids as $id)
                {
                	$model = Kouchbase::factory($field->model)->load($id);
                    $foreign_key = isset($field->foreign_key)?$field->foreign_key:$this->_model;

                    if(!$model->field($foreign_key)->empty && !$model->field($foreign_key)->null)
                    {
                    	$model->delete();
                    }
                    else
                    {
                        $model->{$foreign_key} = NULL;
                        $model->save();
                    }
                }
                // remove mapping key
                // player1_plants
                $key = $this->_model . $this-id . '_' . $field->model;
                KouchbaseDB::instance()->delete($key);
            }
            elseif($field instanceof Kouchbase_Field_M2O)
            {
                $model = Kouchbase::factory($field->model)->load($value);
                $model->remove_relation(Inflector::plural($this->model), $this->id);
            }
        }

        $key = $this->_model . $this->id;

        KouchbaseDB::instance()->delete($key);

        if(KouchbaseDB::instance()->get_result_code() != COUCHBASE_SUCCESS)
        {
        	throw new Kouchbase_Exception('Failed to delete :key', array(':key' => $key));
        }

        // if key deleted successfull, decrease total count
        KouchbaseDB::instance()->decrement($this->_model . '_total');

        $this->clear();

        return $this;
    }

    /**
     * Add related value to field
     * @param string $name
     * @param mixed $value
     */
    public function add_relation($name, $value)
    {
        if(!is_array($value))
        {
            $value = array($value);
        }

        $field = $this->_fields[$name];

        if(!$field instanceof Kouchbase_Field_O2M)
        {
            throw new Kouchbase_Exception("Unable to add relationship for field type :type", array(':type' => get_class($field)));
        }
        if($field instanceof Kouchbase_Field_M2M)
        {
            throw new Kouchbase_Exception('M2M is not supported yet');
        }
        foreach($value as $val)
        {
            if(!$val instanceof Kouchbase)
            {
                $val = Kouchbase::factory($field->model)->load($val);
            }
            $this->_related[$name][] = $val->id;

            /*
            $foreign_key = isset($field->foreign_key)?$field->foreign_key:$this->_model;

            // update related object's foreign_key to current object' id
            $val->{$field->foreign_key} = $this->id;
            */
        }

        return $this;
    }


    /**
     * Remove related value field
     * @param string $name
     * @param mixed $value
     */
    public function remove_relation($name, $value)
    {
        if(!is_array($value))
        {
            $value = array($value);
        }

        $field = $this->_fields[$name];

        if(!$field instanceof Kouchbase_Field_O2M)
        {
            throw new Kouchbase_Exception("Unable to remove relationship for field type :type", array(':type' => get_class($field)));
        }
        if($field instanceof Kouchbase_Field_M2M)
        {
            throw new Kouchbase_Exception('M2M is not supported yet');
        }
        $temp = array();
        foreach($value as $val)
        {
            if(!$val instanceof Kouchbase)
            {
                $val = Kouchbase::factory($field->model)->load($val);
            }
            /*
            $foreign_key = isset($field->foreign_key)?$field->foreign_key:$this->_model;

            if(!$val->field($foreign_key)->empty && !$val->field($foreign_key)->null)
            {
                throw new Kouchbase_Exception('Foreign key :field of :model cannot be null', array(
                	':field' => $foreign_key,
                	':model' => $val->_model
                ));
            }
            else
            {
                $val->$foreign_key = NULL;
                $val->save();
            }
            */
            $temp[] = $val->id;
        }
        $this->_related[$name] = array_diff($this->_related[$name], $temp);

        return $this;
    }

    /**
     * Check the given data is valid. Only values that have editable fields
     * will be included and checked.
     *
     * @throws  Validate_Exception  when an error is found
     * @param   array  data to check, field => value
     * @return  array  filtered data
     */
    public function check(array $data = NULL)
    {
        $data = Validation::factory($data)->bind(':model', $this);

        foreach ($this->_fields as $name => $field)
        {
            $data->label($name, $field->label);

            if ($field->filters)
            {
                $data->filters($name, $field->filters);
            }

            if ($field->rules)
            {
                $data->rules($name, $field->rules);
            }
        }

        if ( ! $data->check())
        {
            throw new Kouchbase_Validation_Exception($this->_model, $data);
        }

        return $data->as_array();

    }

    /**
     * Test if the model is loaded.
     *
     * @return  boolean
     */
    public function loaded()
    {
	    if($this->_loaded === NULL)
	    {
	      $this->load();
	    }
	    return $this->_loaded;
    }


    /**
     * Load all of the values in an associative array. Ignores all fields are
     * not in the model.
     *
     * @param   array    field => value pairs
     * @return  $this
     */
    public function load_values(array $values)
    {
        // Remove all values which do not have a corresponding field
        $values = array_intersect_key($values, $this->_fields);
        foreach ($values as $name => $value)
        {
            //$field = $this->_fields[$name];
        	//TODO: load related field
        	if($field instanceof Kouchbase_Field_O2M)
        	{
        	    //$key = $this->_model . $this->id . '_' . Inflector::plural($field->model);
        	    $value = isset($value)?$value:(isset($field->default)?$field->default:array());
        	    $this->_related[$name] = $value;
        	}
        	else
        	{
            	$this->$name = $value;
        	}
            //$this->$name = $value;
        }
        $this->_loaded = !empty($this->_values);

        return $this;
    }

    /**
     * Get the model data as an associative array.
     *
     */
    public function as_array()
    {
        return $this->_values;
    }

    /**
     * Get next ID for current model
     *
     * @param boolean If true this method will update new ID
     * @return int
     */
    protected function get_next_id($update = true)
    {
        // store incremental id
        $next = KouchbaseDB::instance()->get($this->_model . '_next');
        if(!$next)
        {
            $next = 1;
            if($update)
            {
                KouchbaseDB::instance()->set($this->_model . '_next', 2);
            }
        }
        else
        {
            if($update)
            {
                KouchbaseDB::instance()->increment($this->_model . '_next');
            }
        }
        return $next;
    }

    protected function increase_total_count()
    {
        $total = KouchbaseDB::instance()->get($this->_model . '_total');
        if(!$total)
        {
            KouchbaseDB::instance()->set($this->_model . '_total', 1);
        }
        else
        {
            KouchbaseDB::instance()->increment($this->_model . '_total');
        }
    }

    protected function decrease_total_count()
    {
        $total = KouchbaseDB::instance()->get($this->_model . '_total');
        if(!$total)
        {
            KouchbaseDB::instance()->set($this->_model . '_total', 0);
        }
        else
        {
            KouchbaseDB::instance()->decrement($this->_model . '_total');
        }
    }


    /**
     * Initialize the fields. This method will only be called once
     * by Sprig::init(). All models must define this method!
     *
     * @return  void
     */
    abstract protected function _init();

    public static function _is_unique($field, $value) {}
}