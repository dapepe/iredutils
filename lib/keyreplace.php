<?php

/**
 * Provides text substitution functions.
 *
 * @author Huy Hoang Nguyen <hnguyen@cms-it.de>
 * @copyright Copyright (C) 2009 - 2011, CMS IT-Consulting GmbH. All rights reserved.
 * @package PROCABS
 */

/**
 * Provides text substitution functions.
 *
 * Dependencies: NONE
 */
class KeyReplace {

	/**
	 * Determines whether to throw an exception when a variable referenced in a text does not exist.
	 * Default is FALSE.
	 *
	 * @var bool If TRUE, an exception will be thrown.
	 * @see replace()
	 */
	protected $strict = false;

	/**
	 * The text. Used to include it in the exception message in strict mode on errors.
	 *
	 * @var string
	 * @see transform()
	 */
	protected $text = null;

	/**
	 * The variables to use for replacement.
	 *
	 * @var array
	 */
	protected $variables = array();

	/**
	 * Associative array of replacement callback functions.
	 *
	 * @var array
	 */
	static protected $callbacks = array();

	static protected $expressionCallback = null;

	/**
	 * Removes all callbacks.
	 * This is primarily for unit testing.
	 *
	 * @return void
	 * @see setCallback()
	 * @see setExpressionCallback()
	 */
	static public function clearCallbacks() {
		self::$callbacks = array();
		self::$expressionCallback = null;
	}

	/**
	 * Sets a replacement callback function.
	 * The callback function will be invoked with the string to process as the
	 * first and the string's key as the second argument. Example:
	 * <code>
	 *     KeyReplace::setCallback('upper', create_function('$value, $key', 'return strtoupper($value);'));
	 *     KeyReplace::replace('Hello {{upper:world}}', array('world' => 'earth'));
	 * </code>
	 * This will call the anonymous function with the arguments "earth" and
	 * "world", and the resulting output will be
	 *     Hello EARTH
	 *
	 * @param string $name
	 * @param callback $callback
	 * @return void
	 * @see replace()
	 * @see expand()
	 * @uses $callbacks
	 */
	static public function setCallback($name, $callback) {
		if ( !is_callable($callback) )
			throw new Exception('Invalid replacement callback function for "'.$name.'".');

		self::$callbacks[$name] = $callback;
	}

	/**
	 * Defines a function to be called when an unknown variable/expression is encountered.
	 *
	 * @param callback $callback A function that will receive two arguments,
	 *     the unknown expression and the array of substitution variables.
	 *     The function
	 * @return void
	 */
	static public function setExpressionCallback($callback) {
		if ( !is_callable($callback) )
			throw new Exception('Invalid expression callback function.');

		self::$expressionCallback = $callback;
	}

	/**
	 * Substitutes variable referenced in the specified text with their values.
	 * If an undefined variable is referenced in strict mode, an exception will
	 * be thrown. If strict mode is disabled, the reference is left as is instead.
	 *
	 * @param string $text
	 * @param array $variables
	 * @param bool $strict Optional. Default is FALSE.
	 * @return string
	 * @see transform()
	 * @see setCallback()
	 */
	static public function replace($text, array $variables, $strict = false) {
		$replacer = new self($variables, $strict);
		return $replacer->transform($text);
	}

	/**
	 * Creates a new instance.
	 *
	 * @param array $variables
	 * @param bool $strict If TRUE, references to undefined replacement
	 *     variables will cause an exception to be thrown. If FALSE, these
	 *     references will be skipped. Default is FALSE.
	 * @return void
	 */
	public function __construct(array $variables, $strict = false) {
		$this->variables = $variables;
		$this->strict    = $strict;
	}

	/**
	 * Performs text substitution.
	 *
	 * @param string $text
	 * @return string
	 * @see setCallback()
	 * @uses callback()
	 */
	public function transform($text) {
		$this->text = $text;
		return preg_replace_callback('`(\\\\?)\\{\\{(.*?)\\}\\}`', array($this, 'callback'), $text);
	}

	/**
	 * Callback for {@link replace()}.
	 *
	 * @param array $matches The matches supplied by {@link preg_replace_callback()}.
	 * @return string
	 * @see transform()
	 */
	protected function callback(array $matches) {
		if ( $matches[1] != '' ) {
			// Strip leading backslash
			return substr($matches[0], 1);
		}

		$expression = explode(':', $matches[2]);

		// Expand variable
		$key = array_pop($expression);
		if ( array_key_exists($key, $this->variables) ) {
			$value = $this->variables[$key];
		} elseif ( self::$expressionCallback !== null ) {
			$value = call_user_func(self::$expressionCallback, $key, $this->variables);
			if ( $value === false ) {
				if ( $this->strict )
					throw new Exception('Undefined variable "'.$key.'" in text "'.$this->text.'".');
				else
					$value = $matches[0];
			}
		} elseif ( $this->strict ) {
			throw new Exception('Undefined variable "'.$key.'" in text "'.$this->text.'".');
		} else {
			$value = $matches[0];
		}

		// User-defined callback function
		while ( count($expression) > 0 ) {
			$functionName = array_pop($expression);
			if ( isset(self::$callbacks[$functionName]) ) {
				$value = call_user_func(self::$callbacks[$functionName], $value, $key);
			} elseif ( $this->strict ) {
				throw new Exception('Expansion of callback expression "'.$matches[0].'" from text "'.$this->text.'" failed.');
			} else {
				return $matches[0];
			}
			// Only the first function gets the key, the other ones get NULL.
			$key = null;
		}

		return $value;
	}

}

?>
