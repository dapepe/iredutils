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
		echo '  list [<DOMAIN> --search=<SEARCH>]'."\n";
		echo '  add <ALIAS> <MAILBOX>'."\n";
		echo '  remove <ALIAS> [<MAILBOX>]'."\n";
	}

	public function add($address, $mailbox) {
		// Check if the mailbox exists
		$node = $this->db->select('*', 'alias', $this->db->where('address', $address).' AND '.$this->db->where('goto', $mailbox))
		if (!$node)
			throw new \Exception('Mailbox does not exist: '.$email);
	}

	public function remove($address, $mailbox) {
		$node = $this->db->table('domain')->getOneBy('domain', $domain);

		if ($node === false)
			throw new \Exception('Domain not found: '.$domain);

		$this->db->table('domain')->removeBy('domain', $domain);
	}

	public function show($domain=false, $search=false) {
		return $this->db->select('*', 'alias', false, 'address');
		if ($domain)
			return $this->db->select('*', 'alias', $this->db->where('domain', $domain).($search ? ' AND '.$this->db->whereLike('address', '%'.$search.'%') : ''), 'domain');
	}
}
