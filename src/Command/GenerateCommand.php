<?php


namespace DatabaseGraphviz\Command;


use DatabaseGraphviz\Generator\Record;
use DatabaseGraphviz\Generator\Simple;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            ->addArgument('database-name', InputArgument::REQUIRED)
            ->addArgument('type', InputArgument::REQUIRED, 'Either "simple" or "record"')
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $databaseName = $input->getArgument('database-name');

        $config = new Configuration();
        $connection = DriverManager::getConnection(
            [
                "driver" => "pdo_mysql",
                "host" => "127.0.0.1",
                "user" => "root",
                "dbname" => $databaseName,
                "password" => "password"
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