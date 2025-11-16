<?php

/**
 * @version    4.5.7
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 05/07/23 CB changed action from task=mailshot.edit to view=mailshot
 * 14/11/23 CB store and pass menu_id
 * 30/01/24 CB include list owner in search
 * 26/08/24 CB don't allow editing if mailshot only partially sent
 * 10/09/24 CB change edit view from mailshot to mailshotform
 * 14/10/24 CB when processing if edit/create allowed, use object passed by MailHelper->lastMailshot
 * 13/02/25 CB replace getIdentity with $this->getCurrentUser()
 * 03/04/25 CB correct check on mailshots not yet sent
 * 20/10/25 CB sendButton
 */

namespace Ramblers\Component\Ra_mailman\Site\View\Mail_lsts;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use \Joomla\CMS\User\CurrentUserInterface;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

/**
 * View class for a list of Ra_mailman.
 *
 * @since  1.0.6
 */
class HtmlView extends BaseHtmlView implements CurrentUserInterface {

    protected $app;
    protected $items;
    protected $mailshot_send_message;
    protected $pagination;
    protected $state;
    protected $params;
    protected $toolsHelper;
    protected $menu_id;
    protected $user;
    protected $last_sent;

    function defineActions($list_id, $list_type, $emails_outstanding, $last_mailshot) {
        /*
         * invoked from the template to set up the required action buttons for the last column of the report
         * $list_type will be Open / Closed
         */
        if ($this->user->id == 0) {
            return '';
        }
        if ($emails_outstanding > 0) {
            return '';
        }
        $objMailHelper = new Mailhelper;
        if ($objMailHelper->isAuthor($list_id)) {
            if (JDEBUG) {
                echo 'Author  ' . $list_id;
                //               print_r($last_mailshot);
            }
            if ((is_null($last_mailshot->date_sent))
                    AND (!is_null($last_mailshot->processing_started))) {
                //               $this->app->enqueueMessage('Last sent ' . $last_mailshot->date_sent . ', started ' . $last_mailshot->processing_started, 'warning');
                $this->app->enqueueMessage('Sending has started ' . $last_mailshot->processing_started . ' cannot edit or create', 'warning');
                return '';
            }
            $target = 'index.php?option=com_ra_mailman&view=mailshotform' . '&Itemid=' . $this->menu_id;
            if (is_null($last_mailshot->date_sent) AND ($last_mailshot->id > 0)) {
                $caption = 'Edit';
                $target .= '&id=' . $last_mailshot->id;
            } else {
                $caption = 'Create';
            }
            return $this->toolsHelper->buildLink($target . '&list_id=' . $list_id, $caption, False, "link-button button-p0159");
        }

        // See if User is already subscribed
        $sql = 'SELECT id, state FROM #__ra_mail_subscriptions WHERE user_id=' . $this->user->id;
        $sql .= ' AND list_id=' . $list_id;
        $subscription = $this->toolsHelper->getItem($sql);
        if ($list_type == 'Open') {
            if (is_null($subscription)) {
                $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe' . '&menu_id=' . $this->menu_id;
                $caption = 'Subscribe';
                $colour = '0583';  // light green
            } else {
//                echo "View: state $subscription->state<br>";
                if ($subscription->state == 0) {
                    $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe' . '&menu_id=' . $this->menu_id;
                    $caption = 'Re-subscribe';
                    $colour = '0555';  // dark green
                }
            }
        }
        // For unsubscribing, we don't care if the list is open or closed
        if (!is_null($subscription)) {
            if ($subscription->state > 0) {
                $target = 'index.php?option=com_ra_mailman&task=mail_lst.unsubscribe&list_id=' . '&menu_id=' . $this->menu_id;
                $caption = 'Un-subscribe';
                $colour = 'rosycheeks';
                return $this->toolsHelper->buildButton($target . $list_id . '&user_id=' . $this->user->id, $caption, False, $colour);
            }
        }
        if ($list_type == 'Open') {
            if (is_null($subscription)) {
                $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe' . '&menu_id=' . $this->menu_id;
                $caption = 'Subscribe';
                $colour = 'sunset';
            } else {
                if ($subscription->state == 0) {
                    $target = 'index.php?option=com_ra_mailman&task=mail_lst.subscribe' . '&menu_id=' . $this->menu_id;
                    $caption = 'Re-subscribe';
                    $colour = 'sunrise';
                }
            }
            return $this->toolsHelper->buildButton($target . '&list_id=' . $list_id . '&user_id=' . $this->user->id, $caption, False, $colour);
        } else {
            return '';
        }
    }

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
        $this->app = Factory::getApplication();
        $this->user = $this->getCurrentUser();
        if (is_null($this->user)) {
            echo 'user is NULL<br>';
        }
        $this->menu_id = $this->app->input->getInt('Itemid', '0');
        $this->toolsHelper = new ToolsHelper;

        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->params = $this->app->getParams('com_ra_mailman');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        $this->_prepareDocument();
        $params = ComponentHelper::getParams('com_ra_tools');
        $email_log_level = $params->get('email_log_level', '0');
        // Log level -2 is solely to benchmark the overhead of sending via SMTP
        if ($email_log_level < 0) {
            Factory::getApplication()->enqueueMessage('Email logging is in Benchmark mode: ' . $email_log_level, 'warning');
        }
        $params = ComponentHelper::getParams('com_ra_mailman');
        $this->max_online_send = $params->get('max_online_send', 100);
        $this->mailshot_send_message = $params->get('mailshot_send_message', 'Waiting for batch job');
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
            $this->params->def('page_heading', Text::_('Mailing lists'));
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

    protected function sendButton($last_mailshot, $count_subscribers) {
        //      print_r($last_mailshot);
        //      echo '<br>';
        $target_send = 'index.php?option=com_ra_mailman&task=mailshot.send&menu_id=' . $this->menu_id . '&mailshot_id=';
        if (($last_mailshot->id > 0) AND is_null($last_mailshot->date_sent)) {
            if ($last_mailshot->attachment != '') {
                $target = 'images/com_ra_mailman/' . $last_mailshot->attachment;
                $label = '<span class="icon-paperclip"></span>';
                echo $this->toolsHelper->buildLink($target, $label, True);
            }
            $target = $target_send . $last_mailshot->id . '&total=' . $count_subscribers;
//                                           $target = 'administrator/index.php?option=com_ra_mailman&task=mailshot.send&mailshot_id='  . '&menu_id=' . $this->menu_id;
            if (is_null($last_mailshot->processing_started)) {
                $label = 'Send';
            } else {
                $count = $this->mailHelper->countSubscribersOutstanding($last_mailshot->id);
                $label = 'Resend to ' . $count;
            }
            return $this->toolsHelper->buildButton($target, $label, False);
        }
    }

}
