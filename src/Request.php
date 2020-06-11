<?php
namespace RepoRangler;

class Request{
	static public $statusCode = 500;

	private function exec($method, $url, $data, $headers)
	{
		$curl = curl_init();

		if(empty($headers)){
			$headers = Request::getTokenHeaders();
		}

		$headers[] = 'Content-Type: application/json';

		// Coerce the method to a set of allowed types
		$method = in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']) ? $method : 'GET';

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, Config::user_agent);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		if(!empty($data)){
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		}

		Request::$statusCode = 500;

		$result = curl_exec($curl);

		$statusCode = Request::$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if(!strlen($result)) throw new Exception('The api request returned an empty response');

		$json = json_decode($result, true);

		switch(Request::$statusCode){
			case 200:
				return $json;
				break;

			case 401:
				throw new RepoRanglerException("Unauthorized", $json);
				break;

			case 404:
				throw new RepoRanglerException("The resource was not found ($statusCode)", $json);
				break;

			case 422:
				switch($json['exception']){
					case 'Illuminate\Validation\ValidationException':
						throw new RepoRanglerValidationException($json);
						break;

					case 'Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException':
						throw new RepoRanglerAlreadyExistsException($json['message']);
						break;
				}
		}

		throw new RepoRanglerException("There was a generic unknown error ($statusCode)", $json);
	}

	public function get($url, $headers=[])
	{
		return self::exec('GET', $url, null, $headers);
	}

	public function post($url, $data, $headers=[])
	{
		return self::exec('POST', $url, $data, $headers);
	}

	public function put($url, $data, $headers=[])
	{
		return self::exec('PUT', $url, $data, $headers);
	}

	public function delete($url, $headers=[])
	{
		return self::exec('DELETE', $url, null, $headers);
	}

	public function options($url, $headers=[])
	{
		return self::exec('OPTIONS', $url, null, $headers);
	}

	static public function getLoginHeaders($username, $password)
	{
		return [
			"reporangler-login-type: database",
			"reporangler-login-username: $username",
			"reporangler-login-password: $password",
		];
	}

	public function getTokenHeaders()
	{
		$token = Config::getToken();

		if(empty($token)){
			die("Token was empty, you must login first\n");
		}

		return ["Authorization: Bearer {$token}"];
	}
}
