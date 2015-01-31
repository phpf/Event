<?php

namespace xpl\Event\Std;

class Invokable extends \xpl\Event\Event
{

	/**
	 * Callback to run when event is invoked.
	 * @var callable
	 */
	protected $call;

	/**
	 * Attaches a callable to run when event is invoked.
	 *
	 * @param callable $call Callback.
	 * @return $this
	 * @throws InvalidArgumentException if callback is not callable.
	 */
	public function onInvoke($call) {

		if (! is_callable($call)) {
			throw new \InvalidArgumentException("Cannot attach uncallable function to invokable event.");
		}

		$this->call = $call;

		return $this;
	}

	/**
	 * Calls the event callback.
	 *
	 * @param array $args Arguments to pass to callback.
	 * @return mixed Results of callback.
	 * @throws RuntimeException if callback is not set.
	 */
	public function __invoke($args = array()) {

		if (! isset($this->call)) {
			throw new \RuntimeException("Cannot invoke event - no callback set.");
		}

		return call_user_func_array($this->call, $args);
	}

}
