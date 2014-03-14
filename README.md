Event
=====

JavaScript-like events for PHP


##Features

 * JS-like event objects
 * Prevent default behavior, or stop propagation altogether
 * Any callable can be callback - not forced to use closures 
 * Arbitrary number of arguments can be passed to callbacks
 * Priority ordering of callbacks
 * Extendable event objects

##Basic Usage

```php

$events = new \Phpf\Event\Container;

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

By default, events are added with a priority of 10 and executed _from lowest to highest_. You can, however, change this to high-to-low:
```php
$events->setSortOrder(\Phpf\Event\Container::SORT_HIGH_LOW);
```

Using the default low-to-high sort order, the following would result in 'myfunc_called_first' to be called before 'myfunc_called_second':
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
In the example above, because the two events have the same priority and the first event (which stops propagation) is added first, the second callback will not be called. 

A more complex example:
```php
$events->on('myevent', function ($event) {
	
	echo "I will not print. In fact, I won't even be called.";
	
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

When events listeners are executed, any returned result will be collected by the container; on completion (or propagation stoppage), the results will be returned as an indexed array.

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

The event container stores completed events and their returned arrays for later use. The example below shows how to access them:
```php
$myeventResults = $events->trigger('myevent');

// ...later on, possibly in another script:
$myeventResultsAgain = $events->getEventResult('myevent');

$myeventObject = $events->getEvent('myevent');

// ... do stuff with $myeventObject

$newResults = $events->trigger($myeventObject); // re-trigger event
```
