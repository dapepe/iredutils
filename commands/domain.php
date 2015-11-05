<?php

class DomainCommand extends Helper {
	public function init($cli) {
		if (!isset($cli['arguments'][0])) {
			echo 'No operation specified!'."\n";
			$this->showUsage();
			die();
		}

		try {
			switch ($cli['arguments'][0]) {
				case 'remove':
				case 'add':
					if (!isset($cli['arguments'][1])) {
						echo 'Insufficient arguments'."\n";
						$this->showUsage();
						die();
					}
					if ($cli['arguments'][0] == 'add')
						$this->add($cli['arguments'][1]);
					else
						$this->remove($cli['arguments'][1]);

					echo 'OK'."\n";
					break;

				case 'show':
					$this->renderTable($this->show(), ['domain', 'created']);
					break;

				case 'export':
					echo json_encode($this->show(), JSON_PRETTY_PRINT);
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

	public function showUsage() {
		echo 'Usage: iredcli domain'."\n";
		echo '  show'."\n";
		echo '  add <DOMAIN>'."\n";
		echo '  remove <DOMAIN>'."\n";
		echo '  export'."\n";
		echo '  import <FILENAME>'."\n";
	}

	public function add($domain) {
		if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9.-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $domain))
			throw new \Exception('Not a valid domain name: '.$domain);

		$node = $this->db->table('domain')->getOneBy('domain', $domain);
		if ($node)
			throw new \Exception('Domain already exists: '.$domain);

		$this->db->table('domain')->insert([
			'domain' => $domain,
			'created' => date('Y-m-d H:i:s'),
			'modified' => date('Y-m-d H:i:s')
		]);
	}

	public function remove($domain) {
		$node = $this->db->table('domain')->getOneBy('domain', $domain);
		if ($node === false)
			throw new \Exception('Domain not found: '.$domain);

		$this->db->table('domain')->removeBy('domain', $domain);

		// Remove all mailboxes
		include_once 'mailbox.php';
		$mailbox = new MailboxCommand();
		foreach ($mailbox->show($domain) as $node)
			$mailbox->remove($node['username']);

		// Remove all aliases
		include_once 'alias.php';
		$alias = new AliasCommand();
		foreach ($alias->show($domain) as $node)
			$alias->remove($node['address']);
	}

	public function show() {
		return $this->db->select('*', 'domain', false, 'domain');
	}

	public function import($filename) {
		$data = json_decode(file_get_contents($filename), true);
		if (!$data)
			throw new \Exception('No data to import');

		foreach ($data as $row)
			$this->add(is_string($row) ? $row : $row['domain']);
	}
}
