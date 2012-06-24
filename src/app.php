<?php

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\SymfonyBridgesServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\DoctrineServiceProvider;

$app = new Silex\Application();

require __DIR__ . '/config.php';

$app->register(new HttpCacheServiceProvider());

$app->register(new SymfonyBridgesServiceProvider());

$app->register(new TwigServiceProvider(), array(
    'twig.options'          => array('cache' => false, 'strict_variables' => true),
    'twig.path'             => array(__DIR__ . '/../views')
));

$app->register(new DoctrineServiceProvider(), array(
    'db.options'    => array(
        'driver'    => $app['db.config.driver'],
        'dbname'    => $app['db.config.dbname'],
        'host'      => $app['db.config.host'],
        'user'      => $app['db.config.user'],
        'password'  => $app['db.config.password'],
    )
));

return $app;
