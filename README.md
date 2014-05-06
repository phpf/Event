Event
=====

Simple, powerful, and extendable JavaScript-like events for PHP.

 1. [Features](#features)
 2. [Basic Usage](#basic-usage)
 3. [Examples](#examples)
  * [Priorities](#priorities)
  * [Cancelling Events](#cancelling-events)
  * [Single-Listener Events](#single-listener-events)
  * [Returning Results](#returning-results)
  * [Stopping Propagation](#stopping-propagation)
  * [Retrieving Completed Events](#retrieving-completed-events)
  * [Custom Event Objects](#custom-event-objects)



##Features

 * Simple syntax (`on()` to bind, `trigger()` to emit)
 * Extendable event objects
 * Priority ordering of callbacks
 * Callbacks can be any callable (not limited to closures)
 * An arbitrary number of arguments can be passed to callbacks
 * Prevent default behavior (`preventDefault()`), or stop propagation altogether (`stopPropagation()`)
 * One-time events (`one()`)
 * Event cancelling (`off()`)

##Basic Usage

```php

$events = new \Phpf\Event\Manager;

$events->on('myevent', function ($event, $myarg) {
	
	if ($event->isDefaultPrevented()){
		return;
	}
	
	echo "I'm doing my event called $myarg!";
});

$events->trigger('myevent', 'Example'); // outputs "I'm doing my event called Example!"
```

##Examples

###Priorities

By default, events are added with a priority of 10 and _executed from lowest to highest_:
```php
$events->on('myevent', function ($event) { echo "Child"; }, 15);

$events->on('myevent', function ($event) { echo "Bear"; }, 9);

$events->trigger('myevent'); // outputs "BearChild"
```

You can change the sort order to high-to-low like so:
```php
$events->setSortOrder(\Phpf\Event\Manager::SORT_HIGH_LOW);
```

###Cancelling Events

To cancel an event, simply call the `off()` method:
```php
$events->off('myevent');
```
This will remove any listeners bound to the event, so they will not be called if subsequently triggered.

###Single-Listener Events

You can limit an event's execution to a single listener by using the `one()` method instead of `on()`:
```php
$events->one('myevent', function ($event) {
	echo "I will print.";
});

$events->on('myevent', function ($event) {
	echo "I will not print, even though I was bound later.";
});

$events->trigger('myevent'); // Prints "I will print."
```

###Returning Results

When events listeners are executed, any value returned from the listener will be collected; on completion (or propagation stoppage), the results will be returned as an indexed array.

For example:
```php
$events->on('myevent', function ($event) {
	
	return 'Hello';
});

$events->on('myevent', function ($event) {
	
	return 'Goodbye';
});

$results = $events->trigger('myevent');

print_r($results); // array(0 => 'Hello', 1 => 'Goodbye');
```

###Stopping Propagation

Like JS, propagation of events can be stopped by a listener at any time.
```php
$events->on('myevent', function ($event) {
	
	echo "This will be printed";
	
	$event->stopPropagation();
});

$events->on('myevent', function ($event) {

	echo "This will not be printed.";
});
```

```php
$events->on('myevent', function ($event) {
	
	echo "I will not be called.";
}, 12);

$events->on('myevent', function ($event) {
	
	echo "I will print second.";
	
	$event->stopPropagation();
}, 11);

$events->on('myevent', function ($event) {
	
	echo "I will print first.";
});
```

###Retrieving Completed Events

The completed events and their returned arrays are stored for later use. The event object can be retrieved using the `event()` method, the results using the `result()` method:
```php
$results = $events->trigger('myevent');

// ...later on, in another script:
$myevent = $events->event('myevent');
$sameResults = $events->result('myevent');

// ... do stuff with event/results

// re-trigger the event !
$newResults = $events->trigger($myevent);
```

###Custom Event Objects
You can also pass an instance of the `Event` class to the `trigger()` method instead of the event ID. This way, you can use custom event objects.

For example, if you want listeners to be able to modify a single returned value (a "filter"), you could create a class like this:
```php
namespace MyEvents;

use Phpf\Event\Event;

class FilterEvent extends Event {
	
	protected $value;
	
	public function getValue() {
		return $this->value;
	}
	
	public function setValue($value) {
		$this->value = $value;
		return $this;
	}
}
```
And then use it like so:
```php
$events->on('myFilterEvent', function ($event) {
	$event->setValue('Custom events');
});

$events->on('myFilterEvent', function ($event) {
	$val = $event->getValue();
	$event->setValue($val . ' are cool.');
});

$filterEvent = new \MyEvents\FilterEvent('myFilterEvent');

$events->trigger($filterEvent);

$filteredEvent = $events->event('myFilterEvent');

echo $filteredEvent->getValue(); // Prints "Custom events are cool."
```
