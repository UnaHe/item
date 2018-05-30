<?php
$debug = 1;
ini_set('display_errors', $debug);
error_reporting(($debug ? E_ALL : 0));
define('APP_PATH', realpath('..') . '/');
$config = require APP_PATH . 'app/config/config.php';
define('DB_PREFIX', $config->database->prefix);
define('CACHING', $config->application->caching);
define('SYSTEM', 'item');
define('SESSION_LIFE_TIME', 86400);
define('LOG_FILE_DIR', APP_PATH . 'log/' . date('Y-m-d') . '/');
try {
    include APP_PATH . 'app/config/loader.php';
    /**
     * Load application services
     */
    include APP_PATH . 'app/config/services.php';
    $di->set('dispatcher', function () use ($di) {
        $eventsManager = new \Phalcon\Events\Manager ();
        /**
         * Check if the user is allowed to access certain action using the SecurityPlugin
         */
        $eventsManager->attach('dispatch:beforeDispatch', new AccessPlugin());
        $eventsManager->attach('dispatch:afterDispatchLoop', function () use ($di) {
            $logger = $di->get('logger');
            $logger->log();
        });
        /**
         * Handle exceptions and not-found exceptions using NotFoundPlugin
         */
        // $eventsManager->attach ( 'dispatch:beforeException', new NotFoundPlugin () );
        $dispatcher = new \Phalcon\Mvc\Dispatcher ();
        $dispatcher->setEventsManager($eventsManager);
        return $dispatcher;
    });
    $application = new Phalcon\Mvc\Application ($di);
    $user = $application->session->get('user');
    $locale = isset ($user ['client_locale']) ? $user ['client_locale'] : 'zh_CN';
    $application->getDI()->set('translate', function () use ($locale) {
        return new \Phalcon\Translate\Adapter\NativeArray (array(
            'content' => require APP_PATH . 'app/i18n/'. $locale . '.php'
        ));
    });
    echo $application->handle()->getContent();
} catch (Exception $e) {
    if ($debug) {
        die ($e->__toString());
    }
//    $crushSubject = new ObserverSubject();
//    $crushSubject->attach(new CrushObserverRoger($_SERVER ['HTTP_HOST'] . $_SERVER ['SERVER_PORT'] . ':' . $e->__toString()));
//    $crushSubject->notify();
}