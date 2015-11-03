<?php

class Helper {
	public function __construct($cli) {
		$this->db = new MicroDB\MySQL(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		$this->init($cli);
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
