<?php

/**
 * @version     4.4.0
 * @package     com_ra_mailman
 * @copyright   Copyright (C) 2020. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Charlie Bigley <webmaster@bigley.me.uk> - https://www.developer-url.com
 * Invoked from controllers/dataload to import Users, will be passed 4 parameters:
 *  method_id, list_id, processing and filename
 * Data Type: 3 Download from Insight Hub
 *            4 Export from MailChimp
 *            5 Simple csv file
 * Processing: 0 = report only
 *             1 = Update database
 *
 * 06/12/22 CB Created from com ramblers as LoadUsers
 * 13/11/23 CB link to group Registered as well as Public
 * 09/12/23 CB change validation of email, also check unique username (validEmail instead of checkEmail)
 * 25/03/24 CB don't delete from profiles_audit
 * 18/09/24 CB check for blank filename (processing of Mailchimp may not work)
 * 10/10/24 CB set up return codes from processFile
 * 28/10/24 CB check profile is not present before creating it
 * 20/10/24 CB when processing lapsed members, correct bounce date
 * 04/11/24 CB show subscription count, require rest if creating user from front end
 * 14/11/24 CB create profile; change diagnostic messages
  16/11/24 CB blockUser and purgeUser
 * 12/02/25 CB replace getIdentity with Factory::getApplication()->getSession()->get('user')
 * 14/04/25 CB trim spaces from beginning and end of input fields
 * 18/05/25 CB correct columns for names and email address
 * 26/05/25 CB import report
 */

namespace Ramblers\Component\Ra_mailman\Site\Helpers;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Table\Table;
use \Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\Userhelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\SubscriptionHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Ra_mailman helper class
 */
class UserHelper {

// These six variable are defined by the calling program
    public $method_id;
    public $group_code;
    public $list_id;
    public $processing;
    public $filename;
    public $report_id;
// These are available after processing
    public $error;
// These variables are used internally
    public $email;
    public $name;
    public $preferred_name;
    public $user_id;
    protected $open;
    protected $toolshelper;
    protected $objMailHelper;
    protected $error_count = 0;
    protected $error_report;
    protected $new_users = array();
    protected $new_subs = array();
    protected $lapsed_count = 0;
    protected $lapsed_members = array();
    protected $record_count = 0;
    protected $record_type;
    protected $subscription_count = 0;
    protected $users_created = 0;
    protected $users_required;

    public function __construct() {
        $this->record_count = 0;
        $this->users_created = 0;

// When subscribing, always subscribe as User (rather than an Author)
        $this->record_type = 1;
        $this->objMailHelper = new Mailhelper;
        $this->toolshelper = new ToolsHelper;
    }

    protected function addJoomlaUser() {
        $password = self::randomkey(8);
        $data = array(
            "name" => $this->name,
            "username" => $this->email,
            "password" => $password,
            "password2" => $password,
            "email" => $this->email,
            "reset" => 1
        );

        $user = new User();
//Write to database
        if (!$user->bind($data)) {
            throw new Exception("Could not bind data. Error: " . $user->getError());
        }
        if (!$user->save()) {
            throw new Exception("Could not save user. Error: " . $user->getError());
        }

        return $user->id;
    }

    public function blockUser($user_id) {
// Blocks and renames User record
        $sql = 'SELECT u.name, p.preferred_name ';
        $sql .= 'FROM `#__users` AS u ';
        $sql .= 'LEFT JOIN #__ra_profiles AS p on p.id = u.id ';
        $sql .= 'WHERE u.id=' . $user_id;
        $item = $this->toolshelper->getItem($sql);
        $message = 'Renamed ' . $item->name . '/' . $item->preferred_name . ' to User' . $user_id;

        $email_domain = ComponentHelper::getParams('com_ra_mailman')->get('email_domain');

        $user = 'User' . $user_id;
        $email = 'user' . $user_id . '@' . $email_domain;
        $sql = 'UPDATE `#__users` SET ';
        $sql .= 'block=1, ';
        $sql .= 'name="' . $user . '", ';
        $sql .= 'username="' . $email . '", ';
        $sql .= 'email= "' . $email . '" ';
        $sql .= 'WHERE id=' . $user_id;
//        echo $sql . '<br>';
        $this->toolshelper->executeCommand($sql);

        $sql = 'UPDATE `#__ra_profiles` SET ';
        $sql .= 'preferred_name="' . $user . '" ';
        $sql .= 'WHERE id=' . $user_id;
        echo $sql . '<br>';
        $this->toolshelper->executeCommand($sql);
// Cancel any subscriptions
        $sql = 'SELECT id, list_id ';
        $sql .= "FROM `#__ra_mail_subscriptions` ";
        $sql .= "WHERE user_id=" . $user_id;
//        echo $sql . '<br>';
        $objSubscription = new SubscriptionHelper;
        foreach ($rows as $row) {
            echo "id=$row->id, expires $row->expiry_date<br>";
            $objSubscription->list_id = $row->list_id;
            $objSubscription->user_id = $user_id;
            $objSubscription->cancel();
        }
        echo $message . '<br>';
    }

