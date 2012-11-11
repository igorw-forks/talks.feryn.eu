<?php
//Joind.in
$app['joindin.api'] = 'http://api.joind.in/v2.1/';
$app['joindin.user.id'] = '1975';
// Databases
$app['db.config.driver']    = 'pdo_mysql';
$app['db.config.dbname']    = 'talks';
$app['db.config.host']      = '127.0.0.1';
$app['db.config.user']      = 'talks';
$app['db.config.password']  = 't47kS!';
$app['db.config.charset']   = 'utf8';

// Debug
$app['debug'] = true;

// Cache
$app['cache.path'] = __DIR__ . '/../cache';

// Http cache
$app['http_cache.cache_dir'] = $app['cache.path'] . '/http';
