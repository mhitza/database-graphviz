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

namespace DatabaseGraphviz\Command;

use DatabaseGraphviz\Generator\Record;
use DatabaseGraphviz\Generator\Simple;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use DomainException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
    public const TYPE_SIMPLE = 'simple';
    public const TYPE_RECORD = 'record';

    protected static $defaultName = 'generate';

    /**
     * @var string[]
     */
    protected array $tables = [];

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Generate a graphviz DOT language graph definition for current database')
            ->addArgument('type', InputArgument::REQUIRED, 'Either "simple" or "record"')
            ->addArgument('dbname', InputArgument::REQUIRED, 'Database name')
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
            ->addOption(
                'port',
                null,
                InputOption::VALUE_OPTIONAL,
                'Database port',
                '3306'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @throws Exception
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var string $databaseName
         */
        $databaseName = $input->getArgument('dbname');

        $connection = DriverManager::getConnection(
            [
                'driver' => 'pdo_mysql',
                'dbname' => $databaseName,
                'host' => $input->getOption('host'),
                'port' => $input->getOption('port'),
                'user' => $input->getOption('user'),
                'password' => $input->getOption('password'),
            ]
        );

        $generator = match ($input->getArgument('type')) {
            self::TYPE_SIMPLE => new Simple($connection, $databaseName),
            self::TYPE_RECORD => new Record($connection, $databaseName),
            default => throw new DomainException('Invalid type specified'),
        };

        $output->writeln($generator->generate());

        return 0;
    }
}
