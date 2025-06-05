<?php

/**
 * @version     4.4.0
 * @package     com_ra_mailman
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 05/01/24 CB Created
 * 08/01/24 CB use SubscriptionHelper
 * 14/11/24 CB duffRecords
 * 26/05/25 CB checkSchema / ra_reports
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Controller;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\MVC\Controller\FormController;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\SubscriptionHelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\UserHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\SchemaHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class SystemController extends FormController {

    protected $back;
    protected $objApp;
    protected $toolsHelper;

    public function __construct() {
        parent::__construct();
        $this->toolsHelper = new ToolsHelper;
        $this->objApp = Factory::getApplication();
        $this->back = 'administrator/index.php?option=com_ra_tools&view=dashboard';

        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
    }

    // echo $this->toolsHelper->showQuery($sql);

    public function checkRenewals() {
        // invoked from dashboard
// initialise the Helper classes
        $Mailhelper = new Mailhelper();
        $toolsHelper = new ToolsHelper;
        $back = 'administrator/index.php?option=com_ra_mailman&view=subscriptions';

        /*
         *
         * UPDATE sta_ra_mail_subscriptions SET expiry_date = DATE_ADD(expiry_date,INTERVAL 12 MONTH) WHERE id=1
         * UPDATE sta_ra_mail_subscriptions SET expiry_date = DATE_ADD(created,INTERVAL 12 MONTH) WHERE state=1 and method_id=4
         * UPDATE sta_ra_mail_subscriptions SET expiry_date = DATE_ADD(created,INTERVAL 12 MONTH) WHERE state=1 and list_id=1
         * UPDATE sta_ra_mail_subscriptions SET expiry_date = NULL WHERE expiry_date='0000-00-00 00:00:00'
         */
//==============================================================================
// find Subscription records close to their expiry date
//==============================================================================
// 08/01/24 - next few lines are temporary
        $sql = 'UPDATE `#__ra_mail_subscriptions` SET expiry_date = current_date() WHERE expiry_date IS NULL';
        $toolsHelper->executeCommand($sql);
        $sql = 'UPDATE `#__ra_mail_subscriptions` SET reminder_sent = NULL';
        $toolsHelper->executeCommand($sql);

        $notify_interval = ComponentHelper::getParams('com_ra_mailman')->get('notify_interval');

//        $this->logMessage("R2", 2, "reminders.php: Seeking Subscriptions in " . $notify_interval) . ' days time';
        echo "Seeking Subscriptions in " . $notify_interval . ' days time<br>' . PHP_EOL;

        $sql = "SELECT s.user_id, MIN(datediff(expiry_date, CURRENT_DATE)) ";
        $sql .= "FROM `#__ra_mail_subscriptions` AS s ";
        $sql .= "WHERE (s.state =1) ";
        $sql .= "AND ((datediff(expiry_date, CURRENT_DATE) < " . $notify_interval . ') ';
        $sql .= " AND (s.reminder_sent IS NULL)) ";
        $sql .= "GROUP BY s.user_id ";
        $sql .= "ORDER BY s.user_id ";
        $sql .= 'LIMIT 5';
        //       echo $sql . PHP_EOL;
        $rows = $this->toolsHelper->getRows($sql);
        if ($this->toolsHelper->rows == 0) {
            echo 'None found<br>';
            echo $toolsHelper->backButton($back);
            return;
        }
        echo $this->toolsHelper->rows . ' records found <br>';
