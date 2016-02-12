<?php

namespace Spyrit\PropelCrudGeneratorBundle\Command;

use Spyrit\PropelCrudGeneratorBundle\Generator\Generator;
use Propel\Bundle\PropelBundle\Command\GeneratorAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class Command extends GeneratorAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('crud:generate')
            ->setDescription('Generate a CRUD')
            ->addArgument('class_name', InputArgument::REQUIRED, 'The class for which the CRUD will be generated.')
            ->addArgument('bundle', InputArgument::REQUIRED, 'The bundle in which the class is defined.')
            ->addOption('templates_path', null, InputOption::VALUE_OPTIONAL, 'The path containing the templates.', __DIR__.'/../Resources/templates')
            ->addOption('output_path', null, InputOption::VALUE_OPTIONAL, 'The path in which the files will be generated.', __DIR__.'/../Generated')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $templates_path = $input->getOption('templates_path');
        $output_path = $input->getOption('output_path');
        $output->writeln("Templates path: ".$templates_path.".");
        $output->writeln("Output path: ".$output_path.".");

        $class_name = $input->getArgument('class_name');
        if ($input->hasArgument('bundle') && '@' === substr($input->getArgument('bundle'), 0, 1)) {
            $bundle = $this->getContainer()->get('kernel')->getBundle(substr($input->getArgument('bundle'), 1));
        }
        $classTable = null;
        if ($schemas = $this->getSchemasFromBundle($bundle)) {
            foreach ($schemas as $fileName => $array) {
                foreach ($this->getDatabasesFromSchema($array[1]) as $database) {
                    foreach ($database->getTables() as $table) {
                        if ($table->getName() == $class_name) {
                            $classTable = $table;
                        }
                    }
                }
            }
        }
        if($classTable) {
            $output->writeln("\tFound table ".$classTable->getName()." with ".count($classTable->getColumns())." columns.");
            $fields = array();
            foreach ($classTable->getColumns() as $column) {
                if (!$column->isPrimaryKey()) {
                    $output->writeln("\tFound column ".$column->getName().".");
                    $fields[$column->getName()] = 'text';
                }
            }
            Generator::configure_and_run($class_name, $fields, $templates_path, $output_path);
            $output->writeln("Done generating CRUD for ".$class_name.".");
        } else {
            $output->writeln("Could not find table for ".$class_name.".");
        }
    }
}