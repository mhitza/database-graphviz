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
use Doctrine\DBAL\DBALException;
use Generator;

class Simple implements GeneratorInterface
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
     * @var string[]
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
     * @return Generator<string>
     *
     * @throws DBALException
     */
    public function generate()
    {
        yield sprintf('digraph %s {', $this->databaseName);

        yield from $this->getTables();

        yield from $this->getRelationships();

        yield '}';
    }

    /**
     * @return Generator<string>
     *
     * @throws DBALException
     */
    protected function getTables()
    {
        $statement = $this->connection->query('SHOW TABLES');
        /*
         * @psalm-suppress MixedAssignment
         */
        while ($row = $statement->fetch()) {
            /**
             * @var array  $row
             * @var string $tableName
             */
            $tableName = current($row);

            $this->tables[] = $tableName;

            yield "\t$tableName;";
        }
    }

    /**
     * @return Generator<string>
     *
     * @throws DBALException
     */
    protected function getRelationships()
    {
        foreach ($this->tables as $tableName) {
            $createStatement = $this->connection->query(sprintf('SHOW CREATE TABLE %s', $tableName));
            /*
             * @psalm-suppress MixedAssignment
             */
            while ($row = $createStatement->fetch()) {
                /**
                 * @var array $row
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
