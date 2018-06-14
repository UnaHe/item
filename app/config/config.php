<?php
return new \Phalcon\Config (array(
    'database' => array(
        'adapter' => 'Postgresql',
        'host' => '127.0.0.1',
        'username' => 'postgres',
        'password' => '123456',
        'dbname' => 'smartsign3',
        'prefix' => 'n_',
        'dbport' => 5432,
        'readdbport' => 5432
    ),
    'application' => array(
        'controllersDir' => 'app/controllers/',
        'modelsDir' => 'app/models/',
        'viewsDir' => 'app/views/',
        'pluginsDir' => 'app/plugins/',
        'libraryDir' => 'app/library/',
		'strategyDir' => 'app/strategy/',
		'observerDir' => 'app/observer/',
		'componentDir' => 'app/component/',
        'caching' => 0
    ),
));
