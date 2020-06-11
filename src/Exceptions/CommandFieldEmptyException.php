<?php
namespace RepoRangler\Exception;

class CommandFieldEmptyException extends \Exception{
	public function __construct($message="There are missing command parameters that were required", $code=0){
		parent::__construct($message, $code);
	}
}
