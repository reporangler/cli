<?php
class RepoRanglerException extends \Exception{
	public $data;

	public function __construct($message = null, $data = [])
	{
		$this->data = $data;
		$code = is_array($data) && array_key_exists('code', $data) ? $data['code'] : 500;

		parent::__construct($message, $code);
	}
}
