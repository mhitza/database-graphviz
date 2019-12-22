<?php


namespace DatabaseGraphviz\Generator;


use Doctrine\DBAL\Connection;

interface GeneratorInterface
{

    public function __construct(Connection $connection, string $databaseName);

    public function generate();
}