    public function checkEmail($email, $username, $group_code) {
// Returns True or an error message
        $sql = 'SELECT u.id, u.name, u.registerDate, p.home_group ';
        $sql .= 'FROM #__users AS u ';
        $sql .= 'LEFT JOIN #__ra_profiles as p ON p.id = u.id ';
        $sql .= 'WHERE u.email="' . $email . '"';
        $item = $this->toolshelper->getItem($sql);
        if (!is_null($item)) {
            if ($item->id > 0) {
                $message = $email . '/' . $item->name . '/' . $item->home_group . ' was registered ' . $item->registerDate . '.';
                $message .= ' You can just logon to update your subscriptions.';
                return $message;
            }
        }

        $sql = 'SELECT u.id, u.name, u.registerDate, p.home_group ';
        $sql .= 'FROM #__users AS u ';
        $sql .= 'LEFT JOIN #__ra_profiles as p ON p.id = u.id ';
        $sql .= 'WHERE u.name="' . $username . '" ';
        $sql .= 'AND p.home_group="' . $group_code . '" ';
//        echo $sql . '<br>';
//        die($sql);
        $item = $this->toolshelper->getItem($sql);
        if (!is_null($item)) {
            if ($item->id > 0) {
                return 'This Name is already in use for ' . $item->email . '/' . $item->home_group . ' registered ' . $item->registerDate;
            }
        }
        return True;
    }

    public function checkExistingUser($email, $username, $group_code) {
// Invoked from the front end if administrator is trying to register a new user
// Returns ID of existing user, if one found
        $sql = 'SELECT u.id ';
        $sql .= 'FROM #__users AS u ';
        $sql .= 'LEFT JOIN #__ra_profiles as p ON p.id = u.id ';
        $sql .= 'WHERE u.email="' . $email . '"';
        $sql .= 'AND u.name = "' . $username . '"';
        $sql .= 'AND p.home_group = "' . $group_code . '"';
        return $this->toolshelper->getValue($sql);
    }

    private function createPreferredName() {
// Created a default preferred_name as First name + first characters of Surname
        $parts = explode(' ', $this->name);
        $last = count($parts) - 1;  // in case more than 2 names given
        $this->preferred_name = $parts[0] . ' ' . substr($parts[$last], 0, 1);
    }

    public function createProfile() {
//    Create a record in ra_profiles
//      Check that record not already present
//      should not be an existing records, but if there is, update it anyway



        $sql = 'SELECT id FROM #__ra_profiles WHERE id=' . $this->user_id;
        $record_exists = $this->toolshelper->getValue($sql);
        $date = Factory::getDate('now', Factory::getConfig()->get('offset'))->toSql(true);
        if ($record_exists > 0) {
            $user = Factory::getApplication()->getSession()->get('user');
            $sql = 'UPDATE #__ra_profiles SET ';
            $sql .= 'home_group=' . $db->quote($this->group_code) . ', ';
            $sql .= 'preferred_name=' . $db->quote($this->preferred_name) . ', ';
            $sql .= 'modified=' . $db->quote($date) . ', ';
            $sql .= 'modified_by=' . $db->quote($user->id) . ' ';
            $sql .= 'WHERE id=' . $this->user_id;
            $this->toolshelper->executeCommand($sql);
        } else {
// See if user is logged in (i.e not self registering)
            if ($user->id == 0) {
                $created = $this->user_id;
            } else {
                $created = $user_id;
            }
// Prepare the insert query.
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
            $query->set('id =' . $db->quote($this->user_id))
                    ->set('home_group =' . $db->quote($this->group_code))
                    ->set('groups_to_follow  =' . $db->quote($group_code))
                    ->set('preferred_name =' . $db->quote($this->preferred_name))
                    ->set('created =' . $db->quote($date))
                    ->set('created_by =' . $db->quote($created))
                    ->insert($db->quoteName('#__ra_profiles'));
//           echo $db->replacePrefix($query) . '<br>';
//           die;
            $db->setQuery($query);
            $result = $db->execute();
            if (!$result) {
                $this->error = 'Unable to create Profile record for ' . $this->group_code . ' ' . $this->preferred_name;
            }
            return $result;
        }
    }

