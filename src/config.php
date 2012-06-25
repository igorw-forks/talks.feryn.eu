<?php

// Databases
$app['db.config.driver']    = 'pdo_mysql';
$app['db.config.dbname']    = 'talks';
$app['db.config.host']      = '127.0.0.1';
$app['db.config.user']      = 'talks';
$app['db.config.password']  = 'talks';
$app['db.config.charset']   = 'charset';

// Debug
$app['debug'] = true;

// Cache
$app['cache.path'] = __DIR__ . '/../cache';

// Http cache
$app['http_cache.cache_dir'] = $app['cache.path'] . '/http';
