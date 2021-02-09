<?php
/**
 * @copyright 2019 Marius Ghita <mhitza@gmail.com>
 *
 * This file is part of database-graphviz
 *
 * database-graphviz is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * database-graphviz is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with database-graphviz.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace DatabaseGraphviz\Generator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Statement;
use Exception;
use Generator;

class Record implements GeneratorInterface
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
     * @var array<string>
     */
    private $tables = [];

    public function __construct(Connection $connection, string $databaseName)
    {
        $this->connection = $connection;
        $this->databaseName = $databaseName;
    }

    /**
     * @return Generator<string>
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function generate()
    {
        yield sprintf('digraph %s {', $this->databaseName);
        yield sprintf("\tnode [shape=record];");
        yield sprintf("\trankdir=LR;");

        $this->collectTables();

        yield from $this->getTablesWithRowsAndRelationships();

        yield '}';
    }

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function collectTables(): void
    {
        /** @var Statement $statement */
        $statement = $this->connection->executeQuery('SHOW TABLES');
        /*
         * @psalm-suppress MixedAssignment
         */
        while ($tableName = $statement->fetchOne()) {
            $this->tables[] = $tableName;
        }
    }

    /**
     * @return Generator<string>
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function getTablesWithRowsAndRelationships()
    {
        foreach ($this->tables as $tableName) {
            /** @var Statement $createStatement */
            $createStatement = $this->connection->executeQuery(sprintf('SHOW CREATE TABLE %s', $tableName));
            /*
             * @psalm-suppress MixedAssignment
             */
            while ($row = $createStatement->fetchAssociative()) {
                /**
                 * @var array<string, string> $row
                 */
                if (false === isset($row['Create Table'])) {
                    // skip views
                    continue;
                }
                /**
                 * @var string $description
                 * @psalm-suppress MixedArrayAccess
                 */
                $description = $row['Create Table'];
                $lines = explode("\n", $description);

                $columns = [];
                $relationships = [];
                foreach ($lines as $line) {
                    if (preg_match('/^\s+`(.*?)`/', $line, $matches)) {
                        $columns[] = $matches[1];
                    }
                    if (preg_match('/FOREIGN KEY \(.*\) REFERENCES .*?\(.*\)/', $line, $matches)) {
                        $relationship = str_replace(['FOREIGN KEY ', 'REFERENCES ', '(', ')', '`'], '', $matches[0]);
                        $parts = explode(' ', $relationship);
                        $relationships[] = sprintf("\t%s:%s -> %s:%s;\n", $tableName, $parts[0], $parts[1], $parts[2]);
                    }
                }

                /*
                 * NOTE: Just by using the record type there can be no distinction between say a "header" of the record
                 * and all the other rows, as such we just push the table name on top of the record
                 */
                array_unshift($columns, $tableName);

                yield sprintf("\t%s [label=\"%s\"];", $tableName, implode('|', array_map(
                    function ($column) {
                        return sprintf('<%s> %s', $column, $column);
                    },
                    $columns
                )));

                yield implode('', $relationships);
            }
        }
    }
}
