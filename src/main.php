<?php
spl_autoload_register(function ($class_name) {
	$file = __DIR__ . "/" . $class_name . '.php';
	if(file_exists($file)) require_once($file);

	$file = __DIR__ . '/' . strtolower(PHP_OS) . '/' . $class_name . '.php';
	if(file_exists($file)) require_once($file);
});

function array_pluck($array, $keys)
{
	return array_intersect_key($array, array_flip($keys));
}

try{
    Config::read();
	Command::setArgList(array_slice($argv,2));

	$command = $argv[1];

	print("RepoRangler: CLI\n");

	if($method = Command::commandToMethod($command)){
		call_user_func($method);
	}else{
		print("All Supported Commands, use 'reporangler {command} --help' to obtain details about how to use each command:\n");
		foreach(get_class_methods(RepoRangler::class) as $method){
			print("  - ".Command::methodToCommand($method)."\n");
		}
	}
}catch(RepoRanglerCurlException $e){
    print("Curl has failed: ".$e->getMessage()."\n");
}catch(RepoRanglerValidationException $e){
    print($e->getMessage()."\n");
}catch(RepoRanglerException $e){
    if(strpos($e->data['message'], 'duplicate key') !== false){
        print("The information you provided already exists or was not unique enough\n");
    }else{
        print($e->getMessage()."\n");
        var_dump($e->data);
    }
}catch(Exception $e){
    print($e->getMessage()."\n");
}finally{
    Config::write();
}



