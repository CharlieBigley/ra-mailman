<?php

/**
 * Helper to load member data from the Central Office API feed
 * Usually run from a batch job via cron, but can be invoked from the dashboard
 * with menu option "Refresh members".
 *
 * If run on-line, messages are display directly; if in batch mode, messages are
 * stored in arrays for later display
 *
 * @version    4.6.0
 * @package    com_ra_mailman
 * @author     charles
 * 18/04/26 CB created
 */

namespace Ramblers\Component\Ra_mailman\Site\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\JsonHelper;

class LoadHelper {

    protected $db;
    protected $app;
    protected $toolsHelper;
    private $counter = 0;
    public $online_mode = false;
    public $comments;
    public $comment_count;
    public $errors;

    public function __construct() {
        $this->app = Factory::getApplication();
        $this->db = Factory::getDbo();
        $this->jsonHelper = new JsonHelper;
        $this->mailHelper = new MailHelper;
        $this->toolsHelper = new ToolsHelper;
    }

    private function doesMemberExist($salesforceId) {
        $sql = 'SELECT id FROM #__ra_profiles WHERE salesforceId="' . $salesforceId . '"';
        return $this->toolsHelper->getItem($sql);    
    }

// doesMemberExist ($salesforceId)


    public function getJson($code){
    $endpoint = 'ramblers.org.uk';
    $endpoint .= '/api/groups/' . $code . '/members';
    $members = array();
    $response = $this->jsonHelper->getJson($endpoint);
    if ($response === false){
        $messages[] = 'Failed to retrieve members for ' . $code;
        $this->logMessage('Failed to retrieve members for ' . $code, '3');
        return false;
    }

    return $members;
    }

    public function loadMembers($code) {
        $this->logMessage('Processing ' . $code,1);    
        $messages = array();
        $members = $this->getJson($code);
        if ($members === false){
            return false;
        }
        $count = $this->processMembers($members);
        $messages[] = $count . ' records processed for ' . $code;
        if ($count > 0){
            $messages[] = 'New records ' . $this->count_new . ', Updated records ' . $this->count_updated;
        }
        return;
    }   
    /**
     *   Store a log entry
     */
    public function logMessage($text, $record_type = '3') {

        $query = $this->db->getQuery(true);

        $query->insert('#__ra_logfile')
                ->set("record_type = " . $this->db->quote($record_type))
                ->set("message = " . $this->db->quote($text))
                ->set("sub_system = 'RA Mailman'" )
                ->set("ref = " . $this->db->quote('LoadMembers'))
        ;

        $result = $this->db->setQuery($query)->execute();
    }

// logMessage ($text , $code = 0)

    public function processMembers($members){
    $count = 0;
    foreach($members as $member){
        $count++;

    }
    return $count;
    }


}
