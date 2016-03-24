<?php

/**
 * iRedUtils CLI Script
 *
 * @author Peter Haider <peter.haider@zeyos.com>
 * @version 1.0
 * @copyright Copyright 2014, Z
 */


include 'bootstrap.php';

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
