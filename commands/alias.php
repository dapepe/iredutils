<?php

class AliasCommand extends Helper {
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
					if (!isset($cli['arguments'][1]) && !isset($cli['arguments'][2])) {
						echo 'Insufficient arguments'."\n";
						$this->showUsage();
						die();
					}

					if ($cli['arguments'][0] == 'add')
						$this->add($cli['arguments'][1], $cli['arguments'][2]);
					else
						$this->remove($cli['arguments'][1], $cli['arguments'][2]);

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
		echo 'Usage: iredcli alias'."\n";
		echo '  show [<DOMAIN|EMAIL> --search=<SEARCH>]'."\n";
		echo '  add <ALIAS> <MAILBOX>'."\n";
		echo '  remove <ALIAS> [<MAILBOX>]'."\n";
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
		$node = $this->db->table('mailbox')->getOneBy('username', $mailbox);
		if (!$node)
			throw new \Exception('Mailbox does not exist: '.$mailbox);

		// Check if the alias exists
		$node = $this->getEntry($address, $mailbox);
		if ($node)
			throw new \Exception('Alias already exists: '.$address.' -> '.$mailbox);

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

	public function remove($address, $mailbox) {
		// Check if the alias exists
		$node = $this->getEntry($address, $mailbox);
		if (!$node)
			throw new \Exception('Alias does not exist: '.$address.' -> '.$mailbox);

		if ($node['accesspolicy'] != 'public')
			throw new \Exception('Alias can\'t be removed (accesspolicy not public)');

		$this->db->remove('alias', $this->db->where('address', $address).' AND '.$this->db->where('goto', $mailbox));
	}

	public function show($domainOrEmail=false, $search=false) {

		if ($domainOrEmail) {
			return $this->db->select(
				'*',
				'alias',
				$this->db->where('accesspolicy', 'public') .
				' AND ' .
				$this->db->where(strpos($domainOrEmail, '@') === false ? 'domain' : 'goto', $domainOrEmail) .
				($search
					? ' AND ' . $this->db->whereLike('address', '%' . $search . '%')
					: ''
				),
				'domain');
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

	public function getEntry($address, $mailbox) {
		$arrItem = $this->db->select('*', 'alias', $this->db->where('address', $address).' AND '.$this->db->where('goto', $mailbox));
		if ($arrItem)
			return $arrItem[0];
		else
			return false;
	}
}
