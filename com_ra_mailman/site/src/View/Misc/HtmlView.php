<?php

/**
 * @version     4.2.0
 * @package     com_ra_tools
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 20/11/24 CB created from Ra_tools/Misc
 * 13/02/25 CB replace getUser with getCurrentUser
 */

namespace Ramblers\Component\Ra_events\Site\View\Misc;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\User\CurrentUserInterface;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class HtmlView extends BaseHtmlView implements CurrentUserInterface {

    public $canDo;
    protected $params;
    protected $list_id;
    protected $menu_id;
    protected $menu_params;
    protected $objHelper;
    protected $state;
    protected $title;
    protected $user;

    public function display($tpl = null) {
        $app = Factory::getApplication();
        $this->menu_id = $app->input->getInt('Itemid', '0');
        $this->list_id = $app->input->getInt('list', '0');
        // Load the component params
        //       $this->params = ComponentHelper::getParams('com_ra_mailman');
//        var_dump($this->params);
//        echo '<br>end of params from component helper<br>';
        $this->params = $this->get('State')->get('params');
        $menu = $app->getMenu()->getActive();
        if (is_null($menu)) {
            echo 'Menu params are null<br>';
        } else {
            $this->menu_params = $menu->getParams();
        }
//        var_dump($this->menu_params);
//        $x = $this->params->get('data');
//        var_dump($x);
        $this->objHelper = new ToolsHelper;
        $this->user = $this->getCurrentUser();
        $this->canDo = $this->objHelper->getActions('com_ra_mailman');

        $wa = $this->document->getWebAssetManager();
        $wa->useScript('keepalive')
                ->useScript('form.validate');
        $this->prepareDocument();
        return parent::display($tpl);
    }

    /**
     * Prepares the document
     *
     * @return void
     *
     * @throws Exception
     */
    protected function prepareDocument() {
        $app = Factory::getApplication();
        $menus = $app->getMenu();
        $title = null;

        // Because the application sets a default page title,
        // we need to get it from the menu item itself
        $menu = $menus->getActive();

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('JPAGETITLE'));
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
