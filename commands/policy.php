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
		$this->db->table('policy_group_members')->insert([
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

		$this->db->table('policy_group_members')->removeBy('ID', $check['ID']);
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
		if (!is_file($filename))
			throw new \Exception('Invalid file: '.$filename);
		
		$command = new Command(CMD_SA_LEARN.' --'.strtolower($type).' --username='.VMAIL_USER.' '.$filename);
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
