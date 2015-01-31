<?php

namespace xpl\Event;

/**
 * Event manager/container/emitter.
 * 
 * Class for binding and triggering events.
 */
class Manager
{
	
	/**
	 * Sort and execute listeners from low-to-high priority.
	 * e.g. 1 before 2, 2 before 3, etc.
	 * @var integer
	 */
	const SORT_LOW_HIGH = 0;
	
	/**
	 * Sort and execute listeners from high-to-low priority.
	 * e.g. 3 before 2, 2 before 1, etc.
	 * @var integer
	 */
	const SORT_HIGH_LOW = 1;
	
	/**
	 * Sort order to use for listener priorities. 
	 * 
	 * One of the SORT_* class constants. Default is low-to-high.
	 * 
	 * @var integer
	 */
	protected $order;
	
	/**
	 * The sort function to use.
	 * 
	 * @var string
	 */
	protected $sort_func;
	
	/**
	 * Associative array of events and their listeners.
	 * 
	 * Event name/IDs are used as keys.
	 * 
	 * @var array
	 */
	protected $listeners;
	
	/**
	 * Completed event objects and their results.
	 * 
	 * @var array
	 */
	protected $completed;
	
	/**
	 * Sets the default sort order (low to high).
	 * 
	 * @return void
	 */
	public function __construct() {
		$this->listeners = array();
		$this->completed = array();
		$this->setSortOrder(static::SORT_LOW_HIGH);
	}

	/**
	 * Adds an event listener.
	 *
	 * @param string $event Event ID.
	 * @param callable $call Callable to execute on event trigger.
	 * @param integer $priority Priority to give to the listener.
	 * 
	 * @return $this
	 */
	public function on($event, $call, $priority = 10) {

		if (! isset($this->listeners[$event])) {
			$this->listeners[$event] = array();
		}
		
		$this->listeners[$event][] = new Listener($event, $call, $priority);
		
		return $this;
	}
	
	/**
	 * Unregisters one or all listeners for an event.
	 * 
	 * @param string|Event $event Event ID or object.
	 * @param callable $callback [Optional] Callback to remove. If no callback 
	 * is given, then all of the event's listeners are removed.
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
			throw new \InvalidArgumentException('Event must be string or instance of Event, given: '.gettype($event));
		}
		
		if (! empty($this->listeners[$id])) {
				
			// if no callback given, unset all listeners
			if (empty($callback)) {
				unset($this->listeners[$id]);
			
			} else {
				// iterate through listeners and unset matching callback
				foreach($this->listeners[$id] as $i => $listener) {
				
					if ($callback == $listener->callback) {
						unset($this->listeners[$id][$i]);
					}
				}
			}
		}
		
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
		
		$this->listeners[$event]['one'] = new Listener($event, $call, 1);
		
		return $this;
	}
	
	/**
	 * Triggers an event.
	 *
	 * @param Event|string $event Event object or ID.
	 * @param ... Arguments to pass to callback.
	 * 
	 * @return array Items returned from event listeners.
	 */
	public function trigger($event) {

		// prepare the event
		if (false === ($prepared = $this->prepare($event))) {
			return array();
		}

		list($event, $listeners) = $prepared;

		// get function args
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
	
	public function filter($event, $value) {

		// get function args
		$args = func_get_args();
		
		// remove event from args
		array_shift($args);
		
		return $this->filterArray($event, $args);
	}

	public function filterArray($event, array $args) {
		
		if (false === ($prepared = $this->prepare($event))) {
			// No listeners, return the initial value
			return reset($args);
		}

		list($event, $listeners) = $prepared;
		
		$event->value = array_shift($args);

		$this->execute($event, $listeners, $args);
		
		return $event->value;
	}

	/**
	 * Returns whether an event has been completed.
	 * 
	 * @param string $eventId Event ID.
	 * @return boolean True if event has completed, otherwise false.
	 */
	public function did($eventId) {
		return isset($this->completed[$eventId]);
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
	 * Returns the array that was returned from a completed event trigger.
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
	 * Tip: you can also use PHP's SORT_ASC and SORT_DESC
	 *
	 * @param int $order One of self::LOW_TO_HIGH or self::HIGH_TO_LOW
	 * 
	 * @return $this
	 * 
	 * @throws \DomainException if order is not one of the class constants.
	 */
	public function setSortOrder($order) {

		if ($order != static::SORT_LOW_HIGH && $order != static::SORT_HIGH_LOW) {
			throw new \DomainException("Invalid sort order.");
		}

		$this->order = (int)$order;
		
		$this->sort_func = 'sortListeners'.($this->order === static::SORT_LOW_HIGH ? 'Asc' : 'Desc');

		return $this;
	}
	
	/**
	 * Returns true if sort order is low to high.
	 * 
	 * @return boolean True if sort order is low-to-high, otherwise false.
	 */
	public function isLowToHigh() {
		return $this->order === static::SORT_LOW_HIGH;
	}
	
	/**
	 * Returns true if sort order is high to low.
	 * 
	 * @return boolean True if sort order is high-to-low, otherwise false.
	 */
	public function isHighToLow() {
		return $this->order === static::SORT_HIGH_LOW;
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
		
		if (is_string($event)) {
			
			// don't instantiate if no listeners
			if (empty($this->listeners[$event])) {
				return false;
			}
			
			$event = new Event($event);
		
		} else if (! $event instanceof Event) {
			throw new \InvalidArgumentException('Event must be string or instance of Event, given: '.gettype($event));
		
		} else if (empty($this->listeners[$event->id])) {
			// object given, no listeners
			return false;
		}
		
		if (isset($this->listeners[$event->id]['one'])) {
			// If a "one" listener exists, call no others
			$listeners = array($this->listeners[$event->id]['one']);
		
		} else {
			// normal event - get all listeners
			$listeners = $this->listeners[$event->id];
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
		
		$results = array();

		// Sort listeners by priority
		usort($listeners, array($this, $this->sort_func));
		
		// Prepend event to args array
		array_unshift($args, $event);
		
		// Call each listener
		foreach ($listeners as $listener) {
			
			// Collect the returned value
			$results[] = $value = $listener($args);
			
			// Update the event value
			if ($value && $value !== $event->value) {
				$event->value = $value;
			}
			
			unset($value);
			
			// End if propagation stopped
			if ($event->isPropagationStopped()) {
				break;
			}
		}
		
		// Return the array of results
		return $this->complete($event, $results);
	}

	/**
	 * Stores the Event and its return array once the last listener has been called.
	 *
	 * @param Event $event The completed event object.
	 * @param array $result The array of returned results.
	 * 
	 * @return array The returned result array, for returning from execute().
	 */
	protected function complete(Event $event, array $results) {
			
		$this->completed[$event->id] = array(
			'event' => $event, 
			'result' => $results
		);
		
		return $results;
	}

	/**
	 * Ascending listener sort callback.
	 *
	 * @param Listener $a
	 * @param Listener $b
	 * 
	 * @return integer Sort result
	 */
	protected function sortListenersAsc(Listener $a, Listener $b) {
		return ($a->priority >= $b->priority) ? 1 : -1;
	}
	
	/**
	 * Descending listener sort callback.
	 *
	 * @param Listener $a
	 * @param Listener $b
	 * 
	 * @return integer Sort result
	 */
	protected function sortListenersDesc(Listener $a, Listener $b) {
		return ($a->priority <= $b->priority) ? 1 : -1;
	}
	
}
