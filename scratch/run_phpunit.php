<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Prepare temporary phpunit xml without bootstrap attribute
$xmlContent = file_get_contents(__DIR__ . '/../phpunit.xml');
$xmlNoBootstrap = preg_replace('/bootstrap="[^"]*"/', '', $xmlContent);
$tempXml = sys_get_temp_dir() . '/phpunit_nobootstrap_' . md5(__DIR__) . '.xml';
file_put_contents($tempXml, $xmlNoBootstrap);

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
putenv('QUEUE_CONNECTION=sync');

$args = array_slice($_SERVER['argv'], 1);
if (empty($args)) {
    $args = ['tests/Feature'];
}

$application = new \PHPUnit\TextUI\Application();
$exitCode = $application->run(array_merge(['phpunit', '-c', $tempXml], $args));
exit($exitCode);
