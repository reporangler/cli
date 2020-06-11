<?php
namespace RepoRangler;
use Exception;

class RepoRangler{
	static public function healthCheck()
	{
		$list = Config::getEndpoint();

		foreach($list as $service => $endpoint){
			try{
				print("Checking '$service': ");
				$response = Request::get($endpoint);
				if(!empty($response) && array_key_exists('statusCode', $response)){
					print(($response['statusCode'] === 200 ? "OK" : "FAIL") . "\n");
				}else{
					print("FAIL - Unknown reason\n");
				}
			}catch(Exception $e){
				print("FAIL - Exception Thrown, message = '".$e->getMessage()."'\n");
			}
		}
	}

	static public function login()
	{
		$username = Command::askArg(['user','username'], 'Enter Username');
		$password = Command::askArg('password', 'Enter Password');

		try{
			$endpoint = Config::getEndpoint('auth');
			$headers = Request::getLoginHeaders($username, $password);
			$response = Request::get("$endpoint/login/api", $headers);

			if(array_key_exists('token', $response)){
				Config::setToken($response['token']);
				Config::setUserId($response['id']);
				print("Login was successful, token: {$response['token']}\n");
			}else{
				print("Login has failed\n");
			}
		}catch(Exception $e){
			print("Login was not successful: ".$e->getMessage()."\n");
		}
	}

	static public function listUser()
	{
		$endpoint = Config::getEndpoint('auth');

		$response = Request::get("$endpoint/user");

		print("User List: \n");
		if(!empty($response['data'])){
			foreach($response['data'] as $user){
				print("  - {$user['username']}\n");
			}
		}else{
			print("** EMPTY **\n");
		}
	}

	static public function createUser()
	{
		$username = Command::askArg(['user','username'], 'Enter Username');
		$email = Command::askArg('email', 'Enter Email');
		$password = Command::askArg('password', 'Enter Password');

		$endpoint = Config::getEndpoint('auth');

		$response = Request::post("$endpoint/user", [
			'username' => $username,
			'email' => $email,
			'password' => $password,
		]);

		print("The User was created successfully\n");
	}

	static public function deleteUser()
	{
		$username = Command::askArg(['user','username'], 'Enter a username to delete');

		$user = self::getUserByUsername($username);

		$endpoint = Config::getEndpoint('auth');
		Request::delete("$endpoint/user/{$user['id']}");

		print("The user was deleted successfully\n");
	}

	static public function getUserByUsername($username)
	{
		if(empty($username)) throw new Exception("username was empty");

		$endpoint = Config::getEndpoint('auth');

		return Request::get("$endpoint/user/$username");
	}

	static public function userInfo()
	{
		$username = Command::getArg(['user','username']);

		if(!empty($username) && is_string($username)){
			try{
				$response = self::getUserByUsername($username);
			}catch(RepoRanglerException $e){
				die("The user with the username '$username' was not found\n");
			}
		}else{
			$user_id = Config::getUserId();
			var_dump($user_id);
			$endpoint = Config::getEndpoint('auth');
			$response = Request::get("$endpoint/user/$user_id");
		}

		$is = [
			'admin' => $response['is_admin_user'] ? 'yes' : 'no',
			'rest' => $response['is_rest_user'] ? 'yes' : 'no'
		];

		print("The user '{$response['username']}' was found:\n");
		print("  Username: {$response['username']}\n");
		print("  Email: {$response['email']}\n");
		print("  Type: \n");
		print("    Admin: {$is['admin']}\n");
		print("    Rest Access: {$is['rest']}\n");
		print("  Package Groups: \n");
		if(!empty($response['package_groups'])){
			foreach($response['package_groups'] as $name => $access){
				$access = $access === "PACKAGE_GROUP_ADMIN" ? 'admin' : 'member';
				print("    $name: $access\n");
			}
		}else{
			print("    ** EMPTY **\n");
		}

		print("  Access Tokens: (TODO: hide if not your own tokens)\n");
		if(!empty($response['access_tokens'])){
			foreach($response['access_tokens'] as $token){
				print("    {$token['type']}: {$token['token']}\n");
			}
		}else{
			print("    ** EMPTY **\n");
		}

		print("  Raw Capability List: (TODO: We should remove this eventually)\n");
		if(!empty($response['capability'])){
			foreach($response['capability'] as $cap){
				$constraint = $cap['constraint'] !== null ? json_encode($cap['constraint']) : "No Constraints given";

				print("    {$cap['name']}: $constraint\n");
			}
		}else{
			print("    ** EMPTY **\n");
		}
		print("  Created At: {$response['created_at']}\n");
		print("  Updated At: {$response['updated_at']}\n");
	}

