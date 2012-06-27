<?php defined('SYSPATH') or die('No direct script access.');


class Kouchbase_DB {

	/**
	 * @var  string  default instance name
	 */
	public static $default = 'development';

	/**
	 * @var  array  Database instances
	 */
	public static $instances = array();

	// Instance name
	protected $_name;

	// Connected
	protected $_connected = FALSE;

	// Raw server connection
	protected $_connection;

	// Store config locally
	protected $_config;

	/**
	 * Return KouchbaseDB instance
	 * @param string $name
	 * @param array $config
	 *
	 * @return KouchbaseDB
	 */
	public static function instance($name = NULL, array $config = NULL)
	{
		if ($name === NULL)
		{
			// Use the default instance name
			$name = KouchbaseDB::$default;
		}

		if ( ! isset(KouchbaseDB::$instances[$name]))
		{
			if ($config === NULL)
			{
				// Load the configuration for this database
				$config = Kohana::$config->load('kouchbase')->$name;
			}

			new KouchbaseDB($name,$config);
		}

		return self::$instances[$name];
	}

	/**
	 * KouchbaseDB
	 * @param unknown_type $name
	 * @param array $config
	 */
	protected function __construct($name, array $config)
	{
		$this->_name = $name;

		$this->_config = $config;

		// Store the database instance
		KouchbaseDB::$instances[$name] = $this;
	}

	final public function __toString()
	{
		return $this->_name;
	}

	public function connect()
	{
		if ( $this->_connection)
		{
			return;
		}

		// Extract the connection parameters, adding required variables
		extract($this->_config);

		if ( ! isset($options))
		{
			$options = array();
		}

		try
		{
			// Create connection object
			$this->_connection = new Couchbase($hostname, $username, $password, $bucket);
		}
		catch (Exception $e)
		{
			// Unable to connect to the database server
			throw new Kohana_Exception('Unable to connect to Couchbase server at :hostname. Error: :error',
				array(':hostname' => $hostname, ':error' => $e->getMessage()));
		}
		return $this->_connected = TRUE;
	}


	protected function add($key, $value, $expire = null)
	{
		return $this->_call('add', array($key, $value, $expire));

	}

	public function replace($key, $value, $expire = null)
	{
		return $this->_call('replace', array($key, $value, $expire));
	}

	public function set($key, $value, $expire = null)
	{
		return $this->_call('set', array($key, $value, $expire));
	}


	public function get($key)
	{
		return $this->_call('get', array($key));
	}

	public function get_result_code()
	{
		return $this->_call('getResultCode');
	}

	public function append($key, $value)
	{
		return $this->_call('append', array($key, $value));
	}

	public function decrement($key, $offset = 1)
	{
		return $this->_call('decrement', array($key, $offset));
	}

	public function delete($key)
	{
		return $this->_call('delete', array($key));
	}

	public function flush()
	{
		return $this->_call('flush');
	}

	public function increment($key, $offset = 1)
	{
		return $this->_call('increment', array($key, $offset));
	}

	public function prepend($key, $value)
	{
		return $this->_call('prepend', array($key, $value));
	}

	public function touch($key, $expiry)
	{
		return $this->_call('key', array($key, $expiry));
	}

	protected function _call($method, array $args = array())
	{
		$this->_connected OR $this->connect();
		try
		{
			return call_user_func_array(array($this->_connection, $method), $args);
		}
		catch(Exception $e)
		{
			throw new Kohana_Exception('Method call error: :error',
				array(':error' => $e->getMessage()));
		}

	}
}