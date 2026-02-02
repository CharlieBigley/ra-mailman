<?php

/**
 * @version    4.2.0
 * @package    Com_Ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2024 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 11/09/24 CB lookup name of the mailing list
 * 07/10/24 CB allow selection of csv files as attachment
 * 13/02/25 CB set up $this->user from getCurrentUser
 *             use ContentHelper to set up $canEdit
 */

namespace Ramblers\Component\Ra_mailman\Site\View\Mailshotform;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\User\CurrentUserInterface;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a list of Ra_mailman.
 *
 * @since  1.0.2
 */
class HtmlView extends BaseHtmlView implements CurrentUserInterface {

    protected $state;
    protected $item;
    protected $form;
    protected $params;
    protected $canEdit;
    protected $canSave;
    protected $list_name;
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
        $app = Factory::getApplication();
        $this->user = $this->getCurrentUser();

        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->params = $app->getParams('com_ra_mailman');
        $canDo = ContentHelper::getActions('com_ra_mailman');
        $this->canEdit = $canDo->get('core.edit');
        $this->canSave = $canDo->get('core.create');

        $this->form = $this->get('Form');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }
        $this->objHelper = new ToolsHelper;
        // Get the  id of the Mailing list, passed as part of the URL
        $this->list_id = $app->input->getInt('list_id', '0');
        if ($this->list_id == 0) {
            Factory::getApplication()->enqueueMessage('this->list_name is Zero', 'message');
        } else {
            $sql = 'SELECT group_code, name from `#__ra_mail_lists` WHERE id=' . $this->list_id;
            $row = $this->objHelper->getItem($sql);
            $this->list_name = $row->group_code . ' ' . $row->name;
        }

        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document
     *
     * @return void
     *
     * @throws Exception
     */
    protected function _prepareDocument() {
        $app = Factory::getApplication();
        $menus = $app->getMenu();
        $title = null;

        // Because the application sets a default page title,
        // we need to get it from the menu item itself
        $menu = $menus->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('COM_RA_MAILMAN_DEFAULT_PAGE_TITLE'));
        }

        $title = $this->params->get('page_title', '');

        if (empty($title)) {
            $title = $app->get('sitename');
        } elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->document->setTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->document->setMetadata('robots', $this->params->get('robots'));
        }
    }

}
