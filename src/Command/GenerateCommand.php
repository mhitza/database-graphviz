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
 *
 */

namespace DatabaseGraphviz\Command;


use DatabaseGraphviz\Generator\Record;
use DatabaseGraphviz\Generator\Simple;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
    const TYPE_SIMPLE = 'simple';
    const TYPE_RECORD = 'record';

    protected static $defaultName = "generate";

    protected $tables = [];

    protected function configure()
    {
        $this
            ->setDescription('Generate a graphviz DOT language graph definition for current database')
            ->addArgument('type', InputArgument::REQUIRED, 'Either "simple" or "record"')
            ->addOption(
                'dbname',
                null,
                InputOption::VALUE_REQUIRED,
                'Database name'
            )
            ->addOption(
                'user',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database user',
                'root'
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database password',
                ''
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database host',
                '127.0.0.1'
            )
        ;

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $databaseName = $input->getOption('dbname');

        $config = new Configuration();
        $connection = DriverManager::getConnection(
            [
                "driver" => "pdo_mysql",
                "dbname" => $databaseName,
                "host" => $input->getOption('host'),
                "user" => $input->getOption('user'),
                "password" => $input->getOption('password')
            ],
            $config
        );

        $generator = null;
        switch ($input->getArgument('type')) {
            case self::TYPE_SIMPLE:
                $generator = new Simple($connection, $databaseName);
                break;
            case self::TYPE_RECORD:
                $generator = new Record($connection, $databaseName);
                break;
            default:
                throw new \DomainException('Invalid type specified');
        }

        $output->writeln($generator->generate());

        return 0;
    }
}