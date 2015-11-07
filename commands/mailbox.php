<?php

class MailboxCommand extends Helper {
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
						echo 'Insufficient arguments: No e-mail address or domain specified'."\n";
						$this->showUsage();
						die();
					}

					$this->remove($cli['arguments'][1]);
					echo 'OK'."\n";
					break;

				case 'add':
					if (!isset($cli['arguments'][1])) {
						echo 'Insufficient arguments: No e-mail address or domain specified'."\n";
						$this->showUsage();
						die();
					}

					$this->add(
						$cli['arguments'][1],
						isset($cli['options']['password'])
							? ($cli['options']['password'] == 1
								? \cli\prompt('Enter password', false, ': ', true)
								: $cli['options']['password']
							)
							: false,
						isset($cli['options']['maildir'])
							? $cli['options']['maildir']
							: false,
						isset($cli['options']['ishash'])
					);
					echo 'OK'."\n";
					break;

				case 'update':
					if (!isset($cli['arguments'][1])) {
						echo 'Insufficient arguments: No e-mail address or domain specified'."\n";
						$this->showUsage();
						die();
					}

					$this->update(
						$cli['arguments'][1],
						isset($cli['options']['password'])
							? ($cli['options']['password'] == 1
								? \cli\prompt('Enter password', false, ': ', true)
								: $cli['options']['password']
							)
							: false,
						isset($cli['options']['ishash'])
					);
					echo 'OK'."\n";
					break;

				case 'show':
					$this->renderTable(
						$this->show(
							isset($cli['arguments'][1]) ? $cli['arguments'][1] : false,
							isset($cli['options']['search']) ? $cli['options']['search'] : false
						),
						['username', 'created', 'passwordlastchange']
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

	public function showUsage() {
		echo 'Usage: iredcli mailbox'."\n";
		echo '  show [<DOMAIN> --search=<SEARCH>]'."\n";
		echo '  add <EMAIL> [--password=<PASSWORD> --maildir=<MAILDIR> --ishash]'."\n";
		echo '  update <EMAIL> [--password=<PASSWORD> --ishash]'."\n";
		echo '  remove <EMAIL>'."\n";
		echo '  export [<DOMAIN> --search=<SEARCH>]'."\n";
		echo '  import <FILENAME>'."\n";
	}

	public function add($email, $password=false, $maildir=false, $isHash=false) {
		// Check email format
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
			throw new \Exception('Not a valid e-mail: '.$email);

		// Check if the mailbox exists
		$node = $this->db->table('mailbox')->getOneBy('username', $email);
		if ($node)
			throw new \Exception('Username already exists: '.$email);

		// Check if an alias exists
		$node = $this->db->table('alias')->getOneBy('address', $email);
		if ($node)
			throw new \Exception('An alias already exists for this address: '.$email);

		// Check if the domain exists
		$domain = explode('@', $email);
		$localpart = array_shift($domain);
		$domain = array_pop($domain);
		$node = $this->db->table('domain')->getOneBy('domain', $domain);
		if (!$node)
			throw new \Exception('Domain does not exist: '.$domain);

		// Create password and hash
		if ($password === false) {
			$password = $this->generateRandomString();
			echo 'Generated password: '.$password."\n";
		}
		$hash = $isHash ? $password : $this->generatePasswordHash($password);

		// Initialize the mail directory
		if ($maildir) {
			$maildir = explode(DIRECTORY_SEPARATOR, $maildir);
		} else {
			$str1 = substr($localpart, 0, 1);
			$str2 = substr($localpart, 1, 1) === false ? $str1 : substr($localpart, 1, 1);
			$str3 = substr($localpart, 2, 1) === false ? $str2 : substr($localpart, 2, 1);
			$maildir = [
				$domain,
				$str1,
				$str2,
				$str3,
				$localpart.'-'.date('Y.m.d.H.i.s')
			];
		}

		// Create the directory
		$currentDir = STORAGE_DIR . DIRECTORY_SEPARATOR . STORAGE_NODE . DIRECTORY_SEPARATOR;
		foreach ($maildir as $dir) {
			$currentDir .= $dir . DIRECTORY_SEPARATOR;
			if (!is_dir($currentDir)) {
				mkdir($currentDir);
				chown($currentDir, STORAGE_USER);
			}
		}

		$this->db->table('mailbox')->insert([
			'username'             => $email,
			'password'             => $hash,
			'name'                 => 'comment',
			'storagebasedirectory' => realpath(STORAGE_DIR),
			'storagenode'          => STORAGE_NODE,
			'maildir'              => implode('/', $maildir),
			'quota'                => 0,
			'domain'               => $domain,
			'passwordlastchange'   => date('Y-m-d H:i:s'),
			'created'              => date('Y-m-d H:i:s'),
			// expired: 9999-12-31 00:00:00
			'local_part'           => $localpart
		]);
		$this->db->table('alias')->insert([
			'address'      => $email,
			'goto'         => $email,
			'name'         => '',
			'accesspolicy' => '',
			'domain'       => $domain,
			'created'      => date('Y-m-d H:i:s'),
			'modified'     => date('Y-m-d H:i:s'),
		]);
	}

	public function update($email, $password=false) {
		// Check if the mailbox exists
		$node = $this->db->table('mailbox')->getOneBy('username', $email);
		if (!$node)
			throw new \Exception('Mailbox does not exist: '.$email);

		// Create password and hash
		if ($password === false) {
			$password = $this->generateRandomString();
			echo 'Generated password: '.$password."\n";
		}
		$hash = $this->generatePasswordHash($password);

		// Get the password hash
		$hash = $this->generatePasswordHash($password);

		$this->db->table('mailbox')->updateBy('username', $email, [
			'password'           => $hash,
			'passwordlastchange' => date('Y-m-d H:i:s')
		]);
	}

	public function remove($email) {
		// Check if the mailbox exists
		$node = $this->db->table('mailbox')->getOneBy('username', $email);
		if (!$node)
			throw new \Exception('Mailbox does not exist: '.$email);

		$dir =
			$node['storagebasedirectory'] . DIRECTORY_SEPARATOR .
			$node['storagenode'] . DIRECTORY_SEPARATOR .
			$node['maildir'];

		if (!is_dir($dir))
			throw new \Exception('Storage directory does not exist: '.$dir);

		$this->removeDir($dir);
		$this->db->table('mailbox')->removeBy('username', $email);
		$this->db->remove('alias', $this->db->where('address', $email).' AND '.$this->db->where('goto', $email));

		// Remove all aliases that include the mailbox
		include_once 'alias.php';
		$alias = new AliasCommand();
		foreach ($alias->show($email) as $node)
			$alias->remove($node['address'], $email);
	}

	public function show($domain=false, $search=false) {
		if ($domain)
			return $this->db->select('*', 'mailbox', $this->db->where('domain', $domain).($search ? ' AND '.$this->db->whereLike('username', '%'.$search.'%') : ''), 'domain');

		return $this->db->select('*', 'mailbox', $search ? $this->db->whereLike('username', '%'.$search.'%') : false, 'domain');
	}

	public function import($filename) {
		$data = json_decode(file_get_contents($filename), true);
		if (!$data)
			throw new \Exception('No data to import');

		foreach ($data as $row) {
			try {
				$this->add($row['username'], isset($row['hash']) ? $row['hash'] : $row['password'], isset($row['maildir']) ? $row['maildir'] : false, isset($row['hash']));
			} catch (Exception $e) {
				echo 'ERROR: '.$e->getMessage()."\n";
			}
		}

	}

	public function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	public function generatePasswordHash($password) {
		$command = new Command(CMD_PYTHON.' '.RES_DIR.'generate_password_hash.py SSHA512 '.$password);
		if ($command->getExitCode() !== 0)
			throw new Exception('Could not create password hash (exit code '.$command->getExitCode().'): '.$command->getStderrText());
		return $command->getStdoutText();
	}

	public function removeDir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir."/".$object) == "dir")
						$this->removeDir($dir.DIRECTORY_SEPARATOR.$object);
					else
						unlink($dir.DIRECTORY_SEPARATOR.$object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}
}
