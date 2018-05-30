<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader = new \Phalcon\Loader ();
$loader->registerDirs(array(
	APP_PATH . $config->application->controllersDir . '/',
	APP_PATH . $config->application->pluginsDir,
	APP_PATH . $config->application->componentDir,
	APP_PATH . $config->application->libraryDir,
	APP_PATH . $config->application->modelsDir,
	APP_PATH . $config->application->strategyDir,
	APP_PATH . $config->application->observerDir,
))->register();