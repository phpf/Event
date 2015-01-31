<?php

namespace xpl\Event\Std;

class Error extends \xpl\Event\Event {
	
	public $exceptions;
	
	public function attachException(\Exception $exception){
		$this->exceptions[] = $exception;
	}
	
	public function getExceptions(){
		return $this->exceptions;
	}
	
}
