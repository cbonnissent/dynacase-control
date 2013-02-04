<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Phase Class
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 */

class Phase
{
    /**
     * @var DOMElement
     */
    public $xmlNode;
    public $name;
    /**
     * @var Module
     */
    public $module;
    /**
     * @param string $phaseName the name of the phase
     * @param DOMElement $xmlNode XMLNode object
     * @param Module $module object Module
     */
    public function __construct($phaseName, DOMElement $xmlNode, Module $module)
    {
        $this->name = $phaseName;
        $this->xmlNode = $xmlNode;
        $this->module = $module;
    }
    /**
     * Get Process list
     * @return array of object Process
     */
    public function getProcessList()
    {
        require_once ('class/Class.Process.php');
        
        $plist = array();
        
        if (!in_array($this->name, array(
            'pre-install',
            'pre-upgrade',
            'pre-remove',
            'unpack',
            'clean-unpack',
            'remove',
            'param',
            'post-install',
            'post-upgrade',
            'post-remove',
            'post-param',
            'reconfigure',
            'unregister-module',
            'purge-unreferenced-parameters-value'
        ))) {
            return $plist;
        }
        // Special internal hard coded phase
        if ($this->name == 'unregister-module' || $this->name == 'purge-unreferenced-parameters-value' || $this->name == 'unpack' || $this->name == 'clean-unpack') {
            return array(
                new Process(sprintf("<%s><label>Do %s</label></%s>", $this->name, $this->name, $this->name) , $this)
            );
        }
        // Get processes for the phase from module's info.xml
        $phaseNodeList = $this->xmlNode->getElementsByTagName($this->name);
        if ($phaseNodeList->length <= 0) {
            return $plist;
        }
        $phaseNode = $phaseNodeList->item(0);
        
        $processes = $phaseNode->childNodes;
        foreach ($processes as $process) {
            if (!($process instanceof DomComment)) {
                /**
                 * @var DOMElement $process
                 */
                $xmlStr = $process->ownerDocument->saveXML($process);
                
                $xmlStr = ltrim($xmlStr); // @TODO While making this loop, there are occurencies of $xmlStr composed of spaces only. Check why. The ltrim correct this but should not by required.
                if ($xmlStr != '') {
                    $plist[] = new Process($xmlStr, $this);
                }
            }
        }
        
        return $plist;
    }
    /**
     * Get Process by rank in list and xml
     * @return object Process or false in case of error
     * @param int $rank
     */
    public function getProcess($rank)
    {
        $processList = $this->getProcessList();
        
        return $processList[$rank];
    }
}
