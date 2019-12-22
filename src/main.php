<?php

$print = true;

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

    if ($print) {
        print "\t$tableName;\n";
    }
}

foreach ($tables as $tableName => $value) {
    $createStatement = $connection->query(sprintf("SHOW CREATE TABLE %s", $tableName));
    while ($row_ = $createStatement->fetch()) {
        $description = $row_['Create Table'];
        $lines = explode("\n", $description);
        foreach ($lines as $line) {
            if (preg_match('/FOREIGN KEY \(.*\) REFERENCES .*?\(.*\)/', $line, $matches)) {
                $relationship = str_replace(['FOREIGN KEY ', 'REFERENCES ', '(', ')', '`'], '', $matches[0]);

                if ($print) {
                    $parts = explode(' ', $relationship);
                    printf("\t%s -> %s [label=\"%s\"]; \n", $tableName, $parts[1], $parts[0]);
                }
            }

        }
    }
}

if ($print) {
    print "}\n";
}
