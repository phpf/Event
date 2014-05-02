Event
=====

Simple, powerful, and extendable JavaScript-like events for PHP.


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

### Using Priorities

By default, events are added with a priority of 10 and _executed from lowest to highest_. However, you can change this to high-to-low, like so:
```php
$events->setSortOrder(\Phpf\Event\Manager::SORT_HIGH_LOW);
```

Using the default low-to-high order, the following would result in 'myfunc_called_first' to be called before 'myfunc_called_second':
```php
$events->on('myevent', 'myfunc_called_second'), 15);
$events->on('myevent', 'myfunc_called_first', 9);

$events->trigger('myevent');
```

### Stopping Propagation

Like JS events, propagation of Phpf events can be stopped by a listener at any time. 

```php
$events->on('myevent', function ($event) {
	
	echo "This will be printed";
	
	$event->stopPropagation();
});

$events->on('myevent', function ($event) {

	echo "This will not be printed.";
});
```
In the example above, because the two events have the same priority and the first event is added first (which stops propagation), the second callback will not be called. 

A more complex example:
```php
$events->on('myevent', function ($event) {
	
	echo "I won't even be called.";
	
}, 15);

$events->on('myevent', function ($event) {
	
	echo "I will print second.";
	
	$event->stopPropagation();
	
}, 11);

$events->on('myevent', function ($event) {
	
	echo "I will print first.";
});
```

### Returning Results

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

### Retrieving Completed Events

The event container stores completed events and their returned arrays for later use. The event object can be retrieved using the `event()` method, and the results can be retrieved using the `result()` method:
```php
$results = $events->trigger('myevent');

// ...later on, possibly in another script:
$results2 = $events->result('myevent');

$myevent = $events->event('myevent');

// ... do stuff with $myevent

// re-trigger the event
$newResults = $events->trigger($myevent);
```

### Cancelling Events

To cancel an event, simply call the `off()` method:
```php
$events->off('myevent');
```
This will remove any listeners bound to the event, so they will not be called if subsequently triggered.

### Single-Listener Events

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

### Custom Event Objects
You can also pass an instance of the `Event` class to the `trigger()` method instead of the event ID. This way, you can use custom event objects.

For example, if you want an event listeners to be able to modify a returned value (a "filter"), you could create a class like the following:
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

You could then use the class like so:
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
