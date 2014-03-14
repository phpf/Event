Event
=====

JavaScript-like events for PHP


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

##Features

 * JS-like event objects
 * Prevent default behavior, or stop propagation altogether
 * Any callable can be callback - not forced to use closures 
 * Arbitrary number of arguments can be passed to callbacks
 * Priority ordering of callbacks
 * Extendable event objects
