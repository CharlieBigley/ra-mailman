<?php

/*
 * Installation script
 * 24/08/23 CB code for copying cli file
 * 19/08/23 CB correct copying of ra_renewals
 * 14/11/23 CB delete ra_mailshots / author_id
 * 20/11/23 CB mail_lists / group_primary
 * 21/11/23 CB mail_lists / chat_list
 * 02/01/24 CB subscription/expiry date
 * 05/01/24 CB comment out ra_renewals (use online system function)
 * 09/09/24 CB compare version of com_ra_tools
 * 10/09/24 CB new getVersions, new deleteFiles (from version 4.1.8)
 * 11/09/24 CB delete mailshot/ordering
 * 04/10/24 CB ensure ToolsCheck returns true
 * 08/10/24 CB replace JPATH_SITE with JPATH_ROOT, delete files for mailshot view
 * 19/10/24 CB abort installation if com_ra_tools not present
 * 29/04/25 CB getDbVersion and getVersion
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

class Com_Ra_mailmanInstallerScript {

    private $component;
    private $minimumJoomlaVersion = '4.0';
    private $minimumPHPVersion = JOOMLA_MINIMUM_PHP;

    function buildLink($url, $text, $newWindow = 0, $class = "") {
        // N.B. cannot be used from batch programs, because Uri::root() is not available
// copied from ToolsHelper 10/04/25
        $q = chr(34);
        $out = PHP_EOL . "<a ";
//        echo "BuildLink: url = $url, substr=" . substr($url, 0, 4) . ", text=$text, root=" . Uri::root() . "<br>";
        if (!$class == "") {
            $out .= "class=" . $q . $class . $q;
        }
        $out .= " href=" . $q;
        if (substr($url, 0, 4) == "http") {

        } else {
            $out .= Uri::root();    // this seems to be derived from configuration.php/ live_site in the website root
        }
        $out .= $url . $q;
        if ($newWindow) {
            $out .= " target =" . $q . "_blank" . $q;
        } else {
            $out .= " target =" . $q . "_self" . $q;
        }
        $out .= ">";
        if ($text == "")
            $out .= $url;
        else
            $out .= $text;
        $out .= "</a>" . PHP_EOL;
//        echo "BuildLink: output= $out";
        return $out;
    }

    function checkColumn($table, $column, $mode, $details = '') {
//  $mode = A: add the field, using data supplied in $details
//  $mode = U: update the field (keeping name the same), using $details
//  $mode = D: delete the field

        $count = $this->checkColumnExists($table, $column);
        $table_name = $this->dbPrefix . $table;
//        echo 'mode=' . $mode . ': Seeking ' . $table_name . '/' . $column . ', count=' . $count . "<br>";
        if (($mode == 'A') AND ($count == 1)
                OR ($mode == 'D') AND ($count == 0)) {
            return true;
        }
        if (($mode == 'U') AND ($count == 0)) {
            echo 'Field ' . $column . ' not found in ' . $table_name . '<br>';
            return false;
        }

        $sql = 'ALTER TABLE ' . $table_name . ' ';
        if ($mode == 'A') {
            $sql .= 'ADD ' . $column . ' ';
            $sql .= $details;
        } elseif ($mode == 'D') {
            $sql .= 'DROP ' . $column;
        } elseif ($mode == 'U') {
            $sql .= 'CHANGE ' . $column . ' ' . $column . ' ';
            $sql .= $details;
        }
        echo "$sql<br>";
        $response = $this->executeCommand($sql);
        if ($response) {
            echo 'Success';
        } else {
            echo 'Failure';
        }
        echo ' for ' . $table_name . '<br>';
        return $count;
    }

    private function checkColumnExists($table, $column) {
        $config = JFactory::getConfig();
        $database = $config->get('db');
        $this->dbPrefix = $config->get('dbprefix');

        $table_name = $this->dbPrefix . $table;
        $sql = 'SELECT COUNT(COLUMN_NAME) ';
        $sql .= "FROM information_schema.COLUMNS ";
        $sql .= "WHERE TABLE_SCHEMA='" . $database . "' AND TABLE_NAME ='" . $this->dbPrefix . $table . "' ";
        $sql .= "AND COLUMN_NAME='" . $column . "'";
//    echo "$sql<br>";

        return $this->getValue($sql);
    }

    function checkTools() {
        echo 'Checking version of com_ra_tools<br>';
        if (ComponentHelper::isEnabled('com_ra_tools', true)) {
            $tools_versions = $this->getVersions('com_ra_tools');
            echo '<p>com_ra_tools is currently at version ' . $tools_versions->component;
            echo ', database version ' . $tools_versions->db_version . '</p>';
            if (version_compare($tools_versions->component, '5.0.2', '>')) {
                echo 'Greater than 5.0.2, OK<br>';
                return true;
            } else {
                echo 'Minimum required version of tools is 5.0.2<br>';
                return false;
            }
        } else {
            echo 'This component cannot be installed unless component RA Tools (com_ra_tools) is installed first';
            return false;
        }
        return true;
    }

    function checkTable($table, $details, $details2 = '') {

        $config = JFactory::getConfig();
        $database = $config->get('db');
        $this->dbPrefix = $config->get('dbprefix');

        $table_name = $this->dbPrefix . $table;
        $sql = 'SELECT COUNT(COLUMN_NAME) ';
        $sql .= "FROM information_schema.COLUMNS ";
        $sql .= "WHERE TABLE_SCHEMA='" . $database . "' AND TABLE_NAME ='" . $table_name . "' ";
//        echo "$sql<br>";

        $count = $this->getValue($sql);
        echo 'Seeking ' . $table_name . ', count=' . $count . "<br>";
        if ($count > 0) {
            return $count;
        }
        $sql = 'CREATE TABLE ' . $table_name . ' ' . $details;
        echo "$sql<br>";
        $response = $this->executeCommand($sql);
        if ($response) {
            echo 'Table created OK<br>';
        } else {
            echo 'Failure<br>';
            return false;
        }
        if ($details2 != '') {
            $sql = 'ALTER TABLE ' . $table_name . ' ' . $details2;
            $response = $this->executeCommand($sql);
            if ($response) {
                echo 'Table altered OK<br>';
            } else {
                echo 'Failure<br>';
                return false;
            }
        }
    }

    private function deleteFile($target) {
// Not needed, could use a built in function (if details were known!)
        if (file_exists(JPATH_ROOT . $target)) {
            File::delete(JPATH_ROOT . $target);
            echo "$target deleted<br>";
        }
    }

    private function deleteFolder($target) {
// created 08/10/24 - does not seem to work
        if (file_exists(JPATH_ROOT . $target)) {
            Folder::delete(JPATH_ROOT . $target);
            echo JPATH_ROOT . "$target deleted<br>";
        }
    }

    private function executeCommand($sql) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        return $db->execute();
    }

    public function getDatabaseVersion($component = 'com_ra_events') {
// Get the extension ID
        $db = JFactory::getDbo();
        $eid = $this->getExtensionId($component);

        if ($eid != null) {
// Get the schema version
            $query = $db->getQuery(true);
            $query->select('manifest_cache')
                    ->from('#__extensions')
                    ->where('extension_id = ' . $db->quote($eid));
            $db->setQuery($query);
            $json = $db->loadResult();
            $values = json_decode($json->manifest_cache);
            return $version;
        }
        return null;
    }

    public function getDbVersion($component = 'com_ra_events') {
        $sql = 'SELECT s.version_id ';
        $sql .= 'FROM #__extensions as e ';
        $sql .= 'LEFT JOIN #__schemas AS s ON s.extension_id = e.extension_id ';
        $sql .= 'WHERE e.element="' . $component . '"';
        return $this->getValue($sql);
    }

    public function getVersion($component = 'com_ra_mailman') {
        // This retuns the version as display by System / Manage extensions
        $sql = 'SELECT manifest_cache ';
        $sql .= 'FROM  #__extensions  ';
        $sql .= 'WHERE element="' . $component . '"';
        $data = json_decode($this->getValue($sql));
        return $data->version;
    }

    /**
     *     returns details of the component version and the database version
     *
     * @return  CMSObject
     *
     */
    public function getVersions($component = 'com_ra_mailman') {
        // Returns an object with two values:
        //  ->component
        //  ->db_version
        $versions = new CMSObject;
        $sql = 'SELECT e.manifest_cache, s.version_id AS db_version ';
        $sql .= 'FROM #__extensions as e ';
        $sql .= 'LEFT JOIN #__schemas AS s ON s.extension_id = e.extension_id ';
        $sql .= 'WHERE element="' . $component . '"';

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        $db->execute();
        $item = $db->loadObject();
        if ($item == false) {
            echo 'Can\'t find versions for ' . $component . '<br>';
            echo $db->replacePrefix($query) . '<br>';
            return false;
        } else {
            $values = json_decode($item->manifest_cache);
            $versions->component = $values->version;
            $versions->db_version = $item->db_version;
        }

        return $versions;
    }

    /**
     * Loads the ID of the extension from the database
     *
     * @return mixed
     */
    public function getExtensionId($component = 'com_ra_mailman') {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true);
        $query->select('extension_id')
                ->from('#__extensions')
                ->where($db->qn('element') . ' = ' . $db->q($component) . ' AND type=' . $db->q('component'));
        $db->setQuery($query);
        $eid = $db->loadResult();
