<?php

class AliasCommand extends Helper {
	public function cli($cli) {
		if (!isset($cli['arguments'][0])) {
			echo 'No operation specified!'."\n";
			$this->showUsage();
			die();
		}

		try {
			switch ($cli['arguments'][0]) {
				case 'remove':
					if (!isset($cli['arguments'][1])) {
						echo 'Insufficient arguments'."\n";
						$this->showUsage();
						die();
					}

					$this->remove(
						$cli['arguments'][1],
						isset($cli['arguments'][2]) ? $cli['arguments'][2] : false,
						isset($cli['options']['search']) ? $cli['options']['search'] : false
					);
					echo 'OK'."\n";
					break;

				case 'add':
					if (!isset($cli['arguments'][1]) || !isset($cli['arguments'][2])) {
						echo 'Insufficient arguments'."\n";
						$this->showUsage();
						die();
					}

					$this->add($cli['arguments'][1], $cli['arguments'][2]);
					echo 'OK'."\n";
					break;

				case 'show':
					$this->renderTable(
						$this->show(
							isset($cli['arguments'][1]) ? $cli['arguments'][1] : false,
							isset($cli['options']['search']) ? $cli['options']['search'] : false
						),
						['address', 'goto', 'domain']
					);
					break;

				case 'export':
					echo json_encode(
						$this->show(
							isset($cli['arguments'][1]) ? $cli['arguments'][1] : false,
							isset($cli['options']['search']) ? $cli['options']['search'] : false
						),
						JSON_PRETTY_PRINT
					);
					break;

				case 'import':
					if (!isset($cli['arguments'][1])) {
						echo 'Insufficient arguments: filename required'."\n";
						$this->showUsage();
						die();
					}

					$this->import($cli['arguments'][1]);
					break;

				default:
					echo 'Invalid arguement: '.$cli['arguments'][0]."\n";
					$this->showUsage();
					break;
			}
		} catch (Exception $e) {
			echo 'ERROR: '.$e->getMessage()."\n";
			return;
		}
	}

	public function rest($method, $path=array()) {
		switch ($method) {
			case 'GET':
				// Show the alias list
				if (!$path)
					return $this->show(false, isset($_REQUEST['search']) ? $_REQUEST['search'] : false);

				if (is_array($path) && sizeof($path) == 1)
					return $this->show($path[0], isset($_REQUEST['search']) ? $_REQUEST['search'] : false);

				break;
			case 'POST':
				// Create an alias
				if (!is_array($path) || sizeof($path) != 2)
					throw new Exception('Invalid request: Usage: POST:/alias/<ALIAS>/<MAILBOX>');

				return $this->add(array_shift($path), array_shift($path));
				break;
			case 'DELETE':
				// Remove an alias
				if (!is_array($path) || (sizeof($path) != 1 && sizeof($path) != 2))
					throw new Exception('Invalid request: Usage: DELETE:/alias/<ALIAS>/<MAILBOX>');
					
				return $this->remove(array_shift($path), array_shift($path));
				break;
		}

		throw new Exception('404 - Not found');
	}

	public function getRoutes() {
		return [
			'GET' => [
				'/alias/[?search=<STRING>]',
				'/alias/:DOMAIN[?search=<STRING>]'
			],
			'POST' => [
				'/alias/:ALIAS/:MAILBOX'
			],
			'DELETE' => [
				'/alias/:ALIAS',
				'/alias/:ALIAS/:MAILBOX'
			]
		];
	}

	public function showUsage() {
		echo 'Usage: iredcli alias'."\n";
		echo '  show [<DOMAIN|EMAIL> --search=<SEARCH>]'."\n";
		echo '  add <ALIAS> <MAILBOX>'."\n";
		echo '  remove <ALIAS> [<MAILBOX> --search=<SEARCH>]'."\n";
		echo '  export [<DOMAIN|EMAIL> --search=<SEARCH>]'."\n";
		echo '  import <FILENAME>'."\n";
	}

