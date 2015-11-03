<?php

/**
 * getopt()-style implementation.
 *
 * @author Huy Hoang Nguyen <hnguyen@cms-it.de>
 * @copyright Copyright (C) 2009 - 2011, CMS IT-Consulting GmbH. All rights reserved.
 * @package PROCABS
 */

/**
 * getopt()-style implementation.
 *
 * Dependencies: None.
 */
class GetOptions {

	private $shortOptions;
	private $longOptions;

	private $result = array();

	private $unparsedArguments = array();

	/**
	 * Convenience function for {@link parse()}.
	 * Equivalent to:
	 * <code>
	 *     $o = new GetOptions($shortopts, $longopts);
	 *     $result = $o->parse($params);
	 * </code>
	 *
	 * @param array $params
	 * @param string $shortopts
	 * @param array $longopts
	 * @return array The array of parsed options.
	 * @see parse()
	 */
	static public function get(array $params, $shortopts, array $longopts) {
		$obj = new self($shortopts, $longopts);
		return $obj->parse($params);
	}

	/**
	 * Creates a new instance.
	 *
	 * @param string $shortopts
	 * @param array $longopts
	 * @return void
	 * @see parse()
	 * @see get()
	 */
	public function __construct($shortopts, array $longopts) {
		$this->shortOptions = array();
		if ( is_array($shortopts) )
			$shortopts = implode('', $shortopts);
		preg_match_all('`([^:])((?:::?)?)`', $shortopts, $matches, PREG_SET_ORDER);
		foreach ($matches as $match)
			$this->shortOptions[$match[1]] = strlen($match[2]);

		$this->longOptions = array();
		foreach ($longopts as $longopt) {
			if ( !preg_match('`^([^:]+)((?:::?)?)$`', $longopt, $matches) )
				continue;
			$this->longOptions[$matches[1]] = strlen($matches[2]);
		}
	}

	private function addArgument($name, $value = false) {
		if ( !isset($this->result[$name]) )
			$this->result[$name] = $value;
		elseif ( is_array($this->result[$name]) )
			$this->result[$name][] = $value;
		else
			$this->result[$name] = array($this->result[$name], $value);
	}

	/**
	 * Parses a list of supplied arguments.
	 *
	 * @param array $params
	 * @return array The array of options.
	 * @see get()
	 */
	public function parse(array $params) {
		$this->result = array();
		$this->unparsedArguments = array();

		reset($params);
		while ( list(, $param) = each($params) ) {
			if ( !preg_match('`^-(?:-([^=]+)(?:=(.*))?|([^-].*))$`', $param, $matches) ) {
				$this->unparsedArguments[] = $param;
				continue;
			}

			if ( isset($matches[1]) and ($matches[1] != '') ) {
				// Long option
				$option = $matches[1];
				if ( !isset($this->longOptions[$option]) )
					continue;

				$valueType = $this->longOptions[$option];

				$value = ( isset($matches[2]) ? $matches[2] : '' );
				if ( $value != '' ) {
				} elseif ( $valueType === 0 ) {
					$value = false;
				} else {
					$value = current($params);
					next($params);
				}

				if ( ($value !== false) or ($valueType !== 1) )
					$this->addArgument($option, ( $valueType === 0 ? false : $value ));

			} else {
				// Short option
				$shortOptionsString = $matches[3];
				$shortOptionsLength = strlen($shortOptionsString);

				for ($i = 0; $i < $shortOptionsLength; $i++) {
					$option = $shortOptionsString[$i];
					if ( !isset($this->shortOptions[$option]) )
						break;

					$valueType = $this->shortOptions[$option];
					if ( $valueType === 0 ) {
						$this->addArgument($option);
					} else {
						$value = substr($shortOptionsString, $i + 1);
						if ( $value == '' ) {
							$value = current($params); // Next parameter is the value
							next($params);
						}
						if ( ($value !== false) or ($valueType !== 1) )
							$this->addArgument($option, $value);
						break;
					}
				}
			}
		}

		return $this->result;
	}

	public function getUnparsedArguments() {
		return $this->unparsedArguments;
	}

	/**
	 * Returns an option's value or NULL if the option is missing.
	 * Multiple aliases may be specified for the option name as follows:
	 * <code>
	 *     $helpOption = $o->getOption('help', 'h');
	 * </code>
	 *
	 * @param string $name
	 * @return mixed Returns the option's value string or FALSE if the option
	 *     was supplied, otherwise NULL.
	 * @see parse()
	 */
	public function getOption($name) {
		$names = func_get_args();
		foreach ($names as $name) {
			if ( isset($this->result[$name]) )
				return $this->result[$name];
		}
		return null;
	}

}

?>
