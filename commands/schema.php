<?php

class SchemaCommand {
	public function __construct($cli) {
		if (!isset($cli['arguments'][0])) {
			echo 'No database specified!'."\n";
			echo 'Usage: cli.sh schema <DATABASE> [<FILENAME>]';
			die();
		}
			
		$db = new MicroDB\MySQL(DB_HOST, DB_USER, DB_PASSWORD, $cli['arguments'][0]);
		$schema = '';

		foreach ($db->tables() as $tableId) {
			$schema .= $tableId."\n";

			$table = $db->table($tableId);
			foreach ($table->fields() as $fieldId) {
				$schema .= '  '.$fieldId."\n";
			}

			$schema .= "\n";
		}

		if (isset($cli['arguments'][1])) {
			file_put_contents($cli['arguments'][1], $schema);
			die('File written: '.$cli['arguments'][1]);
		}
		
		echo $schema;
	}
}
