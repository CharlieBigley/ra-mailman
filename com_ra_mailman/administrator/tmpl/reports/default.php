<?php
/**
 * @version     4.5.6
 * @package     com_ra_mailman
 * @copyright   Copyright (C) 2020. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Charlie Bigley <webmaster@bigley.me.uk> - https://www.developer-url.com
 * 28/10/24 CB separate reports duffUsers and duffProfiles, resetUsers
 * 20/11/24 CB showCreated
 * 09/03/25 CB showSubscriptionsByStatus
 * 18/05/25 CB duplicatePreferredname, duplicateRecipients reports
 * 21/05/25 CB dummyEmail, checkDatabase reports
 * 07/07/25 CB breadcrumbs
 * 10/08/25 CB recentMailshots
 * 29/09/25 CB bookableEvents
 * 13/10/25 CB subscriptionsReport
 */
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
// use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$toolsHelper = new ToolsHelper;
$back = 'administrator/index.php?option=com_ra_tools&view=dashboard';
$breadcrumbs = $toolsHelper->buildLink('administrator/index.php', 'Dashboard');
$breadcrumbs .= '>' . $toolsHelper->buildLink($back, 'RA Dashboard');
echo $breadcrumbs;

$reports = [
    'Recent Mailshots' => 'administrator/index.php?option=com_ra_mailman&task=reports.recentMailshots',
    'Subscriptions summary' => 'administrator/index.php?option=com_ra_mailman&task=reports.subscriptionsSummary',
    'Analyse membership enrolment' => 'administrator/index.php?option=com_ra_mailman&task=reports.membershipEnrolment',
    'Subscriptions due' => 'administrator/index.php?option=com_ra_mailman&task=reports.showDue',
    'Subscriptions created' => 'administrator/index.php?option=com_ra_mailman&task=reports.showCreated',
    'Subscriptions by Status' => 'administrator/index.php?option=com_ra_mailman&task=reports.showSubscriptionsByStatus',
    'Mailshots by Month' => 'administrator/index.php?option=com_ra_mailman&task=reports.showMailshotsByMonth',
    'Users awaiting password reset' => 'administrator/index.php?option=com_ra_mailman&task=reports.resetUsers',
    'Blocked users' => 'administrator/index.php?option=com_ra_mailman&task=reports.blockedUsers',
    'Sample Email' => 'administrator/index.php?option=com_ra_mailman&task=reports.dummyEmail',
    'Check database for invalid records' => 'administrator/index.php?option=com_ra_mailman&task=reports.checkDatabase',
    'Duplicate Recipients' => 'administrator/index.php?option=com_ra_mailman&task=reports.duplicateRecipients',
];

if (ToolsHelper::isInstalled('com_ra_events')) {
    $reports['Future bookable Events'] = 'administrator/index.php?option=com_ra_mailman&task=reports.bookableEvents';
}
?>

<form action="<?php echo JRoute::_('index.php?option=com_ra_tools&view=reports'); ?>" method="post" name="reportsForm" id="reportsForm">
    <div id="j-main-container" class="span10">
        <div class="clearfix"> </div>
        <?php
        echo '<ul>';
        foreach ($reports as $caption => $task) {
            echo '<li>' . $toolsHelper->buildLink($task, $caption) . '</li>';
        }
        echo '</ul>';

        echo $toolsHelper->backButton($back);
        ?>
        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</div>
</form>

