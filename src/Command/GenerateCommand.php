<?php


namespace DatabaseGraphviz\Command;


use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{

    protected static $defaultName = "generate";

    protected $tables = [];

    protected function configure()
    {
        $this
            ->setDescription('Generate a graphviz DOT language graph definition for current database')
            ->addArgument('database-name', InputArgument::REQUIRED)
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

        $output->writeln(sprintf("digraph %s {", $databaseName));
        $output->writeln($this->dumpTables($connection));
        $output->writeln($this->dumpRelationships($connection));
        $output->writeln('}');

        return 0;
    }


    protected function dumpTables(Connection $connection)
    {
        $statement = $connection->query("SHOW TABLES");
        while ($row = $statement->fetch()) {
            $tableName = current($row);

            $this->tables[] = $tableName;

            yield "\t$tableName;";
        }
    }


    protected function dumpRelationships(Connection $connection)
    {
        foreach ($this->tables as $tableName) {
            $createStatement = $connection->query(sprintf("SHOW CREATE TABLE %s", $tableName));
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