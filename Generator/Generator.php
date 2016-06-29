<?php

namespace Spyrit\PropelCrudGeneratorBundle\Generator;

require 'vendor/autoload.php';
use \Twig_Loader_Filesystem;

class Generator
{
    protected static function create($filename, $templatesDir, $outputDir, $variables) {
        // create a generator
        $generator = new Generator();
        $generator->setTemplateDirs([$templatesDir]);
        $generator->setTemplateName($filename);
        $generator->setOutputDir($outputDir);
        $outputName = str_replace(
                ['%custom%', '%Custom%'],
                [$variables['ObjectBaseName'], $variables['ObjectCamelCase']],
                $filename);
        $outputName = preg_replace('/.twig$/i', '', $outputName, 1);
        $generator->setOutputName($outputName);

        // always regenerate classes even if they exist -> no cache
        $generator->setMustOverwriteIfExists(true);
        $generator->setVariables($variables);
        $generator->writeOnDisk();
    }

    public static function convertToCamelCase($string, $capitalizeFirstCharacter = false)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        if (!$capitalizeFirstCharacter) {
            $str[0] = strtolower($str[0]);
        }
        return $str;
    }

    protected function setMustOverwriteIfExists($boolean) {
        $this->mustOverwriteIfExists = $boolean;
    }

    protected function setOutputDir($dir) {
        $this->output_dir = $dir;
    }

    protected function setOutputName($name) {
        $this->output_name = $name;
    }

    protected function setTemplateDirs($dir) {
        $this->template_dirs = $dir;
    }

    protected function setTemplateName($name) {
        $this->template_name = $name;
    }

    protected function setVariables($variables) {
        $this->variables = $variables;
    }

    protected function writeOnDisk()
    {
        $path = $this->output_dir . DIRECTORY_SEPARATOR . $this->output_name;
        $dir  = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!file_exists($path) || (file_exists($path) && $this->mustOverwriteIfExists)) {
            file_put_contents($path, $this->getCode());
        }
    }

    protected function getCode()
    {
        $loader = new \Twig_Loader_Filesystem($this->template_dirs);
        $twig = new \Twig_Environment($loader, [
            'autoescape' => false,
            'strict_variables' => true,
            'debug' => true,
        ]);

        $template = $twig->loadTemplate($this->template_name);

        $variables = $this->variables;

        return $template->render($variables);
    }

    /**
     * Generate a CRUD for class $className with attributes $attributes
     * using the templates from $templates_dir and writing the generated
     * files to $output_dir.
     *
     * @param string $className
     * @param array $attributes
     * @param string $templates_dir
     * @param string $output_dir
     */
    public static function configure_and_run($objectBaseName, $fields, $templates_dir, $output_dir) {
        $objectCamelCase = self::convertToCamelCase($objectBaseName, true);
        $applicationBaseName = 'app';
        $applicationCamelCase = self::convertToCamelCase($applicationBaseName, true);
        $coreBundleBaseName = 'core';
        $coreBundleCamelCase = self::convertToCamelCase($coreBundleBaseName, true).'Bundle';
        $currentBundleBaseName = 'admin';
        $currentBundleCamelCase = self::convertToCamelCase($currentBundleBaseName, true).'Bundle';
        $controllerCamelCase = $objectCamelCase.'Controller';
        $queryCamelCase = $objectCamelCase.'Query';
        $typeCamelCase = $objectCamelCase.'Type';

        $variables = [
            'ApplicationBaseName' => $applicationBaseName,
            'ApplicationCamelCase' => $applicationCamelCase,
            'ControllerCamelCase' => $controllerCamelCase,
            'CoreBundleBaseName' => $coreBundleBaseName,
            'CoreBundleCamelCase' => $coreBundleCamelCase,
            'CurrentBundleBaseName' => $currentBundleBaseName,
            'CurrentBundleCamelCase' => $currentBundleCamelCase,
            'ObjectBaseName' => $objectBaseName,
            'ObjectCamelCase' => $objectCamelCase,
            'QueryCamelCase' => $queryCamelCase,
            'TypeCamelCase' => $typeCamelCase,
            'fields' => $fields,
            'types' => array_unique($fields),
        ];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($templates_dir));

        while($it->valid()) {
            if (!$it->isDir()) {
                self::create($it->getSubPathName(), $templates_dir, $output_dir, $variables);
            }
            $it->next();
        }
    }
}
