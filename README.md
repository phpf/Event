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
$events->setSortOrder(\Phpf\Event\Container::::SORT_HIGH_LOW);
```

Using the default ordering, the following events:
```php
$events->on('myevent', array('My_Class', 'myOtherMethod'), 15);
$events->on('myevent', array('My_Class', 'myMethod'), 9); // default priority is 10

$events->trigger('myevent');
```
would call 'myMethod' prior to 'myOtherMethod'.

