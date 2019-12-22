<?php


namespace DatabaseGraphviz\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{

    protected static $defaultName = "database-graphviz:generate";

    protected function configure()
    {
        $this
            ->setDescription('Generate a graphviz DOT language graph definition for current database');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return 0;
    }
}