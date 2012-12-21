<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 */

include_once ('../test.php');
/**
 * @var WIFF $wiff
 * @var Context $ctx
 */
$wiff = WIFF::getInstance();
$ctx = $wiff->getContext('ctx1');
if ($ctx === false) {
    echo "ERROR\n";
    echo $wiff->errorMessage . "\n";
    exit(1);
}

$phase = 'install';

$installedList = $ctx->getInstalledModuleListWithUpgrade(true);
$module = false;
foreach ($installedList as $mod) {
    if ($mod->name == 'dynacase-platform') {
        $module = $mod;
        break;
    }
}
if ($module === false) {
    printf("Should have foud an installed 'dynacase-platform' module.\n");
    exit(1);
}
echo sprintf("%s %s-%s (avail = %s)\n", $mod->name, $mod->version, $mod->release, $mod->availableversion, $mod->availableversionrelease);
if (!$mod->canUpdate) {
    printf("Should have found an update for 'dynacase-platform'.\n");
    exit(1);
}
printf("OK found an update for 'dynacase-platform' with '%s-%s'.\n", $mod->updateName, $mod->availableversionrelease);

if ($module->updateName != 'dynacase-core') {
    printf("Unexpected updateName '%s'.", $module->updateName);
    exit(1);
}

printf("Computing dependencies for update '%s':\n", $module->updateName);
$depsList = $ctx->getModuleDependencies(array(
    $module->updateName
));
if ($depsList === false) {
    echo "ERROR\n";
    echo $ctx->errorMessage . "\n";
    exit(1);
}
echo "--- dependencies ---\n";
$r = '';
foreach ($depsList as $dep) {
    $r.= sprintf("%s %s-%s for %s\n", $dep->name, $dep->version, $dep->release, ($dep->needphase != '') ? $dep->needphase : $phase);
}
print $r;
echo "--- dependencies ---\n";
$expected = <<<'EOT'
dynacase-platform 3.2.1-0 for replaced
dynacase-core 3.3.0-0 for upgrade

EOT;
if ($r != $expected) {
    echo "ERROR: Unexpected dependencies.\n";
    exit(1);
}
echo "OK found " . count($depsList) . " expected dependencies.\n";
echo "\n";

exit(0);
