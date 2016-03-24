<?php

class PolicyCommand extends Helper {
	public function cli($cli) {
		if (!isset($cli['arguments'][0])) {
			echo 'No operation specified!'."\n";
			$this->showUsage();
			die();
		}

		try {
			switch ($cli['arguments'][0]) {
				case 'remove':
					if (!isset($cli['arguments'][1]) && !isset($cli['arguments'][2])) {
						echo 'Insufficient arguments'."\n";
						$this->showUsage();
						die();
					}

					$this->remove($cli['arguments'][1], $cli['arguments'][2]);
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
						)
					);
					break;

				case 'learn':
					if (!isset($cli['arguments'][1]) && !isset($cli['arguments'][2])) {
						echo 'Insufficient arguments'."\n";
						$this->showUsage();
						die();
					}

					$this->learn($cli['arguments'][1], $cli['arguments'][2]);
					echo 'OK'."\n";
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

	public function init() {
		$this->updateConnection(DB_CLUEBRINGER_HOST, DB_CLUEBRINGER_USER, DB_CLUEBRINGER_PASSWORD, DB_CLUEBRINGER_NAME);
	}

	public function rest($method, $path=array()) {
		switch ($method) {
			case 'GET':
				// Show the policy list
				if (!$path)
					return $this->show(false, isset($_REQUEST['search']) ? $_REQUEST['search'] : false);

				if (is_array($path) && sizeof($path) == 1)
					return $this->show($path[0], isset($_REQUEST['search']) ? $_REQUEST['search'] : false);

				break;
			case 'POST':
				// Add an entry to the learning list
				if (is_array($path) && sizeof($path) == 1) {
					$learn = strtolower(array_shift($path));
					$mailbody = file_get_contents('php://input');
					if ($mailbody == '')
						throw new \Exception('Empty body! Please send a e-mail message!');
						
					$tmp = new \TempFile();
					file_put_contents($tmp->getPath(), $mailbody);
					return $this->learn($learn, $tmp->getPath());
				}

				// Create a policy
				if (is_array($path) && sizeof($path) == 2)
					return $this->add(array_shift($path), array_shift($path));

				break;
			case 'PUT':
				break;
			case 'DELETE':
				// Remove a policy
				if (!is_array($path) || sizeof($path) != 2)
					throw new Exception('Invalid request: Usage: DELETE:/policy/<POLICYGROUP>/<MEMBER>');

				return $this->remove(array_shift($path), array_shift($path));
				break;
		}

		throw new Exception('404 - Not found');
	}

	public function getRoutes() {
		return [
			'GET' => [
				'/policy/[?search=<STRING>]',
				'/policy/<POLICYGROUP>[?search=<STRING>]'
			],
			'POST' => [
				'/policy/:LEARNSUBJECT',
				'/policy/:POLICYGROUP/:MEMBER'
			],
			'DELETE' => [
				'/policy/:POLICYGROUP/:MEMBER'
			]
		];
	}

	public function showUsage() {
		echo 'Usage: iredcli policy'."\n";
		echo '  show [<POLICYGROUP> --search=<SEARCH>]'."\n";
		echo '  add <POLICYGROUP> <MEMBER>'."\n";
		echo '  remove <POLICYGROUP> <MEMBER>'."\n";
		echo '  learn <ham|spam|forget> <FILE>'."\n";
	}

	public function add($group, $member) {
		// Get the policy group
		$node = $this->db->table('policy_groups')->getOneBy('Name', $group);
		if (!$node)
			throw new \Exception('Invalid group: '.$group);

		$check = $this->getMember($group, $member);
		if ($check) {
			echo 'Member '.$member.' already exists in '.$group."\n";
			return;
		}

		// Add a new alias
		return $this->db->table('policy_group_members')->insert([
			'PolicyGroupID' => $node['ID'],
			'Member' => $member
		]);
	}

	public function remove($group, $member) {
		// Get the policy group
		$node = $this->db->table('policy_groups')->getOneBy('Name', $group);
		if (!$node)
			throw new \Exception('Invalid group: '.$group);

		$check = $this->getMember($group, $member);
		if (!$check)
			throw new \Exception('Group member "'.$member.'" not found in '.$group);

		return $this->db->table('policy_group_members')->removeBy('ID', $check['ID']);
	}

	public function show($group=false, $search=false) {
		// List the group memebers
		if ($group) {
			// Get the policy group
			$node = $this->db->table('policy_groups')->getOneBy('Name', $group);
			if (!$node)
				throw new \Exception('Invalid group: '.$group);

			return $this->db->select(
				'*',
				'policy_group_members',
				$this->db->where('PolicyGroupID', $node['ID']) .
				($search
					? ' AND ' . $this->db->whereLike('Member', '%' . $search . '%')
					: ''
				),
				'Member');
		}

		// List the policy groups
		return $this->db->select(
			'*',
			'policy_groups',
			($search ? $this->db->whereLike('Name', '%' . $search . '%') : ''),
			'Name');
	}

	public function learn($type, $filename) {
		$type = strtolower($type);
		switch ($type) {
			case 'ham':
			case 'spam':
			case 'forget':
				break;
			default:
				throw new \Exception('Invalid learning subject: '.$type);
				
				break;
		}

		if (!is_file($filename))
			throw new \Exception('Invalid file: '.$filename);
		
		$command = new Command(CMD_SA_LEARN.' --'.$type.' --username='.VMAIL_USER.' '.$filename);
		if ($command->getExitCode() !== 0)
			throw new Exception('Could not add message to SA learning list (exit code '.$command->getExitCode().'): '.$command->getStderrText());
		return $command->getStdoutText();
	}

	public function getMember($group, $member) {
		// Get the policy group
		$node = $this->db->table('policy_groups')->getOneBy('Name', $group);
		if (!$node)
			throw new \Exception('Invalid group: '.$group);

		$node = $this->db->select(
			'*',
			'policy_group_members',
			$this->db->where('PolicyGroupID', $node['ID']) .
			' AND ' . $this->db->where('Member', $member),
			'Member');

		return array_pop($node);
	}
}
