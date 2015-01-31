<?php

namespace xpl\Event;

class Listener
{

	/**
	 * Event's unique identifier.
	 * @var string
	 */
	protected $event_id;
	
	/**
	 * Callback.
	 * @var callable
	 */
	protected $callback;
	
	/**
	 * Priority.
	 * @var integer
	 */
	protected $priority;

	/**
	 * Construct listener using event ID, callback, and priority.
	 * 
	 * @param string $eventID Unique identifier for the event.
	 * @param callable $callback Callback to run when listener called.
	 * @param integer $priority Listener execution priority.
	 */
	public function __construct($event_id, $callback, $priority) {
		$this->event_id = $event_id;
		$this->callback = $callback;
		$this->priority = $priority;
	}

	/**
	 * Invokes the listener's callback function.
	 *
	 * @param array $args Array of arguments to pass to callback.
	 * @return mixed Result of callback.
	 */
	public function __invoke(array $args) {
		
		switch(count($args)) {
			// there is always at least 1 argument (the event)
			case 1:
				return call_user_func($this->callback, $args[0]);
			case 2:
				return call_user_func($this->callback, $args[0], $args[1]);
			case 3:
				return call_user_func($this->callback, $args[0], $args[1], $args[2]);
			case 4:
				return call_user_func($this->callback, $args[0], $args[1], $args[2], $args[3]);
			default:
				return call_user_func_array($this->callback, $args);
		}
	}
	
	public function __get($var) {
		return isset($this->$var) ? $this->$var : null;
	}

}
