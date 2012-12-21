<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 */

$WIFF_ROOT = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
$include_dir = $WIFF_ROOT . DIRECTORY_SEPARATOR . 'include';
if (!is_dir($include_dir)) {
    printf("Error: could not find include directory '%s'.\n", $include_dir);
    exit(1);
}

function usage($me)
{
    print <<<EOT
Usage:

  php $me <contextName> <moduleName>


EOT;
    
}

$me = array_shift($argv);
if (count($argv) != 2) {
    usage($me);
    exit(1);
}
$contextName = array_shift($argv);
if ($contextName === false) {
    usage($me);
    exit(1);
}
$moduleName = array_shift($argv);
if ($moduleName === false) {
    usage($me);
    exit(1);
}

set_include_path(get_include_path() . PATH_SEPARATOR . $include_dir);

putenv('WIFF_ROOT=' . $WIFF_ROOT);

require_once ('class/Class.WIFF.php');

function __autoload($class_name)
{
    require_once 'class/Class.' . $class_name . '.php';
}

$wiff = WIFF::getInstance();
if ($wiff === false) {
    printf("Error getting WIFF instance: %s\n", $wiff->errorMessage);
    exit(1);
}

$context = $wiff->getContext($contextName);
if ($context === false) {
    printf("Could not find a context with name '%s'.\n", $contextName);
    exit(1);
}

$module = $context->getModule($moduleName);
if ($module === false) {
    printf("Could not find a module with name '%s' in context '%s'.\n", $moduleName, $contextName);
    exit(1);
}

printf("Uninstalling module '%s' in context '%s'.\n", $moduleName, $contextName);

$ret = $context->removeModule($moduleName);
if ($ret === false) {
    printf("Error removing module '%s' form context '%s': %s\n", $moduleName, $contextName, $context->errorMessage);
    exit(1);
}

$ret = $context->deleteFilesFromModule($moduleName);
if ($ret === false) {
    printf("Error removing files from module '%s': %s\n", $moduleName, $context->errorMessage);
    exit(1);
}

$ret = $context->deleteManifestForModule($moduleName);
if ($ret === false) {
    printf("Error removing manifest for module '%s': %s\n", $moduleName, $context->errorMessage);
    exit(1);
}

printf("Done.\n");

exit(0);
