<?php

class Helper {
	public function __construct($cli=false) {
		$this->init();
		if ($cli)
			$this->cli($cli);
	}

	public function init() {
		$this->updateConnection(DB_VMAIL_HOST, DB_VMAIL_USER, DB_VMAIL_PASSWORD, DB_VMAIL_NAME);
	}

	public function updateConnection($host, $user, $password, $db) {
		$this->db = new MicroDB\MySQL($host, $user, $password, $db);
	}

	public function renderTable($data, $header = false) {
		if (!is_array($data) || !isset($data[0]))
			return;

		if ($header == false)
			$header = array_keys($data[0]);
		
		$rows = [];
		foreach ($data as $row) {
			$item = [];
			foreach ($header as $col) {
				$item[] = isset($row[$col]) ? $row[$col] : '';
			}
			$rows[] = $item;
		}

		$table = new \cli\Table($header, $rows);
		$table->display();
	}
}
