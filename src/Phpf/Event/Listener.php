<?php

namespace Phpf\Event;

class Listener
{

	/**
	 * Event's unique identifier.
	 * @var string
	 */
	public $eventId;
	
	/**
	 * Callback.
	 * @var callable
	 */
	public $callback;
	
	/**
	 * Priority.
	 * @var integer
	 */
	public $priority;

	/**
	 * Construct listener using event ID, callback, and priority.
	 * 
	 * @param string $eventID Unique identifier for the event.
	 * @param callable $callback Callback to run when listener called.
	 * @param integer $priority Listener execution priority.
	 * @return void
	 */
	public function __construct($eventId, $callback, $priority) {
		$this->eventId = $eventId;
		$this->callback = $callback;
		$this->priority = $priority;
	}

	/**
	 * Invokes the listener's callback function.
	 *
	 * @param Event &$event Event object.
	 * @param array $args [Optional] Array of arguments to pass to callback.
	 * @return mixed Result of callback.
	 */
	public function __invoke(Event &$event, array $args = array()) {

		// Prepend Event to $args array
		array_unshift($args, $event);

		return call_user_func_array($this->callback, $args);
	}

}
