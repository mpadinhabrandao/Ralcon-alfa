<?php
namespace PhalconRest\Responses;

class Response extends \Phalcon\DI\Injectable{

	protected $head = false;

	public function __construct(){
		//parent::__construct();
		$di = \Phalcon\DI::getDefault();
		$this->setDI($di);
		if(strtolower($this->di->get('request')->getMethod()) === 'head'){
			$this->head = true;
		}
	}

	protected function arrayKeysToSnake($snakeArray){
		if( !is_array($snakeArray) ) return $snakeArray;
		
		foreach($snakeArray as $k=>$v){
			if (is_array($v)){
				$v = $this->arrayKeysToSnake($v);
			}
			$snakeArray[$this->snakeToCamel($k)] = $v;
			if($this->snakeToCamel($k) != $k){
				unset($snakeArray[$k]);
			}
		}
		return $snakeArray;
	}

	protected function snakeToCamel($val) {
		return str_replace(' ', '', lcfirst(ucwords(str_replace('_', ' ', $val))));
	}

}
