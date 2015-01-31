<?php

namespace xpl\Event;

abstract class Eventable
{

	/**
	 * Returns the event manager.
	 *
	 * @return \xpl\Event\Manager
	 */
	abstract public function events();

	/**
	 * Returns a unique string for the event.
	 * 
	 * e.g. Prepend an object-specific string like: "mystr.{$event}"
	 * 
	 * @param string|xpl\Event\Event $event Event ID or object
	 * @return string|xpl\Event\Event Unique ID for the event.
	 */
	abstract public function getEventId($event);

	public function on($event, $callback, $priority = 10) {
		$this->events()->on($this->getEventId($event), $callback, $priority);
	}

	public function trigger($event) {
		$args = func_get_args();
		array_shift($args);
		return $this->events()->triggerArray($this->getEventId($event), $args);
	}

	public function triggerArray($event, array $args = array()) {
		return $this->events()->triggerArray($this->getEventId($event), $args);
	}

	public function filter($event, $value) {
		$args = func_get_args();
		array_shift($args);
		return $this->events()->filterArray($this->getEventId($event), $args);
	}

	public function triggerArray($event, array $args) {
		return $this->events()->filterArray($this->getEventId($event), $args);
	}

}
