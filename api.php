<?php

include 'bootstrap.php';

try {

	$parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
	$commandName = array_shift($parts);
	$commandClass = ucfirst($commandName).'Command';
	if (!file_exists(CMD_DIR.$commandName.'.php')) {
		throw new Exception('Command "'.$commandName.'" not found!');
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

	$cmd = new $commandClass();
	$res = ['result' => $cmd->rest($_SERVER['REQUEST_METHOD'], $parts)];

} catch (Exception $e) {

	$res = ['error' => $e->getMessage()];

}

header('Content-type: application/json');
echo json_encode($res);
