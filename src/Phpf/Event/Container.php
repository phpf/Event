<?php

namespace Phpf\Event;

use Phpf\Util\Arr;

class Container {
	
	const SORT_LOW_HIGH = 1;
	
	const SORT_HIGH_LOW = 2;
	
	protected $sort_order;
	
	protected $events = array();
	
	protected $listeners = array();
	
	protected $completed = array();
	
	protected static $instance;
	
	public static function instance(){
		if ( !isset(self::$instance) )
			self::$instance = new self();
		return self::$instance;
	}
	
	/**
	 * Sets the default sort order (low to high)
	 */
	private function __construct(){
		$this->sort_order = self::SORT_LOW_HIGH;
	}
	
	/**
	 * Sets the listener priority sort order.
	 * 
	 * @param int $order One of self::SORT_LOW_HIGH (1) or self::SORT_HIGH_LOW (2)
	 * @return $this
	 */
	public function setSortOrder( $order ){
		
		if ( ! in_array($order, array(self::SORT_LOW_HIGH, self::SORT_HIGH_LOW)) ){
			
			throw new \InvalidArgumentException("Invalid sort order.");
		}
		
		$this->sort_order = (int)$order;
		
		return $this;
	}
	
	/**
	 * Adds an event listener.
	 * 
	 * @param string $event Event ID
	 * @param mixed $call Callable to execute on event
	 * @param int $priority Priority to give to the listener
	 * @return $this
	 */
	public function on( $event, $call, $priority = 10 ){
		
		if ( ! isset($this->listeners[$event]) )
			$this->listeners[$event] = array();
		
		$this->listeners[$event][] = new Listener($event, $call, $priority);
		
		return $this;
	}
	
	/**
	 * Triggers an event.
	 * 
	 * @param Event|string $event Event object or ID
	 * @return array Items returned from event listeners.
	 */
	public function trigger( $event ){
		
		if (!$event instanceof Event){
			
			if (!is_string($event)){
				$msg = "Event must be string or instance of Event - " . gettype($event) . " given.";
				throw new \InvalidArgumentException($msg);
			}
			
			$event = new Event($event);
		}
		
		$return = array();
		
		$listeners = $this->getListeners($event->id);
		
		if ( empty($listeners) ){
			return $return;
		}
		
		// Get arguments
		$args = func_get_args();
		
		// Remove event from arguments
		array_shift($args);
		
		// Sort the events.
		usort($listeners, array($this, 'listenerSort'));
		
		// Call the listeners
		foreach($listeners as $listener){
		
			$return[] = $listener($event, $args);
			
			// Return if listener has stopped propagation
			if ( $event->propagationStopped() ){
				
				$this->completeEvent($event);
				
				return $return;
			}
		}
		
		$this->completeEvent($event);
		
		return $return;
	}
	
	/**
	 * Returns a completed Event object.
	 * 
	 * @param string $eventId The event's ID
	 * @return Event The completed Event object.
	 */
	public function getCompletedEvent($eventId){
		return isset($this->completed[$eventId]) ? $this->completed[$eventId] : null;
	}
	
	/**
	 * Saves the Event after the last listener has been called.
	 * 
	 * @param Event $event The completed event.
	 * @return void
	 */
	protected function completeEvent( Event $event ){
		$this->completed[$event->id] = $event;
	}
	
	/**
	* Get array of Listeners for an event.
	*
	* @param string $event Event ID
	* @return array Event listeners
	*/
	protected function getListeners($event){
		return isset($this->listeners[$event]) ? $this->listeners[$event] : array();
	}
	
	/**
	* Listener sort function
	*
	* @param Listener $a
	* @param Listener $b
	* @return int sort result
	*/
	protected function listenerSort(Listener $a, Listener $b){
		
		switch($this->sort_order){
			
			case self::SORT_LOW_HIGH:
			default:
					
				if ($a->priority >= $b->priority){
					return 1;
				}
				
				return -1;
			
			case self::SORT_HIGH_LOW:
					
				if ($a->priority <= $b->priority){
					return 1;
				}
				
				return -1;
		}
	}
	
}
