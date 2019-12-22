<?php

$print = true;
$subgraphs = true;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

include_once __DIR__ . '/../vendor/autoload.php';

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
}

$tables = [];
$references = [];
$statement = $connection->query("SHOW TABLES");
while ($row = $statement->fetch()) {
    $tableName = current($row);
    $tables[$tableName] = [];

    if ($print && !$subgraphs) {
        print "\t$tableName;\n";
    }
}

$clusterIndex = 0;
$collected = [];
foreach ($tables as $tableName => $value) {
    $createStatement = $connection->query(sprintf("SHOW CREATE TABLE %s", $tableName));
    while ($row_ = $createStatement->fetch()) {
        $description = $row_['Create Table'];

        if ($subgraphs) {
            printf("\tsubgraph cluster_%d {\n", $clusterIndex++);
            printf("\t\tlabel=\"%s\";\n", $tableName);
        }

        $lines = explode("\n", $description);
        foreach ($lines as $line) {
            if ($subgraphs && preg_match('/^\s+`(.*?)`/', $line, $matches)) {
                $column = $matches[1];
                printf("\t\t\"%s.%s\";\n", $tableName, $column);
            }
            if (preg_match('/FOREIGN KEY \(.*\) REFERENCES .*?\(.*\)/', $line, $matches)) {
                $relationship = str_replace(['FOREIGN KEY ', 'REFERENCES ', '(', ')', '`'], '', $matches[0]);

                if ($print && !$subgraphs) {
                    $parts = explode(' ', $relationship);
                    printf("\t%s -> %s [label=\"%s\"]; \n", $tableName, $parts[1], $parts[0]);
                }

                if ($subgraphs) {
                    $parts = explode(' ', $relationship);
                    $collected[] = sprintf("\t\"%s.%s\" -> \"%s.%s\";\n", $tableName, $parts[0], $parts[1], $parts[2]);
                }
            }
        }

        if ($subgraphs) {
            printf("\t}\n");
        }
    }
}

if ($subgraphs) {
    foreach ($collected as $relation) {
        print $relation;
    }
}

if ($print) {
    print "}\n";
}