	static public function listPackageGroup()
	{
		$endpoint = Config::getEndpoint('metadata');

		$response = Request::get("$endpoint/package-group");

		print("Package Groups: \n");
		if(!empty($response['data'])) {
			foreach ($response['data'] as $packageGroup) {
				print("  - {$packageGroup['name']}\n");
			}
		}else{
			print("  **EMPTY**\n");
		}
	}

	static public function createPackageGroup()
	{
		$packageGroup = Command::askArg('name', 'Enter the name of a package group to create');

		$endpoint = Config::getEndpoint('metadata');

		Request::post("$endpoint/package-group", ['name' => $packageGroup]);

		print("The Package group '$packageGroup' was created successfully\n");
	}

	static public function deletePackageGroup()
	{
		$packageGroup = Command::askArg('name', 'Enter the name of a package group to delete');

		$endpoint = Config::getEndpoint('metadata');

		$response = Request::get("$endpoint/package-group/name/$packageGroup");

		Request::delete("$endpoint/package-group/{$response['id']}");

		print("The Package group '$packageGroup' was deleted successfully\n");
	}

	static public function addAccessToken()
	{
		$username = Command::getArg('username');
		$user_id = Config::getUserId();
		try{
			$user = self::getUserByUsername($username);
			$user_id = $user['id'];
		}catch(Exception $e){ /* do nothing, just continue */ }

		$type = Command::askArg('type', 'Enter a name for this token (e.g: github, gitlab, maven)');
		$token = Command::askArg('token', 'Enter the token to assign');

		$endpoint = Config::getEndpoint('auth');

		$response = Request::post("$endpoint/access-token/$user_id", [
			'user_id' => $user_id,
			'type' => $type,
			'token' => $token,
		]);

		if(empty($response)){
			die("An unexpected empty response was returned\n");
		}

		if(!array_key_exists('type', $response) || $response['type'] !== $type){
			die("The type of this response was not found, or not the requested type\n");
		}

		if(!array_key_exists('token', $response) || $response['token'] !== $token){
			die("The token of this response was not found, or not the requested text\n");
		}

		print("Token '{$response['type']}' with key '{$response['token']}' was successfully created\n");
	}

	static public function listAccessToken()
	{
		$username = Command::getArg('username');
		$user_id = Config::getUserId();
		try{
			$user = self::getUserByUsername($username);
			$user_id = $user['id'];
		}catch(Exception $e){ /* do nothing, just continue */ }

		$endpoint = Config::getEndpoint('auth');

		$response = Request::get("$endpoint/access-token/$user_id");

		if(empty($response['data'])){
			print("There were no tokens by this user to delete\n");
			return;
		}

		print("The following tokens were found:\n");
		if(!empty($response['data'])){
			foreach($response['data'] as $index => $token){
				print("  ".($index+1).": {$token['type']}\n");
			}
		}else{
			print("  **EMPTY**\n");
		}
	}

	static public function removeAccessToken()
	{
		$username = Command::getArg('username');
		$user_id = Config::getUserId();
		try{
			$user = self::getUserByUsername($username);
			$user_id = $user['id'];
		}catch(Exception $e){ /* do nothing, just continue */ }

		$endpoint = Config::getEndpoint('auth');

		$response = Request::get("$endpoint/access-token/$user_id");

		if(empty($response['data'])){
			print("There were no tokens by this user to delete\n");
			return;
		}

		print("Please choose a token to delete:\n");
		if(!empty($response['data'])){
			foreach($response['data'] as $index => $token){
				print("  ".($index+1).": {$token['type']}\n");
			}
			$id = readline("Enter id: ");
			$id = intval($id);
			if($id < 1) die("The Id given was not a positive integer");

			$response = Request::delete("$endpoint/access-token/$user_id/{$response[$id-1]['id']}");

			if(array_key_exists('deleted', $response) && count($response['deleted']) === 1){
				$deleted = current($response['deleted']);
				print("Token '{$deleted['type']}' with key '{$deleted['token']}' was successfully deleted\n");
			}else{
				print("Something went wrong, but the expected response was not found\n");
			}
		}else{
			print("  **EMPTY**\n");
		}
	}

