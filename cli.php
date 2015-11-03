<?php

/**
 * ZeyOS documentation generator
 *
 * @author Peter Haider <peter.haider@zeyos.com>
 * @version 1.0
 * @copyright Copyright 2014, Zeyon Technologies Inc.
 */


/* Initialize includes
--------------------------------------------------- */
define('BASE_DIR'    , dirname(__FILE__).DIRECTORY_SEPARATOR);
define('DOCS_DIR'    , BASE_DIR.'docs'.DIRECTORY_SEPARATOR);
define('VENDOR_DIR'  , BASE_DIR.'vendor'.DIRECTORY_SEPARATOR);
define('CMD_DIR'     , BASE_DIR.'commands'.DIRECTORY_SEPARATOR);
define('BIN_DIR'     , BASE_DIR.'bin'.DIRECTORY_SEPARATOR);
define('TEMPLATE_DIR', BASE_DIR.'template'.DIRECTORY_SEPARATOR);
define('TEMP_DIR'    , BASE_DIR.'temp'.DIRECTORY_SEPARATOR);
define('DIST_DIR'    , BASE_DIR.'dist'.DIRECTORY_SEPARATOR);
define('LIB_DIR'     , BASE_DIR.'lib'.DIRECTORY_SEPARATOR);
define('RES_DIR'     , BASE_DIR.'res'.DIRECTORY_SEPARATOR);

set_include_path(
	LIB_DIR.PATH_SEPARATOR.
	VENDOR_DIR.PATH_SEPARATOR.
	BASE_DIR.PATH_SEPARATOR
);

/* Include libraries and config
--------------------------------------------------- */
include 'autoload.php';
include 'config.php';
include 'helper.php';
include 'command.php';

/* Initialize settings
--------------------------------------------------- */
if (!defined('DB_HOST'))
	define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME'))
	define('DB_NAME', 'vmail');
if (!defined('DB_USER'))
	define('DB_USER', 'root');
if (!defined('DB_PASSWORD'))
	define('DB_PASSWORD', '');
if (!defined('CMD_PYTHON'))
	define('CMD_PYTHON', 'python');
if (!defined('STORAGE_DIR'))
	define('STORAGE_DIR', '/var/vmail');
if (!defined('STORAGE_NODE'))
	define('STORAGE_NODE', 'vmail1');
if (!defined('STORAGE_USER'))
	define('STORAGE_USER', 'vmail');
if (!defined('STORAGE_GROUP'))
	define('STORAGE_GROUP', 'vmail');

/* Get the CLI arguements
--------------------------------------------------- */
function CLIarguments($args) {
    $ret = array(
        'exec'      => '',
        'options'   => array(),
        'flags'     => array(),
        'arguments' => array()
    );

    $ret['exec'] = array_shift($args);

    while (($arg = array_shift($args)) != NULL) {
        // Is it a option? (prefixed with --)
        if ( substr($arg, 0, 2) === '--' ) {
            $option = substr($arg, 2);

            // is it the syntax '--option=argument'?
            if (strpos($option,'=') !== FALSE)
                array_push( $ret['options'], explode('=', $option, 2) );
            else
                array_push( $ret['options'], $option );

            continue;
        }

        // Is it a flag or a serial of flags? (prefixed with -)
        if ( substr( $arg, 0, 1 ) === '-' ) {
            for ($i = 1; isset($arg[$i]) ; $i++)
                $ret['flags'][] = $arg[$i];

            continue;
        }

        // finally, it is not option, nor flag
        $ret['arguments'][] = $arg;
        continue;
    }
    return $ret;
}

try {
	$cli = CLIarguments($argv);

	if (!$cli['arguments']) {
		echo 'No command specified! Usage:'."\n\n".'  iredcli {command} [arguments]'."\n\n".'Commands:'."\n";
		foreach (scandir(CMD_DIR) as $file) {
			if (preg_match('/.php$/', $file))
				echo "\n".'  -> '.basename($file, '.php');
		}
		die("\n\n");
	}

	$commandName = array_shift($cli['arguments']);
	$commandClass = ucfirst($commandName).'Command';
	if (!file_exists(CMD_DIR.$commandName.'.php')) {
		echo 'Command "'.$commandName.'" not found!'."\n";
		die();
	}

	include_once(CMD_DIR.$commandName.'.php');

	if (!class_exists($commandClass)) {
		echo 'Command class "'.$commandClass.'" not found!'."\n";
		die();
	}

	$options = array();
	foreach ($cli['options'] as $opt) {
		if (is_array($opt))
			$options[$opt[0]] = $opt[1];
		else {
			if (substr($opt, 0, 3) == 'no-')
				$options[substr($opt, 3)] = false;
			else
				$options[$opt] = true;
		}
	}
	$cli['options'] = $options;

	$cmd = new $commandClass($cli);
} catch (Exception $e) {
	echo 'ERROR: '.$e->getMessage().' in '.$e->getFile().' Line '.$e->getLine()."\n";
	echo 'TRACE:'."\n".$e->getTraceAsString()."\n";
}