    public function createProfile_1() {
//    Create a record in ra_profiles
        $db = Factory::getDbo();
        $user = Factory::getApplication()->getSession()->get('user');
        $query = $db->getQuery(true);
        $date = Factory::getDate('now', Factory::getConfig()->get('offset'))->toSql(true);
        $query->insert($db->quoteName('#__ra_profiles'))
                ->set('id =' . $db->quote($this->user_id))
                ->set('home_group =' . $db->quote($this->group_code))
                ->set('groups_to_follow  =' . $db->quote($this->group_code))
                ->set('preferred_name =' . $db->quote($this->preferred_name))
                ->set('created =' . $db->quote($date))
                ->set('created_by =' . $db->quote($user->id))
        ;
//        echo $db->replacePrefix($query) . '<br>';
        $db->setQuery($query);
        return $db->execute();
    }

    public function createProfile_3($user_id, $group_code) {
// Fails to find Instance of table
        $data = array(
            'id' => $user_id,
            'home_group' => $db->quote($group_code),
            'groups_to_follow' => $db->quote($group_code),
            'preferred_name' => $db->quote($this->preferred_name),
        );
        $table = Table::getInstance('Profile', 'Table');
        if (!$table->bind($data)) {
            echo 'could not bind<br>';
            return false;
        }
        if (!$table->check()) {
            echo 'could not validate<br>';
            return false;
        }
        if (!$table->store(true)) {
            echo 'could not store<br>';
            return false;
        }
    }

    public function createUser() {
        /*
         * This uses Joomla objects to create a User record (and send them a message about the new password)
         * It is used from the front-end (controllers/profile) from view profiles
         * However, if used from the back end it seems only to work the first time it is invoked
         * 23/10/23 add field sendEmail, pass array of groups rather than call linkUser
         */

        if ($this->name == 'Email Address') {
// this is the first line of a MailChimp export
            return;
        }
        $this->user_id = 0;

        $password = '$2y$10$PCUXW4xpLTsLGmdJJ4NqUuuNSnpq7fBkZxB4XiqUNFq8tP1Ha3FHa'; // unspecifiedpassword
// This code only seems to work for the first user
        $user = new User();   // Write to database
        $data = array(
            "name" => $this->name,
            "username" => $this->email,
            "password" => $password,
            "password2" => $password,
            "sendEmail" => '1',
            "group" => array('1', '2'), // Public & Registered
            "require_reset" => 1,
            "email" => $this->email
        );
        if (!$user->bind($data)) {
            $this->error = 'Could not validate data - Error: ' . $user->getError();
            return false;
        }

        if (!$user->save()) {
// throw new Exception("Could not save user. Error: " . $user->getError());
            $this->error = 'Could not create user - Error: ' . $user->getError();
            return false;
        }
        $this->user_id = $user->id;
//        $this->linkUser();
        Factory::getSession()->clear('user', "default");
        return true;
    }

