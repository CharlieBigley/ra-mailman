<?php

/**
 * @version    CVS: 4.7.0
 * @component   com_ra_mailman
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 18/07/23 CB left join on nations
 * 21/11/23 CB correct spelling of search_fields
 * 22/02/25 CB includ cluster in field list
 * 08/04/26 Claude Refactored from com_ra_tools
 */

namespace Ramblers\Component\Ra_mailman\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
//use Joomla\CMS\Form\Form;
//use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * Item Model for a list of organisations.
 *
 * @since  1.6
 */
class OrganisationsModel extends ListModel {

    protected $search_fields;

    public function __construct($config = []) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'a.code',
                'a.name',
                'a.cluster',
                'n.name',
                'a.email_header',
                'a.logo',
            );
            $this->search_fields = $config['filter_fields'];
        }
        parent::__construct($config);
    }

    protected function getListQuery() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('a.*');
$query->select('n.name as nation');

        $query->from($db->quoteName('#__ra_organisations', 'a'));
        $query->LeftJoin('#__ra_nations AS n ON n.id = a.nation_id');
        // Filter by search
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } else {
                $query = ToolsHelper::buildSearchQuery($search, $this->search_fields, $query);
            }
        }

        // Add the list ordering clause, defaut to name ASC
        $orderCol = $this->state->get('list.ordering', 'a.name');
        $orderDirn = $this->state->get('list.direction', 'asc');

        if ($orderCol == 'n.name') {
            $orderCol = $db->quoteName('n.name') . ' ' . $orderDirn . ', ' . $db->quoteName('a.name');
        }

        $query->order($db->escape($orderCol . ' ' . $orderDirn));
        if (JDEBUG) {
            Factory::getApplication()->enqueueMessage('sql = ' . (string) $query, 'notice');
        }
        return $query;
    }

    protected function populateState($ordering = 'a.name', $direction = 'asc') {
        // List state information.
        parent::populateState($ordering, $direction);
    }

}
