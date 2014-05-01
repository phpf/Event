<?php

namespace Phpf\Event;

use Phpf\Common\ManagerInterface;
use OutOfBoundsException;
use InvalidArgumentException;

/**
 * Event manager/container.
 * 
 * Class for binding and triggering events.
 */
class Manager implements ManagerInterface
{
	
	/**
	 * Sort and execute listeners from low-to-high priority.
	 * e.g. 1 before 2, 2 before 3, etc.
	 * @var integer
	 */
	const LOW_TO_HIGH = 1;
	
	/**
	 * Sort and execute listeners from high-to-low priority.
	 * e.g. 3 before 2, 2 before 1, etc.
	 * @var integer
	 */
	const HIGH_TO_LOW = 2;
	
	/**
	 * Default priority assigned to events.
	 * @var integer
	 */
	const DEFAULT_PRIORITY = 10;
	
	/**
	 * Type of listener order to use. Either 1 or 2.
	 * @var integer
	 */
	protected $order;
	
	/**
	 * Events and their listeners.
	 * @var array
	 */
	protected $listeners = array();
	
	/**
	 * Completed event objects and their results.
	 * @var array
	 */
	protected $completed = array();

	/**
	 * Sets the default sort order (low to high).
	 * 
	 * @return void
	 */
	public function __construct() {
		$this->order = static::LOW_TO_HIGH;
	}

	/**
	 * Adds an event listener (real listeners are lazy-loaded).
	 *
	 * @param string $event Event ID.
	 * @param callable $call Callable to execute on event trigger.
	 * @param integer $priority Priority to give to the listener.
	 * 
	 * @return $this
	 */
	public function on($event, $call, $priority = self::DEFAULT_PRIORITY) {

		if (! isset($this->listeners[$event])) {
			$this->listeners[$event] = array();
		}
		
		$this->listeners[$event][] = array($call, $priority);

		return $this;
	}
	
	/**
	 * Adds an event listener which will be the only one called for the event.
	 * 
	 * @param string $event Event ID.
	 * @param callable $call Callable to execute on event trigger.
	 * 
	 * @return $this
	 */
	public function one($event, $call) {
			
		if (! isset($this->listeners[$event])) {
			$this->listeners[$event] = array();
		}
		
		$this->listeners[$event]['one'] = array($call, 1);
		
		return $this;
	}
	
	/**
	 * Unregisters one or all listeners for an event.
	 * 
	 * @param string|Event $event Event ID or object.
	 * @param callable $callback [Optional] Callback to remove.
	 * 
	 * @return $this
	 * 
	 * @throws InvalidArgumentException if event is not a string or Event instance.
	 */
	public function off($event, $callback = null) {
		
		if (is_string($event)) {
			$id = $event;
		} else if ($event instanceof Event) {
			$id = $event->id;
		} else {
			throw new InvalidArgumentException('Event must be string or instance of Event - '.gettype($event).' given.');
		}
		
		if (empty($this->listeners[$id])) {
			return $this;
		}
		
		if (! isset($callback)) {
			unset($this->listeners[$id]);
			return $this;
		}
		
		foreach($this->listeners[$id] as $i => $arr) {
			if ($callback == $arr[0]) {
				unset($this->listeners[$id][$i]);
			}
		}
		
		return $this;
	}

	/**
	 * Triggers an event.
	 *
	 * @param Event|string $event Event object or ID.
	 * @param ... Args
	 * 
	 * @return array Items returned from event listeners.
	 */
	public function trigger($event) {

		// prepare the event
		if (false === ($prepared = $this->prepare($event))) {
			return array();
		}

		list($event, $listeners) = $prepared;

		// get args
		$args = func_get_args();

		// remove event from args
		array_shift($args);

		return $this->execute($event, $listeners, $args);
	}

	/**
	 * Triggers an event with an array of arguments.
	 * 
	 * @param Event|string $event Event object or ID.
	 * @param array $args Args to pass to listeners.
	 * 
	 * @return array Items returned from event Listeners.
	 */
	public function triggerArray($event, array $args = array()) {

		if (false === ($prepared = $this->prepare($event))) {
			return array();
		}

		list($event, $listeners) = $prepared;

		return $this->execute($event, $listeners, $args);
	}

