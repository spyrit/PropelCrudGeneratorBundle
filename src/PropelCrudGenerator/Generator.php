<?php

namespace PropelCrudGenerator;

require 'vendor/autoload.php';
use \Twig_Loader_Filesystem;

class Generator
{
    public static function create($filename, $templatesDir, $variables) {
        // create a generator
        $generator = new Generator();
        $generator->setTemplateDirs(array($templatesDir));
        $generator->setTemplateName($filename);
        $generator->setOutputDir('Generated');
        $outputName = str_replace(
                array('%custom%', '%Custom%'),
                array($variables['ObjectBaseName'], $variables['ObjectCamelCase']),
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

    public function setMustOverwriteIfExists($boolean) {
        $this->mustOverwriteIfExists = $boolean;
    }

    public function setOutputDir($dir) {
        $this->output_dir = $dir;
    }

    public function setOutputName($name) {
        $this->output_name = $name;
    }

    public function setTemplateDirs($dir) {
        $this->template_dirs = $dir;
    }

    public function setTemplateName($name) {
        $this->template_name = $name;
    }

    public function setVariables($variables) {
        $this->variables = $variables;
    }

    public function writeOnDisk()
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

    public function getCode()
    {
        $loader = new \Twig_Loader_Filesystem($this->template_dirs);
        $twig = new \Twig_Environment($loader, array(
                        'autoescape' => false,
                        'strict_variables' => true,
                        'debug' => true,
        ));

        $template = $twig->loadTemplate($this->template_name);

        $variables = $this->variables;

        return $template->render($variables);
    }
}

// Customize this part:
$objectBaseName = 'my_object';
$objectCamelCase = Generator::convertToCamelCase($objectBaseName, true);
$applicationBaseName = 'app';
$applicationCamelCase = Generator::convertToCamelCase($applicationBaseName, true);
$coreBundleBaseName = 'core';
$coreBundleCamelCase = Generator::convertToCamelCase($coreBundleBaseName, true).'Bundle';
$currentBundleBaseName = 'admin';
$currentBundleCamelCase = Generator::convertToCamelCase($currentBundleBaseName, true).'Bundle';
$controllerCamelCase = $objectCamelCase.'Controller';
$queryCamelCase = $objectCamelCase.'Query';
$typeCamelCase = $objectCamelCase.'Type';

// Attributes of the object, from schema.
// @FIXME: should be provided by Propel or Doctrine
$fields = array(
                'field1' => 'text',
                'field2' => 'text',
                'field3' => 'text'
);

// set common variables
$variables = array(
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
);

$templatesDir = __DIR__.'/../../Resources/templates';

$it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($templatesDir));

while($it->valid()) {
    if (!$it->isDir()) {
        Generator::create($it->getSubPathName(), $templatesDir, $variables);
    }
    $it->next();
}