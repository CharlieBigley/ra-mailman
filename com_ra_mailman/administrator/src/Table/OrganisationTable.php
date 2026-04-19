<?php

/**
 * @version    CVS: 4.7.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2024 Charlie Bigley
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * 21/04/25 CB remove Ra_toolsHelper
 * 08/04/26 Claude Refactored from com_ra_tools
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Access\Access;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Table\Table as Table;
use \Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use \Joomla\Database\DatabaseDriver;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\CMS\Filesystem\File;
use \Joomla\CMS\Filesystem\Folder;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Helper\ContentHelper;

/**
 * Organisation table
 *
 * @since 3.0.0
 */
class OrganisationTable extends Table implements VersionableTableInterface, TaggableTableInterface {

    use TaggableTableTrait;

    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     * @since  4.0.0
     */
    protected $_supportNullValue = true;

    /**
     * Constructor
     *
     * @param   JDatabase  &$db  A database connector object
     */
    public function __construct(DatabaseDriver $db) {
        $this->typeAlias = 'com_ra_mailman.organisation';
        parent::__construct('#__ra_organisations', 'id', $db);
        $this->setColumnAlias('published', 'state');
    }

    /**
     * Get the type alias for the history table
     *
     * @return  string  The alias as described above
     *
     * @since   3.0.0
     */
    public function getTypeAlias() {
        return $this->typeAlias;
    }

    /**
     * Overloaded bind function to pre-process the params.
     *
     * @param   array  $array   Named array
     * @param   mixed  $ignore  Optional array or list of parameters to ignore
     *
     * @return  boolean  True on success.
     *
     * @see     Table:bind
     * @since   3.0.0
     * @throws  \InvalidArgumentException
     */
    public function bind($array, $ignore = '') {
        $date = Factory::getDate();
        $task = Factory::getApplication()->input->get('task');
        $user = Factory::getApplication()->getIdentity();

        // Support for multiple or not foreign key field: nation_id
        if (!empty($array['nation_id'])) {
            if (is_array($array['nation_id'])) {
                $array['nation_id'] = implode(',', $array['nation_id']);
            } else if (strrpos($array['nation_id'], ',') != false) {
                $array['nation_id'] = explode(',', $array['nation_id']);
            }
        } else {
            $array['nation_id'] = 0;
        }
        $input = Factory::getApplication()->input;
        $task = $input->getString('task', '');

        if ($array['id'] == 0 && empty($array['created_by'])) {
            $array['created_by'] = Factory::getUser()->id;
        }

        if (isset($array['cluster'])) {
            $array['cluster'] = strtoupper(trim((string) $array['cluster']));
        }

        if (!empty($array['logo'])) {
            $array['logo'] = $this->prepareLogoPath($array['logo']);
        }

        if (isset($array['params']) && is_array($array['params'])) {
            $registry = new Registry;
            $registry->loadArray($array['params']);
            $array['params'] = (string) $registry;
        }

        if (isset($array['metadata']) && is_array($array['metadata'])) {
            $registry = new Registry;
            $registry->loadArray($array['metadata']);
            $array['metadata'] = (string) $registry;
        }

        if (!$user->authorise('core.admin', 'com_ra_mailman.organisation.' . $array['id'])) {
            $actions = Access::getActionsFromFile(
                            JPATH_ADMINISTRATOR . '/components/com_ra_mailman/access.xml',
                            "/access/section[@name='area']/"
            );
            $default_actions = Access::getAssetRules('com_ra_mailman.organisation.' . $array['id'])->getData();
            $array_jaccess = array();

            foreach ($actions as $action) {
                if (key_exists($action->name, $default_actions)) {
                    $array_jaccess[$action->name] = $default_actions[$action->name];
                }
            }

            $array['rules'] = $this->JAccessRulestoArray($array_jaccess);
        }

        // Bind the rules for ACL where supported.
        if (isset($array['rules']) && is_array($array['rules'])) {
            $this->setRules($array['rules']);
        }

        return parent::bind($array, $ignore);
    }