    public function createUserDirect($front_end = '0') {
// writes a record to the users table
// If invoked from the front-end, $front_end will have value of 1:
//    User will require a reset
//    Notification message generated
//    Notification email will be send
// and the user will need to activate themselves by resetting their password
// From the back end, requireReset = 0
        if ($this->name == 'Email Address') {
// this is the first line of a MailChimp export
            return;
        }
        $this->user_id = 0;

        $date = Factory::getDate();
        $params = '{"admin_style":"","admin_language":"","language":"","editor":"","timezone":""}';
        $password = '$2y$10$PCUXW4xpLTsLGmdJJ4NqUuuNSnpq7fBkZxB4XiqUNFq8tP1Ha3FHa'; // unspecifiedpassword
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

// Prepare the insert query.
        $query
                ->insert($db->quoteName('#__users'))
                ->set('name =' . $db->quote($this->name))
                ->set('username =' . $db->quote($this->email))
                ->set('email =' . $db->quote($this->email))
                ->set('password =' . $db->quote($password))
                ->set('registerDate =' . $db->quote($date->toSQL()))
                ->set("activation =''")
                ->set('params =' . $db->quote($params))
                ->set("otpKey =''")
                ->set("otep =''")
                ->set('requireReset=' . $db->quote($front_end))
        ;
//        echo $query . '<br>';
//      Set the query using our newly populated query object and execute it.
        $db->setQuery($query);
        $db->execute();
// $db_insertid can be flakey
//        $this->user_id = $db->insertid();
// Factory::getApplication()->enqueueMessage('Unable to create User record for ' . $this->group_code . ' ' . $this->name, 'Error');
        $user_id = $this->lookupUser();
        if ($user_id > 0) {
            $this->user_id = $user_id;
            $this->linkUser(1);  // Public
            $this->linkUser(2);  // Registered
            if ($front_end == '1') {
                Factory::getApplication()->enqueueMessage('Created MailMan user record ' . $user_id . ' for ' . $this->group_code . ' ' . $this->name, 'Info');
                $this->sendEmail();
            }
            return true;
        }
        $this->error = 'Unable to create User record for ' . $this->group_code . ' ' . $this->name;
        die;
        return false;
    }

//protected function linkUser($group_id) {
    public function linkUser($group_id) {
//  Links User to given group
        $return == true;
        $db = Factory::getDbo();
// Check for existing record added 28/10/24 - should not be necessary
        $sql = 'SELECT COUNT(user_id) FROM ' . $db->quoteName('#__user_usergroup_map');
        $sql .= ' WHERE user_id=' . $db->quote($this->user_id) . ' AND group_id=' . $db->quote($group_id);
        $count = (int) $this->toolshelper->getValue($sql);
//        $db = Factory::getDbo();
        if ($count == 0) {
            $query = $db->getQuery(true);
            $query
                    ->insert($db->quoteName('#__user_usergroup_map'))
                    ->set('user_id =' . $db->quote($this->user_id))
                    ->set('group_id=' . $db->quote($group_id));
            $db->setQuery($query);
//        echo $query . '<br>';
            $return = $db->execute();
            if ($return == false) {
                $this->error = 'Unable to link ' . $this->user_id . ' to ' . $group_id;
                Factory::getApplication()->enqueueMessage('Unable to link MailMan user ' . $group_id, 'Warning');
            }
        }
        return $return;
    }

    protected function lookupUser() {
        $this->user_id = 0;
        $sql = 'SELECT id, name FROM #__users WHERE email="' . $this->email . '"';
//        echo $sql . '<br>';
        $item = $this->toolshelper->getItem($sql);
        $user_id = (int) $item->id;
        if ($user_id > 0) {
            $this->name = $item->name;
            $this->user_id = $item->id;
        }
        return $user_id;
    }

    protected function parseLine($data) {
        /*
         * Sets up the internal fields this->name, this->email etc
         * The format of the line depends on the type of data being loaded
         */
        switch ($this->method_id) {
            case 3:     // Download from Insight Hub
// First record is just column headings
                if ($this->record_count == 1) {
                    echo $this->record_count . ': Ignoring header row<br>';
                    return 0;
                } else {
                    $response = true;
                    $validation_message = '';
                    $this->name = trim($data[10]) . ' ' . trim($data[11]);
                    if ($this->name == ' ') {
                        $this->error_count++;
                        $validation_message = '<b>Record has no name' . "</b><br>";
                        echo '<b>Record ' . $this->record_count . ' has no name' . "</b><br>";
                        $response = false;
                    }
                    $this->group_code = trim($data[25]);
                    if (!$this->group_code = $data[25]) {
                        $this->error_count++;
                        echo '<b>' . $this->name . ' is in ' . $data[25] . ', not in Group ' . $this->group_code . "</b><br>";
                        $response = false;
                    }
                    $this->email = trim($data[19]);
                    if ($this->email == '') {
                        $this->error_count++;
                        $validation_message = '<b>Record  has no email</b>, name=' . $this->name . '<br>';
                        echo '<b>Record ' . $this->record_count . ' has no email</b>, name=' . $this->name . '<br>';
                        $response = false;
                    }
//                   if (!$this->validEmailFormat()) {
//                       $this->error_count++;
//                       echo "User $this->name: email address '$this->email' is considered invalid<br>";
//                       $response = false;
//
                    if ($validation_message !== '') {
                        $this->error_report .= $this->record_count . ': ' . implode(',', $data) . '<br>';
                        $this->error_report .= $validation_message . '<br>';
                    }
                    return $response;
                }

            case 4:  // Mailchimp
// First record is just column headings
                if ($this->record_count == 1) {
                    echo $this->record_count . ': Ignoring header row<br>';
                    return 0;
                } else {
                    $response = true;
                    $this->name = trim($data[1]) . ' ';
                    $this->email = trim($data[0]);
                    if ($data[2] == '') {
                        $this->name .= $data[4];
                    } else {
                        $this->name .= $data[2];
                    }

                    if (trim($this->name) == '') {
                        $this->error_count++;
                        echo '<b>Record ' . $this->record_count . ' has no name' . "</b><br>";
                        $response = false;
                    }
                    $this->email = trim($data[0]);
                    if ($this->email == '') {
                        $this->error_count++;
                        echo '<b>First column (email) is blank' . "</b><br>";
                        $response = false;
                    }
                    if (!$this->validEmailFormat()) {
                        $this->error_count++;
                        echo "User $this->name: email address '$this->email' is considered invalid<br>";
                        $response = false;
                    }
                    return $response;
                }
            case 5:    // simple csv file
                if ($this->record_count == 1) {
                    echo 'Ignoring header row<br>';
                    return 0;
                } else {
                    $return = 1;
                    $this->group_code = $data[0];
                    $this->name = $data[1];
                    $this->email = $data[2];
                    if ($this->group_code == '') {
                        $this->error_count++;
                        echo '<b>First column (Group code) is blank' . "</b><br>";
                        $return = 0;
                    }

                    if ($this->name == '') {
                        $this->error_count++;
                        $message = '<b>Second column (name) is blank</b>';
                        $message .= ', email=' . $this->email;
                        echo $message . "<br>";
                        $return = 0;
                    }

                    if ($this->email == '') {
                        $this->error_count++;
                        echo '<b>Third column (email) is blank' . "</b><br>";
                        $return = 0;
                    }
                    return $return;
                    return $this->validEmailFormat();
                }
        }
    }