	public function add($address, $mailbox) {
		// Check email format
		if (!filter_var($address, FILTER_VALIDATE_EMAIL))
			throw new \Exception('Not a valid e-mail: '.$address);

		// Check if the domain exists
		$domain = explode('@', $address);
		$domain = array_pop($domain);
		$node = $this->db->table('domain')->getOneBy('domain', $domain);
		if (!$node)
			throw new \Exception('Domain does not exist: '.$domain);

		// Check if the mailbox exists
		if (strpos($mailbox, ',')) {
			$mailboxList = preg_split('/\s?+[,;]\s?+/', $mailbox);
		} else {
			$mailboxList = [$mailbox];
		}
		/*
		foreach ($mailboxList as $mailbox) {
			$node = $this->db->table('mailbox')->getOneBy('username', $mailbox);
			if (!$node)
				throw new \Exception('Mailbox does not exist: '.$mailbox);
		}
		*/

		// Add a new mailbox to the alias
		$node = $this->db->table('alias')->getOneBy('address', $address);
		if ($node) {
			$goto = explode(',', $node['goto']);
			foreach ($mailboxList as $mailbox) {
				if (in_array($mailbox, $goto)) {
					echo 'Alias already exists: ' . $address . ' -> ' . $mailbox . "\n";
					continue;
				}
				$goto[] = $mailbox;
			}

			$goto = implode(',', $goto);

			$this->db->table('alias')->updateBy('address', $address, [
				'goto'     => $goto,
				'modified' => date('Y-m-d H:i:s')
			]);
			return;
		}

		// Add a new alias
		$this->db->table('alias')->insert([
			'address'      => $address,
			'goto'         => $mailbox,
			'name'         => '',
			'accesspolicy' => 'public',
			'domain'       => $domain,
			'created'      => date('Y-m-d H:i:s'),
			'modified'     => date('Y-m-d H:i:s'),
			// active: 1
			// expired: 9999-12-31 00:00:00
		]);
	}

	public function remove($address, $mailbox=false, $search=false) {
		// Check if the alias exists
		$node = $this->db->table('alias')->getOneBy('address', $address);
		if (!$node && !$search)
			throw new \Exception('Alias does not exist: '.$address);

		if ($search) {
			foreach ($this->show($address, $search) as $alias)
				$this->remove($alias['address'], $mailbox);
			return;
		}

		if ($node['accesspolicy'] != 'public')
			throw new \Exception('Alias can\'t be removed (accesspolicy not public)');

		if ($mailbox)
			$goto = $this->cleanGoto($node['goto'], $mailbox);

		if (!$mailbox || !$goto) {
			$this->db->table('alias')->removeBy('address', $address);
			echo 'Deleting alias: '.$address.($mailbox ? ' -> '.$mailbox : '')."\n";
			return;
		}

		$this->db->table('alias')->updateBy('address', $address, [
			'goto'     => $goto,
			'modified' => date('Y-m-d H:i:s')
		]);

		echo 'Removing alias: '.$address.($mailbox ? ' -> '.$mailbox : '')."\n";
	}

	public function show($domainOrEmail=false, $search=false) {
		if ($domainOrEmail) {
			return $this->db->select(
				'*',
				'alias',
				$this->db->where('accesspolicy', 'public') .
				' AND ' .
				$this->db->whereLike(strpos($domainOrEmail, '@') === false ? 'domain' : 'goto', '%'.$domainOrEmail.'%') .
				($search
					? ' AND ' . $this->db->whereLike('address', '%' . $search . '%')
					: ''
				),
				'address');
		}
		return $this->db->select(
			'*',
			'alias',
				$this->db->where('accesspolicy', 'public').
				($search
					? ' AND '.$this->db->whereLike('address', '%'.$search.'%')
					: ''),
			'domain');
	}

	public function import($filename) {
		$data = json_decode(file_get_contents($filename), true);
		if (!$data)
			throw new \Exception('No data to import');

		foreach ($data as $row) {
			if (
				(isset($row['accesspolicy']) && $row['accesspolicy'] != 'public')
				|| !isset($row['address'])
				|| !isset($row['goto'])
				|| $row['goto'] == ''
			)
				continue;
			try {
				$this->add($row['address'], $row['goto']);
			} catch (Exception $e) {
				echo 'ERROR: '.$e->getMessage()."\n";
			}
		}
	}

	public function cleanGoto($goto, $mailbox) {
		$goto = preg_split('/\s?+[,;]\s?+/', $goto);

		if (($key = array_search($mailbox, $goto)) !== false)
			unset($goto[$key]);

		return sizeof($goto) > 0 ? implode(',', $goto) : false;
	}
}
