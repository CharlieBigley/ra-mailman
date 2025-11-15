<?php

/**
 * @version    4.5.8
 * @package    com_ra_mailman
 * @author     Barlie Chigley <charlie@bigley.me.uk>
 * @copyright  2025 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 11/11/25 CB Created
 */

namespace Ramblers\Component\Ra_mailman\Administrator\View\Recipients;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Helper\ContentHelper;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\CMS\User\CurrentUserInterface;

/**
 * View class for a list of Recipients.
 *
 * @since  4.5.7
 */
class HtmlView extends BaseHtmlView implements CurrentUserInterface {

    protected $items;
    protected $pagination;
    protected $state;
    protected $user;

    /**
     * Display the view
     *
     * @param   string  $tpl  Template name
     *
     * @return void
     *
     * @throws Exception
     */
    public function display($tpl = null) {
        $this->user = $this->getCurrentUser();
        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->addToolbar();

        $this->sidebar = Sidebar::render();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   4.5.7
     */
    protected function addToolbar() {
        $app = Factory::getApplication();
// Suppress menu side panel
        $app->input->set('hidemainmenu', true);
// Set sidebar action
        Sidebar::setAction('index.php?option=com_ra_mailman&view=mail_lsts');
//        $state = $this->get('State');
        $this->canDo = ContentHelper::getActions('com_ra_mailman');
        // find which layout is in use


        ToolbarHelper::title(Text::_('Mailshot Recipients'), "generic");

        $toolbar = Toolbar::getInstance('toolbar');

        $toolbar->standardButton('nrecords')
                ->icon('fa fa-info-circle')
                ->text(number_format($this->pagination->total) . ' Records')
                ->task('')
                ->onclick('return false')
                ->listCheck(false);
        ToolbarHelper::cancel('subscriptions.cancel', 'Return to Dashboard');
//        $help_url = 'https://docs.stokeandnewcastleramblers.org.uk/mail-manager.html?view=article&id=420:mm-02-2-mailing-lists&catid=34';
//        ToolbarHelper::help('', false, $help_url);
    }

    /**
     * Method to order fields
     *
     * @return void
     */
    protected function xx_getSortFields() {
        return array(
            'a.`id`' => Text::_('JGRID_HEADING_ID'),
            'a.`mailshot_id`' => Text::_('COM_RA_MAILMAN_RECIPIENTS_MAILSHOT_ID'),
            'a.`user_id`' => Text::_('COM_RA_MAILMAN_RECIPIENTS_USER_ID'),
            'a.`email`' => Text::_('COM_RA_MAILMAN_RECIPIENTS_EMAIL'),
        );
    }

    /**
     * Check if state is set
     *
     * @param   mixed  $state  State
     *
     * @return bool
     */
    public function getState($state) {
        return isset($this->state->{$state}) ? $this->state->{$state} : false;
    }

}
