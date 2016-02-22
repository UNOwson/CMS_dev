<?php

class Warning extends Exception {

	private $title;

	public function __construct ($title = '', $message = '') {
		$this->title = $title;
		parent::__construct($message);
	}

	public function getTitle() {
		return $this->title;
	}
}


class BailOut extends Exception {

}

class WarningException extends Warning {

}