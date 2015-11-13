<?php

// Obsolete: Has been used to compare schemas across different versions

class SchemaCommand {
	public function __construct($cli) {
		if (!isset($cli['arguments'][0])) {
			echo 'No database specified!'."\n";
			echo 'Usage: cli.sh schema <DATABASE> [<FILENAME>]';
			die();
		}
			
		$db = new MicroDB\MySQL(DB_VMAIL_HOST, DB_VMAIL_USER, DB_VMAIL_PASSWORD, $cli['arguments'][0]);
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
