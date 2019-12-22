<?php


namespace DatabaseGraphviz\Generator;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Generator;

class Simple
{

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var string
     */
    private $databaseName;
    /**
     * @var array
     */
    private $tables = [];

    /**
     * Simple constructor.
     * @param Connection $connection
     * @param string $databaseName
     */
    public function __construct(Connection $connection, string $databaseName)
    {
        $this->connection = $connection;
        $this->databaseName = $databaseName;
    }


    /**
     * @return Generator
     * @throws DBALException
     */
    public function generate()
    {
        yield sprintf("digraph %s {", $this->databaseName);

        yield from $this->getTables();

        yield from $this->getRelationships();

        yield '}';
    }

    /**
     * @return Generator
     * @throws DBALException
     */
    protected function getTables()
    {
        $statement = $this->connection->query("SHOW TABLES");
        while ($row = $statement->fetch()) {
            $tableName = current($row);

            $this->tables[] = $tableName;

            yield "\t$tableName;";
        }
    }


    protected function getRelationships()
    {
        foreach ($this->tables as $tableName) {
            $createStatement = $this->connection->query(sprintf("SHOW CREATE TABLE %s", $tableName));
            while ($row = $createStatement->fetch()) {
                $description = $row['Create Table'];
                $lines = explode("\n", $description);

                foreach ($lines as $line) {
                    if (preg_match('/FOREIGN KEY \(.*\) REFERENCES .*?\(.*\)/', $line, $matches)) {
                        $relationship = str_replace(['FOREIGN KEY ', 'REFERENCES ', '(', ')', '`'], '', $matches[0]);

                        $parts = explode(' ', $relationship);

                        yield sprintf("\t%s -> %s [label=\"%s\"];", $tableName, $parts[1], $parts[0]);
                    }
                }
            }
        }
    }
}