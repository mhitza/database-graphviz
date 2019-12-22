<?php

$print = true;
$records = true;

use DatabaseGraphviz\Command\GenerateCommand;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Application;

include_once __DIR__ . '/../vendor/autoload.php';

$application = new Application('database-graphviz');
$application->add(new GenerateCommand());
$application->run();

$config = new Configuration();
$connection = DriverManager::getConnection(
    [
        "driver" => "pdo_mysql",
        "host" => "127.0.0.1",
        "user" => "root",
        "dbname" => $argv[1],
        "password" => "password"
    ],
    $config
);

if ($print) {
    printf("digraph %s {\n", $argv[1]);

    if ($records) {
        printf("\tnode [shape=record];\n");
        #printf("\tgraph [rankdir=\"LR\"];\n");
        printf("\trankdir=LR;\n");
    }
}

$tables = [];
$references = [];
$statement = $connection->query("SHOW TABLES");
while ($row = $statement->fetch()) {
    $tableName = current($row);
    $tables[$tableName] = [];

    if ($print && !$records) {
        print "\t$tableName;\n";
    }
}

foreach ($tables as $tableName => $value) {
    $createStatement = $connection->query(sprintf("SHOW CREATE TABLE %s", $tableName));
    while ($row_ = $createStatement->fetch()) {
        $description = $row_['Create Table'];
        $lines = explode("\n", $description);

        $columns = [];
        $relationships = [];
        foreach ($lines as $line) {
            if ($records && preg_match('/^\s+`(.*?)`/', $line, $matches)) {
                $columns[] = $matches[1];
            }
            if (preg_match('/FOREIGN KEY \(.*\) REFERENCES .*?\(.*\)/', $line, $matches)) {
                $relationship = str_replace(['FOREIGN KEY ', 'REFERENCES ', '(', ')', '`'], '', $matches[0]);

                if ($print) {
                    $parts = explode(' ', $relationship);

                    if ($records) {
                        $relationships[] = sprintf("\t%s:%s -> %s:%s;\n", $tableName, $parts[0], $parts[1], $parts[2]);
                    } else {
                        printf("\t%s -> %s [label=\"%s\"]; \n", $tableName, $parts[1], $parts[0]);
                    }
                }
            }
        }

        if ($records) {
            array_unshift($columns, $tableName);
            printf("\t%s [label=\"%s\"];\n", $tableName, implode("|", array_map(
                function($column) {
                    return sprintf("<%s> %s", $column, $column);
                },
                $columns
            )));

            print implode("", $relationships);
        }
    }
}

if ($print) {
    print "}\n";
}
