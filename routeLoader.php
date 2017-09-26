<?php


return call_user_func(function(){

	$collections = array();
	$collectionFiles = scandir(ROOT_DIR . '/Routes');

	foreach($collectionFiles as $collectionFile){
		$pathinfo = pathinfo($collectionFile);

		if($pathinfo['extension'] === 'php'){
			$collections[] = include(ROOT_DIR.'/Routes/' . $collectionFile);
		}
	}

	return $collections;
});
