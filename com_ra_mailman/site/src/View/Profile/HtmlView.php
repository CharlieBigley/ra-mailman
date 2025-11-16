<?php

/**
 * @version    CVS: 4.2.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 13/02/25 CB replace getIdentity with $this->getCurrentUser()
 * 21/03/25 CB check logged when showing subscriptons
 * 23/04/25 CB check permission before creating a new user
 */

namespace Ramblers\Component\Ra_mailman\Site\View\Profile;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Helper\ContentHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\User\CurrentUserInterface;

/**
 * View class for Profile.
 *
 * @since  4.1.0
 */
class HtmlView extends BaseHtmlView implements CurrentUserInterface {

    protected $canDo;
    protected $state;
    protected $item;
    protected $form;
    protected $list_id;
    protected $menu_id;
    protected $layout;
    protected $params;
    protected $title;
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
        $this->menu_id = $app->input->getInt('Itemid', '0');
        $this->list_id = $app->input->getInt('list', '0');
        $layout = $app->input->getWord('layout', 'profile');
        $this->state = $this->get('State');
        $this->item = $this->get('Item');
        $this->params = $app->getParams('com_ra_mailman');
        $this->canSave = $this->get('CanSave');
        $this->form = $this->get('Form');
        $this->canDo = ContentHelper::getActions('com_ra_mailman');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        if ($layout == 'profile') {
            // Mode is defined when creating the menu entry: 0=Self register, 1=Admin register
            $this->mode = $this->params->get('mode', '0');
            if ($this->mode == '0') {
                if ($this->user->id > 0) {
                    //return Error::raiseWarning(404, "Please login to gain access to this function");
//            throw new \Exception('Please login to gain access to this function', 403);
                    echo '<h4>You are already Registered</h4>';
                    return false;
                }
            } else {  // Creating a new user
                if ($this->user->id == 0) {
                    //return Error::raiseWarning(404, "Please login to gain access to this function");
//            throw new \Exception('Please login to gain access to this function', 403);
                    echo '<h4>Please login to gain access to this function</h4>';
                    return false;
                }
                if (!$this->canDo->get('core.create')) {
                    $app->enqueueMessage('Sorry, you don\'t have permission to create new Users', 'error');
//                    $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=mail_lsts', false));
                    return;
                }
            }
        } else {   // Showing subscriptons
            if ($this->user->id == 0) {
                throw new \Exception('Please login to gain access to this function', 403);
            }
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

        $this->title = $this->params->get('page_title', '');

        if (empty($this->title)) {
            $this->title = $app->get('sitename');
        } elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $this->title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $this->title = Text::sprintf('JPAGETITLE', $this->title, $app->get('sitename'));
        }

        $this->document->setTitle($this->title);

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