//        echo $db->replacePrefix($query) . '<br>';
        return $eid;
    }

    private function getValue($sql) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $db->setQuery($sql);
        return $db->loadResult();
    }

    public function install($parent): bool {
        echo '<p>Installing RA MailMan (com_ra_mailman) ' . '</p>';
        /*
          if (ComponentHelper::isEnabled('com_ra_mailman', true)) {
          $this->original_version = $this->getVersion();
          echo '<p>com_ra_events found, version ' . $this->original_version;
          echo ', database version ' . $this->getDbVersion() . '</p>';
          }
          if (ComponentHelper::isEnabled('com_ra_tools', true)) {
          $tools_versions = $this->getVersions('com_ra_tools');

          $tools_required = '3.0.4';
          echo '<p>Version ' . $tools_required . ' of com_ra_tools required<br>';
          if (version_compare($tools_version, $tools_required, 'ge')) {
          echo '<p>Version ' . $tools_versions . ' of com_ra_tools found</p>';
          } else {
          echo 'Version ' . $tools_version . ' of com_ra_tools found</p>';
          echo '<p>ERROR: Please install version of com_ra_tools >=' . $tools_required . '</p>';
          return false;
          }
          } else {
          echo 'This component cannot be installed unless component RA Tools (com_ra_tools) is installed first';
          return false;
          }
         */
//        if (ComponentHelper::isEnabled('com_ra_mailman', true)) {
//            $versions = $this->getVersions();
//            if ($versions) {
//            echo '<p>com_ra_mailman found, version ' . $versions->component;
//            echo ', database version ' . $versions->db_version . '</p>';
//            //           }
//        }
//        $v_403 = '4.0.3';
//        if (version_compare($versions->component, $v_403, '>')) {
//            echo 'New version is greater than ' . $v_403 . '<br>';
//        }
//        $sql = "INSERT INTO `dev_ra_mail_access` (`id`, `name`)";
//        $sql .= "VALUES ('1', 'Subscriber'), ('2', 'Author') ,('3', 'Owner') ";
        return true;
    }

    public function uninstall($parent): bool {
        echo '<p>Uninstalling RA MailMan (com_ra_mailman)<br>';
        $versions = $this->getVersions();
        echo '<p>Version ' . $versions->component;
        echo ', database version ' . $versions->db_version . '</p>';
        return true;
    }

    public function update($parent): bool {
        echo '<p>Updating RA MailMan (com_ra_mailman)</p>';

// You can have the backend jump directly to the newly updated component configuration page
// $parent->getParent()->setRedirectURL('index.php?option=com_ra_mailman');
        return true;
    }

    public function postflight($type, $parent) {
        'Postflight RA MailMan (com_ra_mailman)<br>';

        if ($type == 'uninstall') {
            return true;
        }
        echo '<p>com_ra_mailman is now at ' . $this->getVersion() . '</p>';
        $version_required = '4.4.4';
        if (version_compare($this->current_version, $version_required, 'ge')) {
            echo 'Previous version was ' . $this->current_version . ', no additional processing required</p>';
            return true;
        } else {
            echo '<p>Version was originally ' . $this->current_version . ', ';
            echo 'Requires version >= ' . $version_required . '</p>';
        }
        $details = '(
            `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `date_phase1` DATETIME NOT NULL ,
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
            `error_report` MEDIUMTEXT  DEFAULT NULL,
            `new_users` MEDIUMTEXT DEFAULT NULL,
            `new_subs` MEDIUMTEXT DEFAULT NULL,
            `lapsed_members` MEDIUMTEXT DEFAULT NULL,
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
        $thid->checkTable('ra_import_reports', $details);

        /*
          $this->checkColumn('ra_mail_shots', 'ordering', 'D');
          $versions = $this->getVersions();
          echo 'com_ra_mailman is now at version ' . $versions->component;
          echo ', database version ' . $versions->db_version . '<br>';
          $this->deleteFile('/components/com_ra_tools/forms/mailshot.xml');
          $this->deleteFile('/components/com_ra_tools/src/Helpers/temp.php');
          $this->deleteFile('/components/com_ra_tools/src/Model/MailshotModel.php');
          $this->deleteFile('/components/com_ra_tools/src/View/Mailshot/HtmlView.php');
          $this->deleteFile('/components/com_ra_tools/src/tmpl/mailshot/default.php');
          $this->deleteFolder('/components/com_ra_tools/src/View/Mailshot');
          $this->deleteFolder('/components/com_ra_tools/src/tmpl/mailshot');

         */
        return true;
    }

    public function preflight($type, $parent): bool {
        echo 'Preflight RA MailMan (type=' . $type . ')<br>';
        if ($type == 'uninstall') {
            return true;
        }

        if (!empty($this->minimumPHPVersion) && version_compare(PHP_VERSION, $this->minimumPHPVersion, '<')) {
            Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPHPVersion),
                    Log::WARNING,
                    'jerror'
            );
            return false;
        }
        if (!empty($this->minimumJoomlaVersion) && version_compare(JVERSION, $this->minimumJoomlaVersion, '<')) {
            Log::add(
                    Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomlaVersion),
                    Log::WARNING,
                    'jerror'
            );
            return false;
        }
        if (ComponentHelper::isEnabled('com_ra_mailman', true)) {
            $this->current_version = $this->getVersion();
            echo 'com_ra_mailman already present, version=' . $this->getVersion();
            echo ', DB version=' . $this->getDbVersion() . '<br>';
        }

        if (!ComponentHelper::isEnabled('com_ra_tools', true)) {
            echo 'Can only be installed if com_ra_tools is already present';
            return false;
        }

        $tools_required = '3.2.3';
        $tools_version = $this->getVersion('com_ra_tools');
        echo '<p>Version ' . $tools_required . ' of com_ra_tools required<br>';
        if (version_compare($tools_version, $tools_required, 'ge')) {
            echo 'Version ' . $tools_version . ' of com_ra_tools found</p>';
        } else {
            echo 'Version ' . $tools_version . ' of com_ra_tools found</p>';
            echo '<p>WARNING: Requires version of com_ra_tools >=' . $tools_required . '</p>';
//          return false;
        }

        return true;
    }

}
