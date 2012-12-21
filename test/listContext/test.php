<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 */

include_once ('../test.php');

$wiff = WIFF::getInstance();
$contextList = $wiff->getContextList();

if ($contextList === false) {
    echo "FAILED:\n";
    echo $wiff->errorMessage . "\n";
    exit(1);
}

displayContextList($contextList);

exit(0);
?>
