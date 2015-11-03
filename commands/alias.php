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
					if (!isset($cli['arguments'][1])) {
						echo 'Insufficient arguments'."\n";
						$this->showUsage();
						die();
					}

					$this->remove($cli['arguments'][1], isset($cli['arguments'][2]) ? $cli['arguments'][2] : false);
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

		// Add a new mailbox to the alias
		$node = $this->db->table('alias')->getOneBy('address', $address);
		if ($node) {
			$goto = explode(',', $node['goto']);
			if (in_array($mailbox, $goto)) {
				echo 'Alias already exists: '.$address.' -> '.$mailbox."\n";
				return;
			}
			$goto[] = $mailbox;
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

	public function remove($address, $mailbox=false) {
		// Check if the alias exists
		$node = $this->db->table('alias')->getOneBy('address', $address);
		if (!$node)
			throw new \Exception('Alias does not exist: '.$address);

		if ($node['accesspolicy'] != 'public')
			throw new \Exception('Alias can\'t be removed (accesspolicy not public)');

		if ($mailbox)
			$goto = $this->cleanGoto($node['goto'], $mailbox);

		if (!$mailbox || !$goto) {
			$this->db->table('alias')->removeBy('address', $address);
			return;
		}

		$this->db->table('alias')->updateBy('address', $address, [
			'goto'     => $goto,
			'modified' => date('Y-m-d H:i:s')
		]);
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

	public function cleanGoto($goto, $mailbox) {
		$goto = explode(',', $goto);

		if (($key = array_search($mailbox, $goto)) !== false)
			unset($goto[$key]);

		return sizeof($goto) > 0 ? implode(',', $goto) : false;
	}
}
