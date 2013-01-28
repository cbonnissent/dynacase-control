<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * Crontab class
 *
 * This class allows you to manipulate a user crontab by registering
 * and unregistering cron files
 *
 * @author Anakeen
 * @version $Id: Class.Crontab.php,v 1.2 2009/01/16 15:51:35 jerome Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */

class Crontab
{
    private $user = NULL;
    private $crontab = '';
    private $wiff_root = '';
    private $errorMsg = "";
    
    public function __construct($user = NULL)
    {
        $this->user = $user;
        $this->wiff_root = getenv('WIFF_ROOT');
        return $this;
    }
    
    public function getErrors()
    {
        return $this->errorMsg;
    }
    
    public function setUser($user)
    {
        $this->user = $user;
        return $this->user;
    }
    
    public function unsetUser()
    {
        $this->user = NULL;
        return $this->user;
    }
    
    private function load()
    {
        $cmd = 'crontab -l';
        if ($this->user != NULL) {
            $cmd.= ' -u ' . escapeshellarg($this->user);
        }
        $cmd.= ' 2> /dev/null';
        
        $ph = popen($cmd, 'r');
        if ($ph === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error popen");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error popen";
            return FALSE;
        }
        
        $crontab = stream_get_contents($ph);
        if ($crontab === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error stream_get_contents");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error stream_get_contents";
            return FALSE;
        }
        
        $this->crontab = $crontab;
        
        return $crontab;
    }
    
    private function save()
    {
        include_once "include/lib/Lib.System.php";
        
        $tmp = WiffLibSystem::tempnam(null, 'crontab');
        if ($tmp === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error creating temporary file");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error creating temporary file";
            return FALSE;
        }
        
        $ret = file_put_contents($tmp, $this->crontab);
        if ($ret === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error writing content to file '" . $tmp . "'");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error writing content to file '" . $tmp . "'";
            return FALSE;
        }
        
        $cmd = 'crontab';
        if ($this->user != NULL) {
            $cmd.= ' -u ' . escapeshellarg($this->user);
        }
        $cmd.= ' ' . escapeshellarg($tmp);
        $cmd.= ' > /dev/null 2>&1';
        
        system($cmd, $ret);
        if ($ret != 0) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error saving crontab '" . $tmp . "'");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error saving crontab '" . $tmp . "'";
            return FALSE;
        }
        
        return $this->crontab;
    }
    
    public function registerFile($file)
    {
        $crontab = file_get_contents($file);
        if ($crontab === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error reading content from file '" . $file . "'");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error reading content from file '" . $file . "'";
            return FALSE;
        }
        
        $crontab = "# BEGIN:CONTROL_CRONTAB:" . $this->wiff_root . ":" . $file . "\n" . $crontab . "\n" . "# END:CONTROL_CRONTAB:" . $this->wiff_root . ":" . $file . "\n";
        
        $activeCrontab = $this->load();
        if ($activeCrontab === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error reading active crontab");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error reading active crontab";
            return FALSE;
        }
        
        if (preg_match('/^#\s+BEGIN:CONTROL_CRONTAB:' . preg_quote($this->wiff_root, '/') . ':' . preg_quote($file, '/') . '.*?#\s+END:CONTROL_CRONTAB:' . preg_quote($this->wiff_root, '/') . ':' . preg_quote($file, '/') . '$/ms', $activeCrontab) === 1) {
            //print "Removing existing crontab\n";
            $tmpCrontab = preg_replace('/^#\s+BEGIN:CONTROL_CRONTAB:' . preg_quote($this->wiff_root, '/') . ':' . preg_quote($file, '/') . '.*?#\s+END:CONTROL_CRONTAB:' . preg_quote($this->wiff_root, '/') . ':' . preg_quote($file, '/') . '$/ms', '', $activeCrontab);
            if ($tmpCrontab === NULL) {
                error_log(__CLASS__ . "::" . __FUNCTION__ . " Error removing existing registered crontab");
                $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error removing existing registered crontab";
                return FALSE;
            }
            $activeCrontab = $tmpCrontab;
        }
        
        $activeCrontab.= $crontab;
        $this->crontab = $activeCrontab;
        
        $ret = $this->save();
        if ($ret === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error saving crontab");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error saving crontab";
            return FALSE;
        }
        
        return $this->crontab;
    }
    
    public function unregisterFile($file)
    {
        $activeCrontab = $this->load();
        if ($activeCrontab === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error reading active crontab");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error reading active crontab";
            return FALSE;
        }
        
        $tmpCrontab = preg_replace('/^#\s+BEGIN:CONTROL_CRONTAB:' . preg_quote($this->wiff_root, '/') . ':' . preg_quote($file, '/') . '.*?#\s+END:CONTROL_CRONTAB:' . preg_quote($this->wiff_root, '/') . ':' . preg_quote($file, '/') . '$/ms', '', $activeCrontab);
        if ($tmpCrontab === NULL) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error unregistering crontab '" . $this->wiff_root . ":" . $file . "' from active crontab");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error unregistering crontab '" . $this->wiff_root . ":" . $file . "' from active crontab";
            return FALSE;
        }
        
        $this->crontab = $tmpCrontab;
        
        $ret = $this->save();
        if ($ret === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error saving crontab");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error saving crontab";
            return FALSE;
        }
        
        return $this->crontab;
    }
    
    public function listAll()
    {
        $crontabs = $this->getActiveCrontab();
        if ($crontabs === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error retrieving active crontabs");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error retrieving active crontabs";
            return FALSE;
        }
        
        print "\n";
        print "Active crontabs\n";
        print "---------------\n";
        print "\n";
        foreach ($crontabs as $crontab) {
            print "Crontab: " . $crontab['file'] . "\n";
            print "--8<--\n" . $crontab['content'] . "\n-->8--\n\n";
        }
        
        return TRUE;
    }
    
    public function getActiveCrontab()
    {
        $activeCrontab = $this->load();
        if ($activeCrontab === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error reading active crontab");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error reading active crontab";
            return FALSE;
        }
        
        $ret = preg_match_all('/^#\s+BEGIN:CONTROL_CRONTAB:' . preg_quote($this->wiff_root, '/') . ':(.*?)\n(.*?)\n#\s+END:CONTROL_CRONTAB:' . preg_quote($this->wiff_root, '/') . ':\1/ms', $activeCrontab, $matches, PREG_SET_ORDER);
        if ($ret === FALSE) {
            error_log(__CLASS__ . "::" . __FUNCTION__ . " Error in preg_match_all");
            $this->errorMsg.= ($this->errorMsg ? '\n' : '') . __CLASS__ . "::" . __FUNCTION__ . " Error in preg_match_all";
            return FALSE;
        }
        
        $crontabs = array();
        foreach ($matches as $match) {
            array_push($crontabs, array(
                'file' => $match[1],
                'content' => $match[2]
            ));
        }
        
        return $crontabs;
    }
    
    public function isAlreadyRegister($file)
    {
        $crontabs = $this->getActiveCrontab();
        foreach ($crontabs as $crontab) {
            if ($crontab["file"] == $file) return true;
        }
        return false;
    }
}
?>