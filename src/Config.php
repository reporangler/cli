<?php
namespace RepoRangler;

class Config{
	const file = '.reporangler-config.json';
	const user_agent = 'reporangler-cli';

	const defaults = [
		'endpoints' => [
			'auth' => 'http://auth.reporangler.develop',
			'metadata' => 'http://metadata.reporangler.develop',
			'php' => 'http://php.reporangler.develop',
			'npm' => 'http://npm.reporangler.develop',
			'storage' => 'http://storage.reporangler.develop',
		]
	];

	static public $state = [];

	static public function read(){
		self::$state = file_exists('file://'.self::file)
			? json_decode(file_get_contents(self::file), true)
			: self::defaults;

		var_dump(self::$state);
	}

	static public function write(){
		return file_put_contents(self::file, json_encode(self::$state, JSON_PRETTY_PRINT));
	}

	static public function getEndpoint($name = null)
	{
		if(empty(self::$state)) {
			die("State: Was empty\n");
		}

		if(!array_key_exists('endpoints', self::$state)) {
			die("State: Endpoints are not configured\n");
		}

		if($name && !array_key_exists($name, self::$state['endpoints'])) {
			die("State: Endpoint '$name' does not exist\n");
		}

		return $name ? self::$state['endpoints'][$name] : self::$state['endpoints'];
	}

	static public function setToken($token)
	{
		self::$state['login_token'] = $token;
	}

	static public function getToken()
	{
		return array_key_exists('login_token', self::$state)
			? self::$state['login_token']
			: null;
	}

	static public function setUserId($userId)
	{
		self::$state['user_id'] = $userId;
	}

	static public function getUserId()
	{
		return self::$state['user_id'];
	}
}