//        $toolsHelper->showQuery($sql);
//        $this->logMessage("R3", 3, "Number of Subscriptions due=" . $toolsHelper->rows);
        $sql = 'UPDATE `#__ra_mail_subscriptions` SET reminder_sent=CURRENT_DATE WHERE id=';

        foreach ($rows as $row) {
            echo "id=$row->user_id<br>";
//                $this->logMessage("R4", $row->user_id, "id:" . $row->id . "," . $row->expiry_date);
            if ($Mailhelper->sendRenewal($row->user_id)) {
                echo $row->user_id . ' renewed ' . '<br>';
            } else {
                echo $row->user_id . ' failed ' . '<br>';
            }
        }

        echo '<br>';
        die;
        echo $toolsHelper->backButton($back);
    }

    public function checkRenewalsForList() {

        $sql = 'UPDATE #__ra_mail_subscriptions SET expiry_date = created WHERE state=1 and list_id=2';
        $this->toolsHelper->executeCommand($sql);
        // Sends email renewalks for a single list
        // invoked from the report "Subscription due"
        $back = 'administrator/index.php?option=com_ra_mailman&view=reports';
        $Mailhelper = new Mailhelper();
        $list_id = $this->app->input->getInt('list_id', '1');
        $list_name = $Mailhelper->lookupList($list_id);
        $this->app->enqueueMessage('Renewal emails sent for list ' . $list_name, 'message');
//        $objSubscription = new SubscriptionHelper;
        $sql = 'SELECT user_id, list_id ';
        $sql .= 'FROM `#__ra_mail_subscriptions`  ';
        $sql .= 'WHERE (state =1) ';
        $sql .= 'AND (datediff(expiry_date, CURRENT_DATE) < 0) ';
//        $sql .= ' AND (reminder_sent IS NULL) ';
        $sql .= "AND (list_id=" . $list_id . ') ';
        $sql .= "ORDER BY user_id ";

//        echo $sql . PHP_EOL;
//        die;
        $rows = $this->toolsHelper->getRows($sql);
        if ($this->toolsHelper->rows == 0) {
            echo 'No renewals due for list ' . $list_name . '<br>';
            echo $sql . '<br>';
            echo $this->toolsHelper->backButton($back);
            return;
        }
        echo $this->toolsHelper->rows . ' records found <br>';
//        $toolsHelper->showQuery($sql);

        $objMailhelper = new Mailhelper;
//        $objSubscription = new SubscriptionHelper;

        foreach ($rows as $row) {
            echo "user_id=$row->user_id<br>";

            $objMailhelper->sendRenewal($row->user_id, $list_id);
//                $this->logMessage("R4", $row->user_id, "id:" . $row->id . "," . $row->expiry_date);
//            if ($Mailhelper->sendRenewal($row->user_id, $list_id)) {
//                echo $row->user_id . ' renewed ' . '<br>';
//            } else {
//                echo $row->user_id . ' failed ' . '<br>';
//            }
        }

        echo 'Renewals for' . $list_id . '<br>';
        die;
        $this->setRedirect('/administrator/index.php?option=com_ra_mailman&task=reports.showDue');
    }

    public function checkSchema() {
        $toolsHelper = new ToolsHelper;
        if (!$toolsHelper->isSuperuser()) {
            return;
        }
        $helper = New SchemaHelper;
// table ra_import_reports
        $details = '(
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `date_phase1` DATETIME NOT NULL ,
            `date_phase2` DATETIME NULL ,
            `date_completed` DATETIME NULL ,
            `method_id` int(11) NOT NULL,
            `list_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `num_records` INT  NOT NULL DEFAULT "0",
            `num_errors` INT  NOT NULL DEFAULT "0",
            `num_users` INT  NOT NULL DEFAULT "0",
            `num_subs` INT  NOT NULL DEFAULT "0",
            `num_lapsed` INT  NOT NULL DEFAULT "0",
            `ip_address` VARCHAR(255)  NULL  DEFAULT "",
            `error_report` TEXT  DEFAULT NULL,
            `new_users` TEXT DEFAULT NULL,
            `new_subs` TEXT DEFAULT NULL,
            `lapsed_members` TEXT DEFAULT NULL,
            `input_file` VARCHAR(255) NOT NULL,
            `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT NULL DEFAULT "0",
            `modified` DATETIME NULL DEFAULT NULL,
            `modified_by` INT NULL DEFAULT "0",
            `checked_out_time` DATETIME NULL  DEFAULT NULL ,
            `checked_out` INT NULL,
            `state` TINYINT(1)  NULL  DEFAULT 1,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;';
        $helper->checkTable('ra_import_reports', $details);
        $target = 'administrator/index.php?option=com_ra_tools&view=dashboard';
        echo $toolsHelper->backButton($target);
    }

    public function duffRecords() {
        // one-off clean up to tidy the database
        ToolBarHelper::title('System maintenance');
        $toolsHelper = new ToolsHelper;
        if (!$toolsHelper->isSuperuser()) {
            return;
        }

        $sql = 'SELECT s.id, ';
        $sql .= 'u.name AS `Subscriber`, ';
        $sql .= 'DATE(s.created) AS `Created`, ';
        $sql .= 's.modified, s.expiry_date, s.reminder_sent,';
        if ($list_id == 0) {
            $sql .= 'l.group_code AS `group`, l.name AS `list`, ';
        }
        $sql .= 'm.name AS `Method`, ma.name as Access ';
        $sql .= 'FROM `#__ra_mail_subscriptions` AS s ';
        $sql .= 'INNER JOIN `#__ra_mail_methods` AS `m` ON m.id = s.method_id ';
        $sql .= 'LEFT JOIN `#__users` AS `u` ON u.id = s.user_id ';
        $sql .= 'LEFT JOIN `#__ra_mail_lists` AS `l` ON l.id = s.list_id ';
        $sql .= 'LEFT JOIN #__ra_mail_access AS ma ON ma.id = s.record_type ';
        $sql .= 'LEFT JOIN #__ra_profiles as p ON p.id = s.user_id ';
        $sql .= 'WHERE u.id IS NULL ';
        $sql .= 'OR l.id IS NULL ';
        $sql .= 'OR m.id IS NULL ';
        $sql .= 'OR ma.id IS NULL ';
        $sql .= 'OR p.id IS NULL ';
        $rows = $toolsHelper->getRows($sql);
        if ($toolsHelper->rows == 0) {
            echo 'No unmatched subscriptions ' . '<br>';
        } else {
            echo 'Deleting unmatched subscriptions ' . '<br>';
            $toolsHelper->showQuery($sql);
            foreach ($rows as $row) {
                $sql_audit = 'SELECT id FROM #__ra_mail_subscriptions_audit ';
                $sql_audit .= 'WHERE object_id=' . $row->id;
                $audit_rows = $toolsHelper->getRows($sql_audit);
                foreach ($audit_rows as $audit_row) {
                    $sql = 'DELETE FROM  #__ra_mail_subscriptions_audit ';
                    $sql .= 'WHERE object_id=' . $audit_row->id;
                    echo $sql . '<br>';
                    $toolsHelper->executeCommand($sql);
                }

                $sql = 'DELETE FROM  #__ra_mail_subscriptions ';
                $sql .= 'WHERE id=' . $row->id;
                echo $sql . '<br>';
                $toolsHelper->executeCommand($sql);
            }
        }

        // see if any unlinked audit records for subscriptions
        $sql = 'SELECT a.id, a.object_id, a.created ';
        $sql .= 'FROM #__ra_mail_subscriptions_audit AS a ';
        $sql .= 'LEFT JOIN `#__ra_mail_subscriptions` AS `s` ON s.id = a.object_id ';
        $sql .= 'WHERE s.id IS NULL ';
        $sql .= 'ORDER BY a.id ';
        echo $sql . '<br>';
        $rows = $toolsHelper->getRows($sql);
        if ($toolsHelper->rows == 0) {
            echo 'No unmatched mapping records ' . '<br>';
        } else {
            $toolsHelper->showQuery($sql);
            foreach ($rows as $row) {
                $sql = 'DELETE FROM #__ra_mail_subscriptions_audit ';
                $sql .= 'WHERE id=' . $row->id;
                echo $sql . '<br>';
                $toolsHelper->executeCommand($sql);
            }
        }

        // see if any unlinked records for usergroup_map
        $sql = 'SELECT m.user_id, m.group_id FROM #__user_usergroup_map as m ';
        $sql .= 'LEFT JOIN #__users as u ON u.id = m.user_id ';
        $sql .= 'WHERE u.id IS NULL ';
        $sql .= 'ORDER BY m.user_id ';
        $rows = $toolsHelper->getRows($sql);
        if ($toolsHelper->rows == 0) {
            echo 'No unmatched mapping records ' . '<br>';
        } else {
            $toolsHelper->showQuery($sql);
            foreach ($rows as $row) {
                $sql = 'DELETE FROM  #__user_usergroup_map ';
                $sql .= 'WHERE user_id=' . $row->user_id;
                echo $sql . '<br>';
                $toolsHelper->executeCommand($sql);
            }
        }

        echo '<br>';
        $target = 'administrator/index.php?option=com_ra_tools&view=dashboard';
        echo $toolsHelper->backButton($target);
    }

    function logMessage($record_type, $ref, $message) {
        $db = Factory::getDbo();

// Create a new query object.
        $query = $db->getQuery(true);
// Prepare the insert query.
        $query
                ->insert($db->quoteName('#__ra_logfile'))
                ->set('record_type =' . $db->quote($record_type))
                ->set('ref = ' . $db->quote($record_type))
                ->set('message =' . $db->quote($message));

// Set the query using our newly populated query object and execute it.
        $db->setQuery($query);
        $db->execute();
    }

    function test() {
        /*
          <a href="default.asp"><img src="smiley.gif" alt="HTML tutorial" style="width:42px;height:42px;"></a>
         */
        $params = ComponentHelper::getParams('com_ra_mailman');
        // 20/04/25 Including https gives error
        //$header = '<a target="_blank" href="https://' . $params->get('website') . '" >';
        $header = '<a target="_blank" href="' . $params->get('website') . '" >';

        $logo = '/images/com_ra_mailman/' . $params->get('logo_file');
        if (file_exists(JPATH_ROOT . $logo)) {
            $header .= '<img src="' . $logo . '" alt="Logo" style="width:';
            $header .= $params->get('width') . 'px;height:' . $params->get('height') . 'px; ';
            $header .= 'float: right;" />';
            $header .= '</a>';
        } else {
            echo JPATH_ROOT . $logo . ' not found<br>';
            $header .= 'Website</a>';
        }
//        $header = '<img src="https://www.stokeandnewcastleramblers.org.uk/images/ra-images/2022brand/Logo%2090px.png" alt="Logo" width="90" height="90" style="float: right;" />';

        echo $header . '<br>';
        echo '<br>';
        $toolsHelper = new ToolsHelper;
        $target = 'administrator/index.php?option=com_ra_tools&view=dashboard';
        echo $toolsHelper->backButton($target);
        return;

        $date = Factory::getDate();
//        $date = Factory::getDate('now', Factory::getConfig()->get('offset'));
//        $date->modify('+1 year');
//        echo substr($date->toSql(), 0, 10);
//        return;
        echo 'info<i class="icon-info"></i><br>';
        echo 'refresh<i class="icon-refresh"></i><br>';
        echo 'envelope<i class="icon-envelope"></i><br>';
        echo 'repeat<i class="fa--repeat"></i><br>';
        echo 'calendar<i class="fa-solid fa-calendar-days"></i><br>';
        echo '<i class="fa-calendar-days"></i><br>';
        echo 'OK<br>';

        $body = 'Date <b>' . HTMLHelper::_('date', $today, 'd M y') . '</b><br>';
        $body .= 'Time <b>' . HTMLHelper::_('date', $today, 'h.i') . '</b><br>';
        $objSubscription = new SubscriptionHelper;
        echo $body . '<br>';

        $objSubscription->list_id = 2;  // test
        $objSubscription->user_id = 965; // Samsung
        if (!$objSubscription->getData()) {
            echo $objSubscription->message;
            return;
        }

        echo "1 before $objSubscription->expiry_date<br>";

        $objSubscription->resetExpiry();
        if ($objSubscription->update()) {
            if (!$objSubscription->getData()) {
                echo $objSubscription->message;
                return;
            }
            echo "2 after reset $objSubscription->expiry_date<br>";
        } else {
            echo $objSubscription->message;
            return;
        }


        $objSubscription->bumpExpiry();
        echo "3 after bump $objSubscription->expiry_date<br>";
        if ($objSubscription->update()) {
            if (!$objSubscription->getData()) {
                echo $objSubscription->message;
                return;
            }
            echo "4 after update $objSubscription->expiry_date<br>";
        } else {
            echo $objSubscription->message;
            return;
        }

        echo "Renewal<br>";
        echo "1 before $objSubscription->reminder_sent<br>";
        $objSubscription->setReminder();
        echo "1 before update, $objSubscription->reminder_sent<br>";
        if ($objSubscription->update()) {
            if (!$objSubscription->getData()) {
                echo $objSubscription->message;
                return;
            }
            echo "2 after set $objSubscription->reminder_sent<br>";
        } else {
            echo $objSubscription->message;
            return;
        }

        $objSubscription->setReminder();
        echo "2 after reset $objSubscription->reminder_sent<br>";

        //    $objSubscription->cancel();
//        $objSubscription = new SubscriptionHelper;
//        $objUserHelper = new UserHelper;
//        $objUserHelper->blockUser(934);   // Webbie
    }

}
