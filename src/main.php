<?php

include_once __DIR__ . '/../vendor/autoload.php';

use DatabaseGraphviz\Command\GenerateCommand;
use Symfony\Component\Console\Application;

$application = new Application('database-graphviz');
$application->add(new GenerateCommand());
$application->run();
