<?php
return new \Phalcon\Config (array(
    'database' => array(
        'adapter' => 'Postgresql',
        'host' => '10.27.169.114',
        'username' => 'signp',
        'password' => 'signpadmin2',
        'dbname' => 'smartsign3',
        'prefix' => 'n_',
        'dbport' => 5432,
        'readdbport' => 5000
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
