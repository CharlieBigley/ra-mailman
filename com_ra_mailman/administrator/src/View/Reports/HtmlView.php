<?php

/*
 * @version    4.2.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 19/06/23 CB Created from com_ra_tools
 * 04/11/24 CB use getIdentity instead of getUser
 * 13/02/25 CB replace getIdentity with $this->getCurrentUser()
 * 26/04/25 CB Don't hide system menu
 */

namespace Ramblers\Component\ra_mailman\Administrator\View\Reports;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\User\CurrentUserInterface;

//use Ramblers\Component\ra_tools\Administrator\Helpers\ToolsHelper;
class HtmlView extends BaseHtmlView implements CurrentUserInterface {

    protected $params;
    protected $user;

    public function display($tpl = null) {
        $app = Factory::getApplication();
        $this->user = $this->getCurrentUser();
        $this->user_id = $this->user->id;

        $this->params = ComponentHelper::getParams('com_ra_mailman');
        $this->addToolbar();

        parent::display($tpl);
    }

    protected function addToolbar() {
        // Suppress menu side panel
//        Factory::getApplication()->input->set('hidemainmenu', true);

        ToolBarHelper::title('Mailman reports');

        // this button display but does nothing
//        ToolbarHelper::cancel('reports.cancel', 'Return to Dashboard');
    }

}