    public function processFile() {
//        die(' processing = ' . $this->processing . ', filename = ' . $this->filename);
        if (JDEBUG) {
            $diagnostic = ' processing=' . $this->processing . ', filename=' . $this->filename;
            Factory::getApplication()->enqueueMessage("Helper: " . $diagnostic, 'Message');
        }
        if (!file_exists($this->filename)) {
            echo $this->filename . ' not found';
            Factory::getApplication()->enqueueMessage("Helper: " . $this->filename . ' not found', 'Error');
            return 0;
        }
        if (substr(JPATH_ROOT, 14, 6) == 'joomla') {
            echo '<h4>deleting test data</h4> ';
            $this->purgeTestData();
        }

        $sql = "Select group_code, name, record_type from `#__ra_mail_lists` "
                . "WHERE id='" . $this->list_id . "'";
        $item = $this->toolshelper->getItem($sql);
        $this->group_code = $item->group_code;
        $title = $item->group_code . ' ' . $item->name;
        if ($item->record_type == 'O') {
            $this->open = true;
        } else {
            $this->open = false;
            $title .= ' (Closed list)';
        }

        if ($this->processing == 1) {
            echo '<h2>Processing ';
        } else {
            echo '<h2>Validating ';
        }
        if ($this->method_id == 3) {
            echo 'Members from corporate feed';
        } elseif ($this->method_id == 4) {
            echo 'MailChimp export';
        } elseif ($this->method_id == 5) {
            echo 'CSV';
        } else {
            echo 'Type=' . $this->method_id . 'Not recognised';
        }

        echo '<h4>List=' . $title . '<br>';
        echo 'File=' . $this->filename . '</h4>';
        $this->processRecords();
        echo '<br>' . $this->record_count . ' records read<br>';
        if ($this->error_count > 0) {
            echo "<b>$this->error_count errors</b><br>";
        }
        echo $this->users_required . ' Users required<br>';
        if ($this->processing == 1) {
            echo $this->users_created . ' Users created<br>';
        } else {
            echo ($this->subscription_count + $this->users_required) . ' Subscriptions required<br>';
        }
        if (($this->processing == 1) AND ($this->method_id == 3)) {
            $this->processLapsers();
        }
        $this->updateReport();
        return true;
    }

