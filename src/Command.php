<?php
namespace RepoRangler;

class Command
{
	static private $argList;

	static public function setArgList($argList)
	{
		self::$argList = $argList;
	}

	static public function methodToCommand($method)
	{
		$parts = preg_split('/(?=[A-Z])/', $method, -1, PREG_SPLIT_NO_EMPTY);
		return strtolower(implode('-', $parts));
	}

	static public function commandToMethod($arg)
	{
		$command = trim($arg,'-');
		$command = str_replace('-', ' ', $command);
		$command = ucwords($command);
		$command = str_replace(' ', '', $command);
		$command = lcfirst($command);

		if(method_exists("RepoRangler", $command)) {
			return "RepoRangler::$command";
		}

		$altArg = rtrim($arg, 's');
		if($altArg === $arg) return null;

		print("Command '$arg' did not exist, trying '$altArg'\n");
		return self::commandToMethod($altArg);
	}

	static public function extractArg($arg)
	{
		list($arg, $value) = explode("=",$arg) + [null, null];

		return [trim($arg,'-'), $value];
	}

	static public function pluckArgs($fields, $required = [])
	{
		foreach(self::$argList as $arg){
			list($arg, $value) = Command::extractArg($arg);

			if(array_key_exists($arg, $fields)){
				if($value === null) $value = true;

				$fields[$arg] = $value;
			}
		}

		if(in_array(null, array_pluck($fields, $required))){
			throw new CommandFieldEmptyException();
		}

		return $fields;
	}

	static public function getArg($name, $throw=false, $default=null)
	{
		if(!is_array($name)) $name = [$name];

		foreach (self::$argList as $arg) {
			list($arg, $value) = self::extractArg($arg);

			if (in_array($arg, $name, true)) return $value;
		}

		if($throw){
			throw new CommandFieldEmptyException("A parameter from list '".implode(', ',$name)."' was not found\n");
		}

		return $default;
	}

	static public function askArg($name, $text)
	{
		$arg = self::getArg($name);

		if(empty($arg)) $arg = readline(trim($text).": ");

		return $arg;
	}

	static public function hasHelp()
	{
		return self::getArg(self::$argList, 'help') !== null;
	}
}
