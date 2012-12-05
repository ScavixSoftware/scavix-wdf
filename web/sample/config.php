<?

// Defaults
$CONFIG['system']['default_page']  = "Sample";
$CONFIG['system']['default_event'] = "Index";

// Classpath
$CONFIG['class_path']['order']    = array('system','model','content');
$CONFIG['class_path']['content'][] = 'controller/';
$CONFIG['class_path']['model'][]  = 'model/';

// Database
$CONFIG['model']['system']['connection_string'] = "sqlite:sample.db";

// Logger Config
ini_set("error_log", dirname(__FILE__).'/log/fallback_error.log');
$CONFIG['system']['logging'] = array
(
	'human_readable' => array
	(
		'path' => dirname(__FILE__).'/log/',
		'filename_pattern' => 'php_error.log',
		'log_severity' => true,
		'max_filesize' => 10*1024*1024,
		'keep_for_days' => 5,
		'max_trace_depth' => 16,
	),
	'full_trace' => array
	(
		'class' => 'TraceLogger',
		'path' => dirname(__FILE__).'/log/',
		'filename_pattern' => 'php_error.trace',
		'log_severity' => true,
		'max_trace_depth' => 10,
		'max_filesize' => 10*1024*1024,
		'keep_for_days' => 4,
	),
);

// Resources config
$CONFIG['resources'][] = array
(
	'ext' => 'js|css|png|jpg|jpeg|gif|htc|ico',
	'path' => realpath(__DIR__.'/res/'),
	'url' => 'res/',
	'append_nc' => false,
);

// some essentials
$CONFIG['system']['modules'] = array();
date_default_timezone_set("Europe/Berlin");