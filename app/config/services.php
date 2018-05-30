<?php
use Phalcon\Mvc\View;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
/**
 * The FactoryDefault Dependency Injector automatically register the right services providing a full stack framework
 */

$di = new FactoryDefault ();
$di->setShared(
    "logger",
    function () {
        require_once APP_PATH.'app/library/LogModel.php';
        return new LogModel();
    }
);

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->setShared('url', function () use ($config) {
    $url = new UrlProvider ();
    $baseUri = 'https://signposs1.oss-cn-shenzhen.aliyuncs.com/';
//    $baseUri = '/';
    $url->setStaticBaseUri($baseUri);
    return $url;
});

$di->setShared('view', function () use ($config) {
    $view = new View ();
    $view->setViewsDir(APP_PATH . $config->application->viewsDir . '/');
    $view->registerEngines(array(
        '.html' => 'volt',
    ));

    return $view;
});

/**
 * Setting up volt
 */
$di->set('volt', function ($view, $di) {
    $volt = new VoltEngine ($view, $di);
    $voltPath = APP_PATH . 'cache/volt/';
    if (!file_exists($voltPath)) {
        umask(0);
        mkdir($voltPath, 0777, true);
    }
    $volt->setOptions(array(
        'compiledPath' => $voltPath,
        'compileAlways' => true,
        'compiledSeparator' => '_'
    ));
    $volt->getCompiler()->addFilter('_', function ($resolvedArgs, $exprArgs) {
        return '$this->translate->_(' . $resolvedArgs . ')';
    });
    return $volt;
}, true);

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('dbRead', function () use ($config) {
    $dbclass = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $connection = new $dbclass (array(
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->dbname,
        'port' => $config->database->readdbport
    ));
//	$eventsManager = new EventsManager();
//	$logger = new FileLogger(LOG_FILE_DIR."db.log");
//	// Listen all the database events
//	$eventsManager->attach('db', function ($event, $connection) use ($logger) {
//		if ($event->getType() == 'afterQuery') {
//			$logger->log($connection->getSQLStatement(), Phalcon\Logger::INFO);
//		}
//	});
//
//// Assign the eventsManager to the db adapter instance
//	$connection->setEventsManager($eventsManager);
    return $connection;
});

$di->setShared('db', function () use ($config) {
    $dbclass = 'Phalcon\Db\Adapter\Pdo\\' . $config->database->adapter;
    $connection = new $dbclass(array(
        'host' => $config->database->host,
        'username' => $config->database->username,
        'password' => $config->database->password,
        'dbname' => $config->database->dbname,
        'port' => $config->database->dbport
    ));


//	$eventsManager = new EventsManager();
//	$logger = new FileLogger(LOG_FILE_DIR."db.txt");
	// Listen all the database events
//	$eventsManager->attach('db', function ($event, $connection) use ($logger) {
//        $logger->log($event->getType() . ' '.serialize($connection->getType()), Phalcon\Logger::INFO);
//		if ($event->getType() == 'beforeQuery') {
//			$logger->log($connection->getSQLStatement(), Phalcon\Logger::INFO);
//		}
//        if ($event->getType() == 'afterQuery') {
//            $logger->log($connection->getSQLStatement(), Phalcon\Logger::INFO);
//        }
//	});

// Assign the eventsManager to the db adapter instance
//	$connection->setEventsManager($eventsManager);
    return $connection;
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new \Phalcon\Mvc\Model\Metadata\Files(array(
        'metaDataDir' => APP_PATH . 'cache/metadata/'
    ));
});

/**
 * Start the session the first time some component request the session service
 */
$di->setShared('session', function () {
    ini_set('session.gc_maxlifetime', SESSION_LIFE_TIME);
    session_set_cookie_params(SESSION_LIFE_TIME);
    $session = new \Phalcon\Session\Adapter\Files();
//    $session = new Redis(
//        [
//            "uniqueId"   => SYSTEM,
//            "host"       => "127.0.0.1",
//            "port"       => 6379,
//            "persistent" => false,
//            "lifetime"   => SESSION_LIFE_TIME,
//            "index"      => 1,
//        ]
//    );
    $session->start();
    return $session;
});
$di->setShared('cache', function () {
    $cache = new \RedisCache(null);
//	$frontCache = new Phalcon\Cache\Frontend\Data(
//		array(
//			"lifetime" => 86400
//		)
//	);
//	$cache = new MemcacheModel ($frontCache, array(
//		'servers' => array(
//			array(
//				'host' => 'localhost',
//				'port' => 11211
//			)
//		)
//	));
    return $cache;
});