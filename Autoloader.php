<?php 
//echo "Hi! :D";
define('CORE_DIR',__DIR__);

use Phalcon\Logger;
use Phalcon\Events\Event;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Logger\Adapter\File as FileLogger;

use 	Phalcon\DI\FactoryDefault as DefaultDI,
	Phalcon\Mvc\Micro\Collection,
	Phalcon\Config\Adapter\Ini as IniConfig,
	Phalcon\Loader;


if( !is_array($loaderList) ) $loaderList = array();
$loaderList['PhalconRest'] = CORE_DIR."/";

$loader = new Loader();
$loader->registerNamespaces($loaderList)->register();

$di = new DefaultDI();
$di->set('collections', function(){
	return include(CORE_DIR.'/routeLoader.php');
});

$di->setShared('config', function() {
	return new IniConfig(ROOT_DIR."/config.ini");
});
$di->setShared('session', function(){
	$session = new \Phalcon\Session\Adapter\Files();
	$session->start();
	return $session;
});

$di->set('modelsCache', function() {
	$frontCache = new \Phalcon\Cache\Frontend\Data(array(
		'lifetime' => 0,//3600
	));

	//File cache settings
	$cache = new \Phalcon\Cache\Backend\File($frontCache, array(
		'cacheDir' => __DIR__ . '/cache/'
	));

	return $cache;
});

$di->set('db', function(){
	$conf = $this->get('config');
	if(isset($conf->database)){
		
		$dbConf = $conf->database->toArray();
		unset($dbConf['adapter']);
		$className = "\\Phalcon\\Db\\Adapter\\Pdo\\{$conf->database->adapter}";
		$connection = new $className($dbConf);
		// remember not to use in production since it can reveal very sensitive data
		if ($dbConf['debug'] ) {
			$eventsManager = new EventsManager();
			$logger = new FileLogger(ROOT_DIR.'/logs/db.log');
			$eventsManager->attach(
			    'db:beforeQuery',
			    function (Event $event, $connection) use ($logger) {
				$sql = $connection->getSQLStatement();
				$logger->log($sql, Logger::INFO);
			    }
			);
			$connection->setEventsManager($eventsManager);
		}		

		return $connection;
	}
});
$di->setShared('requestBody', function() {
	$in = file_get_contents('php://input');
	$in = json_decode($in, FALSE);

	if($in === null){
		throw new HTTPException(
			'There was a problem understanding the data sent to the server by the application.',
			409,
			array(
				'dev' => 'The JSON body sent to the server was unable to be parsed.',
				'internalCode' => 'REQ1000',
				'more' => ''
			)
		);
	}

	return $in;
});

$app = new Phalcon\Mvc\Micro();
$app->setDI($di);