	static public function publish()
	{
		$scannerTypes = ['github', 'gitlab', 'vcs'];

		if(Command::hasHelp()){
			print("Example: publish --(repository|repo)=php --package-group=public --url=https://github.com/reporangler/lib-reporangler\n");
			print("  --(repository|repo)=php: The name of the repository that this package will be published into\n");
			print("  --package-group=public: The name of the package group that this package will be associated with\n");
			print("  --url=https://...: The url of the project from where to obtain packages from\n");
			print("  --scanner=(".implode("|",$scannerTypes)."): The type of repository scanner to use, must be one of the listed types\n\n");
			print("NOTE: It's true that not all packages come from a VCS, but at the moment, it's the only supported type\n");
			print("NOTE: The scanner will default to using the type coming from the url if you do not specify any, if the url is not github or gitlab, vcs will be used\n");
			die();
		}

		$repository = Command::askArg(['repo', 'repository'], 'Enter the repository to publish to');
		$packageGroup = Command::askArg('package-group', "Enter a package-group for repository '$repository' to publish to");
		$url = Command::askArg('url', 'What is the url to scan for packages?');
		$scanner = Command::getArg('scanner', false);

		if($scanner === null){
			$scanner = 'vcs';

			foreach($scannerTypes as $type){
				if(strpos($url, $type) !== false){
					$scanner = $type;
				}
			}
		}

		$endpoint = Config::getEndpoint($repository);

		$response = Request::post($endpoint, [
			'url' => $url,
			'type' => $scanner,
			'package_group' => $packageGroup,
		]);

		foreach($response as $package => $data){
			print("Published Package: '$package' from '{$url}'\n");
			foreach(array_keys($data) as $version){
				print("  - $version\n");
			}
		}
	}

	public function listRepository()
	{
		$endpoint = Config::getEndpoint('metadata');
		$response = Request::get("$endpoint/repository");

		print("List of Supported Repositories: \n");
		foreach($response['data'] as $repo){
			print("  - ${repo['name']}\n");
		}
	}

	public function createRepository()
	{
		$repo = Command::askArg('name', 'What is the name of the repository?');

		$endpoint = Config::getEndpoint('metadata');

		$response = Request::post("$endpoint/repository",['name' => $repo]);

		print("Repository '{$response['name']}' was successfully created\n");
	}

	public function updateRepository()
	{
		$from = Command::askArg('from', 'What repository would you like to rename?');
		$to = Command::askArg('to', 'What is the new name you\'d like to give to this repository?');

		$endpoint = Config::getEndpoint('metadata');

		try{
			$repo = Request::get("$endpoint/repository/$from");

			$response = Request::put("$endpoint/repository/${repo['id']}",['name' => $to]);
			print("Repository '{$repo['name']}' was renamed to '{$response['name']}' successfully\n");
		}catch(\Exception $e){
			switch($e->getCode()){
				case 404:
					die("The repository named '$from' was not found\n");
					break;
			}

			throw $e;
		}
	}

	public function deleteRepository()
	{
		$repo = Command::askArg('name', 'What is the name of the repository to delete?');

		$endpoint = Config::getEndpoint('metadata');

		try{
			$response = Request::get("$endpoint/repository/$repo");

			Request::delete("$endpoint/repository/{$response['id']}");
			print("Repository '{$response['name']}' was deleted successfully\n");
		}catch(\Exception $e){
			switch($e->getCode()){
				case 404:
					die("The repository named '$repo' was not found\n");
					break;
			}

			throw $e;
		}
	}

	public function joinPackageGroup()
	{
		$user = Command::askArg('user', 'Enter a username to join the group');
		$group = Command::askArg(['group', 'package-group'], 'Enter the package group to join');
		$repo = Command::askArg(['repo', 'repository'], 'Enter the name of the repository to join');

		$fields = Command::pluckArgs(['access' => null, 'admin' => null]);

		$data = [];

		$auth = Config::getEndpoint('auth');
		$metadata = Config::getEndpoint('metadata');

		try{
			$response = Request::get("$auth/user/$user");
			$data['user_id'] = $response['id'];
		}catch(Exception $e){
			throw new RepoRanglerException("The user '$user' does not exist\n");
		}

		try{
			$response = Request::get("$metadata/package-group/$group");
			$data['package_group_id'] = $response['id'];
		}catch(Exception $e){
			throw new RepoRanglerException("The package group '$group' does not exist\n");
		}

		try{
			$response = Request::get("$metadata/repository/$repo");
			$data['repository_id'] = $response['id'];
		}catch(Exception $e){
			throw new RepoRanglerException("The repository '$repo' does not exist\n");
		}

		if($fields['access']) $data['access'] = $fields['access'];
		if($fields['admin']) $data['admin'] = $fields['admin'];

		$response = Request::post("$auth/permission/user/package-group/join", $data);

		$access = array_key_exists('admin', $data) ? "admin" : "normal access";

		print("Access to package group '$group' in repository '$repo' with $access level for user '$user' was granted\n");
	}