    protected function processLapsers() {
        $app = Factory::getApplication();

// Lookup whether Users are to be blocked or deleted
        $members_leave = ComponentHelper::getParams('com_ra_mailman')->get('members_leave');
// Find any members on previous files, but not present on this one
// Set up the date to which current members have been renewed
        $today = date('Y-m-d');
        $bounce_date = date('Y-m-d', strtotime($today . ' + 1 year'));
        $objSubscription = new SubscriptionHelper;

//        echo '+y ' . $bounce_date . '<br>';
// Find subscriptions with renewal date before this
        $sql = "SELECT s.id AS subscription_id, s.expiry_date, l.id AS list_id, ";
        $sql .= "u.id as user_id, u.name AS 'User', u.email AS 'email' ";
        $sql .= 'FROM `#__ra_mail_lists` AS l ';
        $sql .= 'INNER JOIN #__ra_mail_subscriptions AS s ON s.list_id = l.id ';
        $sql .= 'INNER JOIN #__users AS u ON u.id = s.user_id ';
        $sql .= 'WHERE l.id=' . $this->list_id . ' ';
        $sql .= 'AND (datediff("' . $bounce_date . '",s.expiry_date) > 0)  ';
//        $sql .= ' AND s.state=1';  // don't care if they have already unsubscribed
        $sql .= ' AND s.method_id=3';
        $sql .= ' ORDER BY u.id';
        if (JDEBUG) {
//            echo $sql . '<br>';
            $this->toolshelper->showQuery($sql);
        }

        $rows = $this->toolshelper->getRows($sql);
        $this->lapsed_count = $this->toolshelper->rows;
        foreach ($rows as $row) {
            $lapsed_members[] = $row->User . ',' . $row->email . '<br>';
            if ($members_leave == 'B') {
                $this->blockUser($row->user_id);
            } else {
                $this->purgeUser($row->user_id);
            }
        }
        if ($members_leave == 'B') {
            $app->enqueueMessage($this->toolshelper->rows . ' Users blocked', 'info');
        } else {
            $app->enqueueMessage($this->toolshelper->rows . ' Users Purged', 'info');
        }
        $count = $this->toolshelper->getValue('SELECT COUNT(id) FROM #__users');
        $app->enqueueMessage('Total number of Users now =' . $count, 'info');
    }