    private function prepareLogoPath($logo) {
        $logo = trim((string) $logo);

        if ($logo === '') {
            return '';
        }

        $targetDirectoryRelative = 'images/com_ra_mailman';
        $targetDirectoryAbsolute = JPATH_ROOT . '/' . $targetDirectoryRelative;
        $logoRelative = $this->normaliseLogoRelativePath($logo);

        if (!Folder::exists($targetDirectoryAbsolute)) {
            Folder::create($targetDirectoryAbsolute);
        }

        $filename = basename(str_replace('\\', '/', $logoRelative));

        if ($filename === '') {
            return '';
        }

        $targetAbsolute = $targetDirectoryAbsolute . '/' . $filename;
        $sourceAbsolute = JPATH_ROOT . '/' . ltrim($logoRelative, '/');

        if (File::exists($sourceAbsolute) && $sourceAbsolute !== $targetAbsolute) {
            File::copy($sourceAbsolute, $targetAbsolute, null, true);
            return $filename;
        }

        if (File::exists($targetAbsolute) || $sourceAbsolute === $targetAbsolute) {
            return $filename;
        }

        return $filename;
    }

    private function normaliseLogoRelativePath($logo) {
        $logo = trim((string) $logo);
        $parts = explode('#', $logo);

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            $part = preg_replace('/\?.*$/', '', $part);

            if (strpos($part, 'joomlaImage://') === 0) {
                $part = preg_replace('#^joomlaImage://[^/]+/#', '', $part);
            }

            $part = ltrim($part, '/');

            if (strpos($part, 'local-images/') === 0) {
                return 'images/' . substr($part, strlen('local-images/'));
            }

            if ($part !== '') {
                return $part;
            }
        }

        return '';
    }

    /**
     * Method to store a row in the database from the Table instance properties.
     *
     * If a primary key value is set the row with that primary key value will be updated with the instance property values.
     * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     *
     * @since   3.0.0
     */
    public function store($updateNulls = true) {
        if ($this->id > 0) {
            $this->modified_by = Factory::getApplication()->getSession()->get('user')->id;
            $this->modified = Factory::getDate('now', Factory::getConfig()->get('offset'))->toSql(true);
        }
        return parent::store($updateNulls);
    }

    /**
     * This function convert an array of Access objects into an rules array.
     *
     * @param   array  $jaccessrules  An array of Access objects.
     *
     * @return  array
     */
    private function JAccessRulestoArray($jaccessrules) {
        $rules = array();

        foreach ($jaccessrules as $action => $jaccess) {
            $actions = array();

            if ($jaccess) {
                foreach ($jaccess->getData() as $group => $allow) {
                    $actions[$group] = ((bool) $allow);
                }
            }

            $rules[$action] = $actions;
        }

        return $rules;
    }

    /**
     * Overloaded check function
     *
     * @return bool
     */
    public function check() {
        // If there is an ordering column and this is a new row then get the next ordering value
        if (property_exists($this, 'ordering') && $this->id == 0) {
            $this->ordering = self::getNextOrder();
        }



        return parent::check();
    }

    /**
     * Define a namespaced asset name for inclusion in the #__assets table
     *
     * @return string The asset name
     *
     * @see Table::_getAssetName
     */
    protected function _getAssetName() {
        $k = $this->_tbl_key;

        return $this->typeAlias . '.' . (int) $this->$k;
    }

    /**
     * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
     *
     * @param   Table   $table  Table name
     * @param   integer  $id     Id
     *
     * @see Table::_getAssetParentId
     *
     * @return mixed The id on success, false on failure.
     */
    protected function _getAssetParentId($table = null, $id = null) {
        // We will retrieve the parent-asset from the Asset-table
        $assetParent = Table::getInstance('Asset');

        // Default: if no asset-parent can be found we take the global asset
        $assetParentId = $assetParent->getRootId();

        // The item has the component as asset-parent
        $assetParent->loadByName('com_ra_mailman');

        // Return the found asset-parent-id
        if ($assetParent->id) {
            $assetParentId = $assetParent->id;
        }

        return $assetParentId;
    }

    //XXX_CUSTOM_TABLE_FUNCTION

    /**
     * Delete a record by id
     *
     * @param   mixed  $pk  Primary key value to delete. Optional
     *
     * @return bool
     */
    public function delete($pk = null) {
        $this->load($pk);
        $result = parent::delete($pk);

        return $result;
    }

}
