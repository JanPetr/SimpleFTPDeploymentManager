<?php

require __DIR__.'/../vendor/autoload.php';

$configurator = new Nette\Configurator();

// Enable Nette Debugger for error visualisation & logging
//$configurator->setDebugMode(TRUE);
$configurator->enableDebugger(__DIR__.'/../log');
Tracy\Debugger::$maxLen = 2000;

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory(__DIR__.'/../temp');
$configurator->createRobotLoader()->addDirectory(__DIR__)->register();

// Create Dependency Injection container from config.neon file
$configurator->addConfig(__DIR__.'/config/config.neon');
if(file_exists(__DIR__.'/config/config.local.neon'))
{
	$configurator->addConfig(__DIR__.'/config/config.local.neon');
}

$container = $configurator->createContainer();

return $container;
