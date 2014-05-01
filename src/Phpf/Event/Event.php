<?php

namespace Phpf\Event;

use ArrayAccess;

class Event implements ArrayAccess
{
	
	/**
	 * Unique identifier for the event.
	 * @var string
	 */
	public $id;
	
	/**
	 * Whether the default behavior should be prevented.
	 * @var boolean
	 */
	private $defaultPrevented = false;
	
	/**
	 * Whether propagation should be stopped.
	 * @var boolean
	 */
	private $propagationStopped = false;

	/**
	 * Constructor takes one string parameter, the event ID.
	 * 
	 * @param string $id Event identifier.
	 */
	final public function __construct($id) {
		$this->id = $id;
	}

	/**
	 * Prevents execution of the event's default behavior.
	 * 
	 * @return $this
	 */
	final public function preventDefault() {
		$this->defaultPrevented = true;
		return $this;
	}
	
	/**
	 * Stops propagation of the event.
	 * 
	 * @return $this
	 */
	final public function stopPropagation() {
		$this->propagationStopped = true;
		return $this;
	}

	/**
	 * Returns true if preventDefault() has been called on the event.
	 * 
	 * @return boolean
	 */
	final public function isDefaultPrevented() {
		return $this->defaultPrevented;
	}

	/**
	 * Returns true if stopPropagation() has been called on the event.
	 * 
	 * @return boolean
	 */
	final public function isPropagationStopped() {
		return $this->propagationStopped;
	}
	
	/**
	 * Returns a property value.
	 * 
	 * @param string $index Property name.
	 * @return mixed Property value, if set.
	 */
	public function offsetGet($index) {
		return $this->$index;
	}
	
	/**
	 * Sets a property value.
	 * 
	 * Cannot set private properties using this method.
	 * 
	 * @param string $index Property name.
	 * @param mixed $newval Property value.
	 * @return void
	 */
	public function offsetSet($index, $newval) {
		if ('defaultPrevented' !== $index && 'propagationStopped' !== $index) {
			$this->$index = $newval;
		}
	}
	
	/**
	 * Returns true if a property exists and is not null.
	 * 
	 * @param string $index Property name.
	 * @return boolean True if property exists and is not null, otherwise false.
	 */
	public function offsetExists($index) {
		return isset($this->$index);
	}

	/**
	 * Unsets a property.
	 * 
	 * Cannot unset private properties using this method.
	 * 
	 * @param string $index Property name
	 * @return void
	 */
	public function offsetUnset($index) {
		if ('defaultPrevented' !== $index && 'propagationStopped' !== $index) {
			unset($this->$index);
		}
	}

}
