<?php

/**
 * @version    CVS: 4.7.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2024 Charlie Bigley
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * 21/04/25 CB replace Ra_toolsHelper with ContentHelper
 * 08/04/26 Claude Refactored from com_ra_tools
 */

namespace Ramblers\Component\Ra_mailman\Administrator\View\Organisation;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

/**
 * View class for a single Organisation.
 *
 * @since  3.0.0
 */
class HtmlView extends BaseHtmlView {

    protected $canDo;
    protected $state;
    protected $item;
    protected $form;

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
        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');
        $this->canDo = ContentHelper::getActions('com_ra_mailman');
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }
        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function addToolbar() {
        Factory::getApplication()->input->set('hidemainmenu', true);

        $user = Factory::getApplication()->getIdentity();
        $isNew = ($this->item->id == 0);

        if (isset($this->item->checked_out)) {
            $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
        } else {
            $checkedOut = false;
        }

        ToolbarHelper::title(Text::_('Ramblers Area'), "generic");

        // If not checked out, can save the item.
        if (!$checkedOut && ($this->canDo->get('core.edit') || ($$this->anDo->get('core.create')))) {
            ToolbarHelper::apply('organisation.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('organisation.save', 'JTOOLBAR_SAVE');
        }


        if (empty($this->item->id)) {
            ToolbarHelper::cancel('organisation.cancel', 'JTOOLBAR_CANCEL');
        } else {
            ToolbarHelper::cancel('organisation.cancel', 'JTOOLBAR_CLOSE');
        }
    }

}