    protected function processRecords() {
        $this->record_count = 0;
        $this->users_required = 0;
        $this->subscription_count = 0;
        $handle = fopen($this->filename, "r");
        if ($handle == 0) {
            echo 'Unable to open ' . $this->filename . '<br>';
            return 0;
        }
//        die('File ' . $this->filename . ' opened OK');
        $sql_lookup = 'SELECT id FROM #__users WHERE email="';
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $this->record_count++;
            if (JDEBUG) {
                echo $this->record_count . ': ';
            }
            if ($this->record_count == 1) {
                echo 'Ignoring header row<br>';
            } elseif (substr($data[0], 0, 1) == '#') {
                echo 'Ignoring comment ' . $data[0] . ',' . $data[1], '<br>';
            } else {
                /*
                 * After $this->parseLine, the following variables will have been set up:
                 *     $this->group_code
                 *     $this->name
                 *     $this->email
                 */
                if (($this->parseLine($data))) {
                    if (JDEBUG) {
                        echo 'group=' . $this->group_code . ', name=' . $this->name . ', email=' . $this->email . "<br>";
                    }
                    $subscription_required = false;
                    $message = '';
                    $user_id = (int) $this->lookupUser();
                    if ($user_id == 0) {
                        $this->users_required++;
                        $this->new_users[] = $this->name . ',' . $this->email;
                        $message .= 'User ' . $this->name . ' <b>not present</b> (' . $this->email . ')';
                        if ($this->processing == 1) {
                            $response = $this->createUserDirect();
                            if ($response) {
                                $user_id = $this->user_id;
                                $message .= ', User created';
                                if (JDEBUG) {
                                    $message .= ', id=' . $user_id;
                                }
                                $this->createPreferredName();
                                $this->createProfile();
                                $this->users_created++;
                                $subscription_required = true;
                            } else {
                                $subscription_required = false;
                                $message .= ', Error creating User ' . $this->name . '/' . $this->email;
                            }
                        }
                    } else {
                        $message .= 'User ' . $this->name . ' exists for ' . $this->email;
                        $method = $this->objMailHelper->isSubscriber($this->list_id, $user_id);
                        if ($method == '') {
                            $this->new_subs[] = $this->name . ',' . $this->email . '<br>';
                            $message .= ', Subscription <b>not present</b>';
                            $subscription_required = true;
                            $this->subscription_count++;
                        } else {
                            $message .= ', subscription exists, method=<b>' . $method . '</b>';
                            $subscription_required = false;
                        }
                    }

                    if (($subscription_required) AND ($this->processing == 1)) {
                        $this->objMailHelper->subscribe($this->list_id, $user_id, $this->record_type, $this->method_id);
//                        echo $this->record_count . ": Subscription created OK" . '<br>';
                        $message .= ', Subscription created';
                    }
                    echo $message . '<br>';
                }
            }
//            if (($this->record_count == 30) AND (substr(JPATH_ROOT, 14, 6) == 'joomla')) {            // Development
//                return;
//            }
        }
        fclose($handle);
    }

    public function purgeTestData() {
// First check user is a Super-User
        if (!$this->toolshelper->isSuperuser()) {
            Factory::getApplication()->enqueueMessage('Invalid access', 'error');
            $target = 'index.php?option=com_ramblers&view=mail_lsts';
            $this->setRedirect(Route::_($target, false));
        }
        /*
          //update field created in ra_profiles
          $sql = 'SELECT id,created,modified from #__ra_profiles';
          $rows = $this->toolshelper->getRows($sql);
          foreach ($rows as $row) {
          echo $row->created . '<br>';
          if (($row->created == '0000-00-00') OR ($row->created == '0000-00-00 00:00:00')) {
          $this->toolshelper->executeCommand('DELETE FROM #__ra_profiles WHERE id=' . $row->id);
          } else {
          if (strlen($row->created) == 10) {
          $new = $row->created . ' 00:00:00';
          $update = 'UPDATE #__ra_profiles SET created="' . $new . '" WHERE id=' . $row->id;
          echo "$update<br>";
          $this->toolshelper->executeCommand($update);
          }
          }
          }
         */
// For test
//$start_user = 1026;  // After Andrea Parton
//$start_subs = 54;
// For dev
        $start_user = 980;  // After Barry Collis
        $start_subs = 12;

// delete details of any emails sent
        $sql = 'DELETE FROM #__ra_mail_recipients WHERE user_id>' . $start_user;
        echo $sql . '<br>';
        $this->toolshelper->executeCommand($sql);

// Delete any subscriptions
        $sql = 'DELETE FROM #__ra_mail_subscriptions_audit WHERE object_id>' . $start_subs;
        echo $sql . '<br>';
        $rows = $this->toolshelper->executeCommand($sql);
        $sql = 'DELETE FROM #__ra_mail_subscriptions WHERE user_id>' . $start_user;
        echo $sql . '<br>';
        $this->toolshelper->executeCommand($sql);

// delete profile audit records
//        $sql = 'DELETE FROM #__ra_profiles_audit WHERE object_id>' . $start_user;
//        echo $sql . '<br>';
//        $this->toolshelper->executeCommand($sql);
// delete the profile record itself
        $sql = 'DELETE FROM #__ra_profiles WHERE id>' . $start_user;
        echo $sql . '<br>';
        $this->toolshelper->executeCommand($sql);

// Delete the users
        $sql = 'DELETE FROM #__user_usergroup_map WHERE user_id>' . $start_user;
        echo $sql . '<br>';
        $this->toolshelper->executeCommand($sql);
        $sql = 'DELETE FROM #__users WHERE id>' . $start_user;
        echo $sql . '<br>';
        $this->toolshelper->executeCommand($sql);

        echo 'Test data deleted<br>';
    }

    public function purgeUser($user_id) {
//    Delete Subscriptions, Subscriptions Audit, Recipients, Profile and User record itself
        $sql = 'SELECT u.name, p.preferred_name ';
        $sql .= 'FROM `#__users` AS u ';
        $sql .= 'LEFT JOIN #__ra_profiles AS p on p.id = u.id ';
        $sql .= 'WHERE u.id=' . $user_id;
        $item = $this->toolshelper->getItem($sql);
        $message = 'Purged all records for ' . $item->name . '/' . $item->preferred_name;
        if (JDEBUG) {
            $message .= ' (' . $user_id . ')';
        }
        if ($user_id > 0) {
// delete details of any emails sent
            $sql = 'DELETE FROM #__ra_mail_recipients WHERE user_id=' . $user_id;
//            echo $sql . '<br>';
            $this->toolshelper->executeCommand($sql);

// Delete any subscriptions
            $sql = 'SELECT id FROM #__ra_mail_subscriptions WHERE user_id=' . $user_id;
            $rows = $this->toolshelper->getRows($sql);
            foreach ($rows as $row) {
                $sql = 'DELETE FROM  #__ra_mail_subscriptions_audit ';
                $sql .= 'WHERE object_id=' . $row->id;
//                echo $sql . '<br>';
                $this->toolshelper->executeCommand($sql);
                $sql = 'DELETE FROM #__ra_mail_subscriptions WHERE id=' . $user_id;
//                echo $sql . '<br>';
                $this->toolshelper->executeCommand($sql);
            }
            $sql = 'DELETE FROM #__ra_profiles WHERE id=' . $user_id;
            $this->toolshelper->executeCommand($sql);

// delete profile audit records
//                $sql = 'DELETE FROM #__ra_profiles_audit WHERE object_id=' . $user_id;
//                echo $sql . '<br>';
//                $this->toolshelper->executeCommand($sql);

            $sql = 'DELETE FROM #__user_usergroup_map WHERE user_id=' . $user_id;
            $this->toolshelper->executeCommand($sql);
            $sql = 'DELETE FROM #__user_notes WHERE id=' . $user_id;
            $this->toolshelper->executeCommand($sql);
            $sql = 'DELETE FROM #__user_profiles WHERE user_id=' . $user_id;
            $this->toolshelper->executeCommand($sql);
            $sql = 'DELETE FROM #__users WHERE id=' . $user_id;
            $this->toolshelper->executeCommand($sql);
        }
        echo $message . '<br>';
    }

    public function sendEmail() {
// send email to the administrator
        $params = ComponentHelper::getParams('com_ra_mailman');
        $notify_id = $params->get('email_new_user', '0');

        if ($notify_id > 0) {
            $sql = 'SELECT email FROM #__users WHERE id=' . $notify_id;
            $to = $this->toolshelper->getValue($sql);
            if ($to == '') {
                Factory::getApplication()->enqueueMessage('Unable to find email address to notify user ' . $notify_id, 'Warning');
                return;
            }
            $title = 'A new user has been registered to MailMan';
            $body = 'New user registration:' . '<br>';
            $today = Factory::getDate('now', Factory::getConfig()->get('offset'));
            $body .= 'Date <b>' . HTMLHelper::_('date', $today, 'd M y') . '</b><br>';
            $body .= 'Time <b>' . HTMLHelper::_('date', $today, 'h.i') . '</b><br>';
            $body .= 'Name <b>' . $this->name . '</b><br>';
            $body .= 'Group <b>' . $this->group_code . '</b><br>';
            $body .= 'Email <b>' . $this->email . '</b><br>';
            $response = $this->objMailHelper->sendEmail($to, $to, $title, $body);
            if ($response) {
                Factory::getApplication()->enqueueMessage('Notification sent to ' . $to, 'Info');
            }
        }
    }

    public function test() {
        $this->list_id = 1;
        $this->processLapsers();

//       $userTable = Table::getInstance('User', 'Table', array());
    }

    private function updateReport() {
        $new_users = implode('<br>', $this->new_users);
        $new_subs = implode('<br>', $this->new_subs);
        $lapsed_members = implode('<br>', $this->lapsed_members);
        /*
         * echo '<br>';
          var_dump($new_users);
          echo '<br>';
          var_dump($new_subs);
          echo '<br>';
          var_dump($lapsed_members);
          echo '<br>';
          $new_users = '';
          $new_subs = '';
          $lapsed_members = '';
         */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->update('#__ra_import_reports')
                ->set("num_records = " . $db->quote($this->record_count))
                ->set("num_errors = " . $db->quote($this->error_count))
                ->set("num_users = " . $db->quote($this->users_required))
                ->set("num_subs = " . $db->quote($this->subscription_count))
                ->set("num_lapsed = " . $db->quote($this->lapsed_count))
                ->set("error_report = " . $db->quote($this->error_report))
                ->set("new_users = " . $db->quote($new_users))
                ->set("new_subs = " . $db->quote($new_subs))
                ->set("lapsed_members = " . $db->quote($lapsed_members))
                ->set("state=1")
                ->where('id=' . $this->report_id);
        if ($this->processing == 1) {
            $date = $this->modified = Factory::getDate('now', Factory::getConfig()->get('offset'))->toSql(true);
            $query->set("date_completed = " . $db->quote($date));
        }
        $result = $db->setQuery($query)->execute();
    }

    private function validEmailFormat() {
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
//            echo "Email address '$this->email' is considered valid.\n";
            return true;
        }
        return false;
    }

    /**
     *   Random Key
     *
     *   @returns a string
     * */
    public static function randomKey($size) {
// Created 26/04/22 from https://stackoverflow.com/questions/1904809/how-can-i-create-a-new-joomla-user-account-from-within-a-script
        $bag = "abcefghijknopqrstuwxyzABCDDEFGHIJKLLMMNOPQRSTUVVWXYZabcddefghijkllmmnopqrstuvvwxyzABCEFGHIJKNOPQRSTUWXYZ";
        $key = array();
        $bagsize = strlen($bag) - 1;
        for ($i = 0;
                $i < $size;
                $i++) {
            $get = rand(0, $bagsize);
            $key[] = $bag[$get];
        }
        return implode($key);
    }

}