	/**
	 * Returns a completed Event object.
	 *
	 * @param string $eventId The event's ID.
	 * 
	 * @return Event The completed Event object.
	 */
	public function event($eventId) {
		return isset($this->completed[$eventId]) ? $this->completed[$eventId]['event'] : null;
	}

	/**
	 * Returns the array that was returned from a completed Event trigger.
	 *
	 * This allows you to access previously returned values (obviously).
	 *
	 * @param string $eventId The event's ID
	 * 
	 * @return array Values returned from the event's listeners, else null.
	 */
	public function result($eventId) {
		return isset($this->completed[$eventId]) ? $this->completed[$eventId]['result'] : null;
	}

	/**
	 * Sets the listener priority sort order.
	 *
	 * @param int $order One of self::LOW_TO_HIGH (1) or self::HIGH_TO_LOW (2)
	 * 
	 * @return $this
	 * 
	 * @throws OutOfBoundsException if order is not one of the class constants.
	 */
	public function orderBy($order) {

		if ($order != static::LOW_TO_HIGH && $order != static::HIGH_TO_LOW) {
			throw new OutOfBoundsException("Invalid sort order.");
		}

		$this->order = (int)$order;

		return $this;
	}

	/**
	 * Prepares event for execution by lazy-loading listener objects.
	 *
	 * @param string|Event $event The event ID or object to trigger.
	 * 
	 * @return boolean|array False if no listeners, otherwise indexed array of the 
	 * Event object (at index 0) and an array of listeners (at index 1).
	 * 
	 * @throws InvalidArgumentException if event is not a string or Event object.
	 */
	protected function prepare($event) {
		
		if ($event instanceof Event) {
				
			if (empty($this->listeners[$event->id]))
				return false;
			
		} else if (is_string($event)) {
		
			if (empty($this->listeners[$event]))
				return false;
		
			$event = new Event($event);
		
		} else {
			$msg = "Event must be string or instance of Event - ".gettype($event)." given.";
			throw new InvalidArgumentException($msg);
		}
		
		// call only the 'one' listener, if it exists
		if (isset($this->listeners[$event->id]['one'])) {
			
			list($callback, $priority) = $this->listeners[$event->id]['one'];
			
			$listeners = array(new Listener($event->id, $callback, $priority));
			
		} else {
			// normal event - get all listeners
			$listeners = $this->listeners[$event->id];
			
			// lazy-load the listeners
			foreach($listeners as $key => &$value) {
				$value = new Listener($event->id, $value[0], $value[1]);
			}
		}
		
		return array($event, $listeners);
	}

	/**
	 * Executes the event listeners; sorts, calls, and returns result.
	 *
	 * @param Event $event Event object.
	 * @param array $listeners Array of Listener objects.
	 * @param array $args Callback arguments.
	 * 
	 * @return array Array of event callback results.
	 */
	protected function execute(Event $event, array $listeners, array $args = array()) {

		$return = array();

		// Sort the listeners.
		usort($listeners, array($this, 'sortListeners'));

		// Call the listeners
		foreach ( $listeners as $listener ) {

			$return[] = $listener($event, $args);

			// Return if listener has stopped propagation
			if ($event->isPropagationStopped()) {
				return $this->complete($event, $return);
			}
		}

		return $this->complete($event, $return);
	}

	/**
	 * Stores the Event and its return array once the last listener has been called.
	 *
	 * @param Event $event The completed event object.
	 * @param array $return The returned array.
	 * 
	 * @return array The returned array, for returning from execute().
	 */
	protected function complete(Event $event, array $return) {
		$this->completed[$event->id] = array('event' => $event, 'result' => $return);
		return $return;
	}

	/**
	 * Listener sort function.
	 *
	 * @param Listener $a
	 * @param Listener $b
	 * 
	 * @return integer Sort result
	 */
	protected function sortListeners(Listener $a, Listener $b) {

		if ($this->order === static::LOW_TO_HIGH) {
			
			return ($a->priority >= $b->priority) ? 1 : -1;
			
		} else {
				
			return ($a->priority <= $b->priority) ? 1 : -1;
		}
	}
	
	/**
	 * Implements Phpf\Common\ManagerInterface
	 */
	final public function manages() {
		return 'events';
	}

}
