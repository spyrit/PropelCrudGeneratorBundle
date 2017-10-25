<?php

namespace Spyrit\PropelCrudGeneratorBundle\Command;

use Propel\Bundle\PropelBundle\Command\AbstractCommand;
use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Manager\ModelManager;
use Spyrit\PropelCrudGeneratorBundle\Generator\Generator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class Command extends AbstractCommand
{
    protected function configure()
    {
        parent::configure();

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

        $baseName = strtolower($input->getArgument('class_name'));
        $phpName = ucfirst($input->getArgument('class_name'));
        $kernel = $this->getApplication()->getKernel();

        $bundle = null;
        if ($input->hasArgument('bundle') && '@' === substr($input->getArgument('bundle'), 0, 1)) {
            $bundle = $this->getContainer()->get('kernel')->getBundle(substr($input->getArgument('bundle'), 1));
        }
        $this->setupBuildTimeFiles();
        $schemas = $this->getFinalSchemas($kernel, $bundle);
        if (!$schemas) {
            $output->writeln(sprintf('No <comment>*schemas.xml</comment> files found in bundle <comment>%s</comment>.', $bundle->getName()));
            return;
        }

        $manager = $this->getModelManager($input, $schemas);

        foreach ($manager->getDataModels() as $dataModel) {
            foreach ($dataModel->getDatabases() as $database) {
                foreach ($database->getTables() as $table) {
                    if ($table->getPhpName() <> $phpName) {
                        continue;
                    } else {
                        $classTable = $table;
                        break;
                    }
                }
            }
        }

        if ($classTable) {
            $output->writeln("\tFound table ".$classTable->getPhpName()." with ".count($classTable->getColumns())." columns.");
            $columns = [];
            foreach ($classTable->getColumns() as $column) {
                if (!$column->isPrimaryKey()) {
                    $output->writeln("\tFound column ".$column->getLowercasedName().".");
                    $columns[$column->getLowercasedName()] = 'Text';
                }
            }
            Generator::configure_and_run($baseName, $columns, $templates_path, $output_path);
            $output->writeln("Done generating CRUD for ".$baseName.".");
        } else {
            $output->writeln("Could not find table for ".$baseName.".");
        }
    }

    /**
     * Get the GeneratorConfig instance to use.
     *
     * @param InputInterface $input An InputInterface instance.
     *
     * @return GeneratorConfig
     */
    protected function getGeneratorConfig(InputInterface $input)
    {
        $generatorConfig = null;

        if (null !== $input->getOption('platform')) {
            $generatorConfig['propel']['generator']['platformClass'] = $input->getOption('platform');
        }

        return ;
    }

    /**
     * Get the ModelManager to use.
     *
     * @param InputInterface $input   An InputInterface instance.
     * @param array          $schemas A list of schemas.
     *
     * @return ModelManager
     */
    protected function getModelManager(InputInterface $input, array $schemas)
    {
        $schemaFiles = array();
        foreach ($schemas as $data) {
            $schemaFiles[] = $data[1];
        }

        $manager = new ModelManager();
        $manager->setFilesystem(new Filesystem());
        $manager->setGeneratorConfig(new GeneratorConfig($this->getCacheDir().'/propel.json'));
        $manager->setSchemas($schemaFiles);

        return $manager;
    }
}