	public function leavePackageGroup()
	{
		$user = Command::askArg('user', 'Enter a username to join the group');
		$group = Command::askArg(['group', 'package-group'], 'Enter the package group to join');
		$repo = Command::askArg(['repo', 'repository'], 'Enter the name of the repository to join');

		$fields = Command::pluckArgs(['access' => null, 'admin' => null]);

		$data = [];

		$auth = Config::getEndpoint('auth');
		$metadata = Config::getEndpoint('metadata');

		try{
			$response = Request::get("$auth/user/$user");
			$data['user_id'] = $response['id'];
		}catch(Exception $e){
			throw new RepoRanglerException("The user '$user' does not exist\n");
		}

		try{
			$response = Request::get("$metadata/package-group/$group");
			$data['package_group_id'] = $response['id'];
		}catch(Exception $e){
			throw new RepoRanglerException("The package group '$group' does not exist\n");
		}

		try{
			$response = Request::get("$metadata/repository/$repo");
			$data['repository_id'] = $response['id'];
		}catch(Exception $e){
			throw new RepoRanglerException("The repository '$repo' does not exist\n");
		}

		$response = Request::post("$auth/permission/user/package-group/leave", $data);

		print("Access to package group '$group' in repository '$repo' for user '$user' was removed\n");
	}

	public function requestJoinPackageGroup()
	{
		die(__METHOD__ . " NOT IMPLEMENTED YET\n");
	}

	public function joinRepository()
	{
		$user = Command::askArg('user', 'Enter a username to join the repository');
		$repo = Command::askArg(['repo', 'repository'], 'Enter the name of the repository to join');

		$fields = Command::pluckArgs(['access' => null, 'admin' => null]);

		$auth = Config::getEndpoint('auth');
		$metadata = Config::getEndpoint('metadata');

		try{
			$response = Request::get("$auth/user/$user");
			$data['user_id'] = $response['id'];
		}catch(Exception $e){
			throw new RepoRanglerException("The user '$user' does not exist\n");
		}

		try{
			$response = Request::get("$metadata/repository/$repo");
			$data['repository_id'] = $response['id'];
		}catch(Exception $e){
			throw new RepoRanglerException("The repository '$repo' does not exist\n");
		}

		if($fields['access']) $data['access'] = $fields['access'];
		if($fields['admin']) $data['admin'] = $fields['admin'];

		$response = Request::post("$auth/permission/user/repository/join", $data);

		$access = array_key_exists('admin', $data) ? "admin" : "normal access";

		print("Access to repository '$repo' with $access level for user '$user' was granted\n");
	}

	public function leaveRepository()
	{
		$user = Command::askArg('user', 'Enter a username to join the repository');
		$repo = Command::askArg(['repo', 'repository'], 'Enter the name of the repository to join');

		$fields = Command::pluckArgs(['access' => null, 'admin' => null]);

		$auth = Config::getEndpoint('auth');
		$metadata = Config::getEndpoint('metadata');

		try{
			$response = Request::get("$auth/user/$user");
			$data['user_id'] = $response['id'];
		}catch(Exception $e){
			throw new RepoRanglerException("The user '$user' does not exist\n");
		}

		try{
			$response = Request::get("$metadata/repository/$repo");
			$data['repository_id'] = $response['id'];
		}catch(Exception $e){
			throw new RepoRanglerException("The repository '$repo' does not exist\n");
		}

		$response = Request::post("$auth/permission/user/repository/leave", $data);

		print("Access to repository '$repo' for user '$user' was removed\n");
	}

	public function requestRepository()
	{
		die(__METHOD__ . " NOT IMPLEMENTED YET\n");
	}

	public function protectPackageGroup()
	{
		die(__METHOD__ . " NOT IMPLEMENTED YET\n");
	}

	public function unprotectPackageGroup()
	{
		die(__METHOD__ . " NOT IMPLEMENTED YET\n");
	}

	public function protectRepository()
	{
		die(__METHOD__ . " NOT IMPLEMENTED YET\n");
	}

	public function unprotectRepository()
	{
		die(__METHOD__ . " NOT IMPLEMENTED YET\n");
	}
}
