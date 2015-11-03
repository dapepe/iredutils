<?php

class TempFile {

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @copyright Copyright (C) 2009 - 2011, Huy Hoang Nguyen, CMS IT-Consulting GmbH, PROCABS.
	 */
	static public function getTempDirectory() {
		if ( is_callable('sys_get_temp_dir') )
			return sys_get_temp_dir();

		if ( OS::isWindows() ) {
			// From http://msdn.microsoft.com/en-us/library/aa364992%28VS.85%29.aspx:
			// The GetTempPath function checks for the existence of environment
			// variables in the following order and uses the first path found:
			//    1. The path specified by the TMP environment variable.
			//    2. The path specified by the TEMP environment variable.
			//    3. The path specified by the USERPROFILE environment variable.
			//    4. The Windows directory.
			// Note that the function does not verify that the path exists,
			// nor does it test to see if the current process has any kind
			// of access rights to the path.
			foreach (array('TMP', 'TEMP', 'USERPROFILE', 'WINDIR') as $envName) {
				$directory = getenv($envName);
				if ( $directory != '' )
					return $directory;
			}
			return 'C:'.DIRECTORY_SEPARATOR.'WINDOWS';

		} else {
			// From the PHP source code:
			// ext/standard/file.c
			//   PHP_FUNCTION(sys_get_temp_dir)
			// main/php_open_temporary_file.c
			//   PHPAPI const char* php_get_temporary_directory(void)
			$directory = getenv('TMPDIR');
			return ( $directory == '' ? '/tmp' : $directory );

		}
	}

	/**
	 * Creates a new instance.
	 *
	 * @param string $prefix
	 * @param string $directory
	 * @return void
	 */
	public function __construct($prefix = '', $directory = null) {
		if ( $directory === null )
			$directory = self::getTempDirectory();

		$this->path = tempnam($directory, $prefix);
		if ( !is_string($this->path) )
			throw new Exception('Could not create temporary file in directory "'.$directory.'" with prefix "'.$prefix.'".');
	}

	/**
	 * Deletes the temporary file if it still exists.
	 *
	 * @return void
	 * @see delete()
	 */
	public function __destruct() {
		$this->delete();
	}

	/**
	 * Deletes the temporary file.
	 *
	 * @return void
	 */
	public function delete() {
		if ( is_string($this->path) and is_file($this->path) ) {
			@unlink($this->path);
			$this->path = null;
		}
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Renames the temporary file.
	 * This can only be done once.
	 *
	 * Throws an exception on errors.
	 *
	 * @param string $newName
	 * @return void
	 */
	public function rename($newName) {
		if ( ($this->path === null) or !file_exists($this->path) )
			throw new Exception('Temporary file has already been moved.');
		elseif ( !is_file($this->path) )
			throw new Exception('Refusing to rename non-regular file "'.$this->path.'".');
		elseif ( !@rename($this->path, $newName) )
			throw new Exception('Failed to rename file "'.$this->path.'" to "'.$newName.'".');

		$this->path = null;
	}

}

?>
