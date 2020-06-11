<?php
class RepoRanglerValidationException extends RepoRanglerException{
	public function __construct($data = [])
	{
		$keys = implode(', ', array_keys($data['validation']));
		$message = "Validation has failed because one of the following fields was not valid: $keys";

		parent::__construct($message, $data['validation']);
	}
}
