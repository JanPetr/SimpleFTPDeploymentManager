<?php
set_time_limit(3600);

$container = require __DIR__.'/../app/bootstrap.php';

$container->application->run();
