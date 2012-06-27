<?php defined('SYSPATH') or die('No direct access allowed.');

return array
(
	'development' => array
	(
		'hostname'   => 'localhost:8091',
		'bucket'     => 'default',
		'username'   => 'Administrator',
		'password'   => '123456',
		'options'    => array
		(
		    'persist'   => true,
		)
	),
	'production' => array
	(
		'hostname'   => 'localhost:8091',
		'bucket'   => 'default',
		'username'   => FALSE,
		'password'   => FALSE,
		'options'    => array
		(
		    'persist'   => true,
		)
	),
);