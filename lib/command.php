<?php

/**
 * Program execution functions.
 *
 * @author Huy Hoang Nguyen <hnguyen@cms-it.de>
 * @copyright Copyright (C) 2009 - 2011, CMS IT-Consulting GmbH. All rights reserved.
 * @package PROCABS
 */

/**
 * Program execution functions.
 *
 * Dependencies:
 * - {@link Util}
 */
class Command {

	/**
	 * The command line.
	 *
	 * @var string
	 * @see getCommand()
	 */
	private $command;

	/**
	 * The input that is written to the command's STDIN.
	 *
	 * @var string
	 * @see getInput()
	 */
	private $input;

	/**
	 * The command's exit / return code.
	 *
	 * @var int
	 * @see getExitCode()
	 */
	private $exitCode;

	/**
	 * Buffer for STDOUT.
	 *
	 * @var string
	 */
	private $stdoutText;

	/**
	 * Buffer for STDERR.
	 *
	 * @var string
	 */
	private $stderrText;

	/**
	 * Escapes the specified argument.
	 * This works like {@link escapeshellarg()} except for empty strings where
	 * this function returns '""' (two double-quotation marks) while
	 * {@link escapeshellarg()} returns an empty string.
	 *
	 * @param string $argument
	 * @return string
	 * @see escapeshellarg()
	 */
    static public function escapeShellArg($argument) {
        if ( (string)$argument == '' )
            return '""';
        else
            return escapeshellarg($argument);
    }

    /**
     * Runs the specified command.
     * @param string $cmd
     * @param string $input
     * @return void
     */
    public function __construct($cmd, $input = '') {
		$this->command  = $cmd;
		$this->input    = $input;
		$this->exitCode = false;

        // Set terminal to "dumb" if called from a web server
        $env = null;
        if ( isset($_SERVER['REQUEST_METHOD']) )
            $env = array('TERM' => 'dumb');

        $process = @proc_open(
            $cmd,
            array(
                0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
                1 => array('pipe', 'w'),  // stdout is a pipe that the child will write to
                2 => array('pipe', 'w')   // stderr is a file to write to
            ),
            $pipes,
            null,
            $env,
			array('bypass_shell' => true) // Windows only: Do not use "cmd.exe" for execution which fails when there are multiple escapes ["path\to\program" "arg1" "arg2"].
        );

        if ( !is_resource($process) )
            return;

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $this->stdoutText = stream_get_contents($pipes[1]);
        $this->stderrText = stream_get_contents($pipes[2]);

        // Close all pipes before calling "proc_close()" to avoid a deadlock
        fclose($pipes[1]);
        fclose($pipes[2]);

        $this->exitCode = proc_close($process);
    }

	/**
	 * Returns the entire command line.
	 *
	 * @return string
	 */
	public function getCommand() {
		return $this->command;
	}

	/**
	 * Returns the input that was fed to the command via STDIN.
	 *
	 * @return string
	 * @see getStdoutText()
	 * @see getStderrText()
	 */
	public function getInput() {
		return $this->input;
	}

	/**
	 * Returns the command's exit / return code.
	 *
	 * @return int The exit code, or FALSE on errors.
	 */
	public function getExitCode() {
		return $this->exitCode;
	}

	/**
	 * Returns the text the command printed to STDOUT.
	 *
	 * @return string
	 * @see getStderrText()
	 * @see getInput()
	 */
	public function getStdoutText() {
		return $this->stdoutText;
	}

	/**
	 * Returns the text the command printed to STDOUT.
	 *
	 * @return string
	 * @see getStdoutText()
	 * @see getInput()
	 */
	public function getStderrText() {
		return $this->stderrText;
	}

}

?>
