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
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$toolsHelper = new ToolsHelper;
$back = 'administrator/index.php?option=com_ra_tools&view=dashboard';
$breadcrumbs = $toolsHelper->buildLink('administrator/index.php', 'Dashboard');
$breadcrumbs .= '>' . $toolsHelper->buildLink($back, 'RA Dashboard');
echo $breadcrumbs;
$objTable = new ToolsTable();
$objTable->add_header("Report,Action");
?>

<form action="<?php echo JRoute::_('index.php?option=com_ra_tools&view=reports'); ?>" method="post" name="reportsForm" id="reportsForm">
    <div id="j-main-container" class="span10">
        <div class="clearfix"> </div>
        <?php
//        $objTable->width = 30;
//        $objTable->add_column("Report", "R");
//        $objTable->add_column("Action", "L");

        $objTable->add_item("Recent Mailshots");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.recentMailshots", "Go", False, 'red'));
        $objTable->generate_line();

        if (ToolsHelper::isInstalled('com_ra_events')) {
            $objTable->add_item("Future bookable Events");
            $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.bookableEvents", "Go", False, 'red'));
            $objTable->generate_line();
        }

        $objTable->add_item("Subscriptions summary");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.subscriptionsSummary", "Go", False, 'red'));
        $objTable->generate_line();

        $objTable->add_item("Subscriptions due");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.showDue", "Go", False, 'red'));
        $objTable->generate_line();

        $objTable->add_item("Subscriptions created");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.showCreated", "Go", False, 'red'));
        $objTable->generate_line();

        $objTable->add_item("Subscriptions by Status");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.showSubscriptionsByStatus", "Go", False, 'red'));
        $objTable->generate_line();

        $objTable->add_item("Mailshots by Month");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.showMailshotsByMonth", "Go", False, 'red'));
        $objTable->generate_line();

        $objTable->add_item("Users awaiting password reset");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.resetUsers", "Go", False, 'red'));
        $objTable->generate_line();

        $objTable->add_item("Blocked users");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.blockedUsers", "Go", False, 'red'));
        $objTable->generate_line();

        $objTable->add_item("Sample Email");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.dummyEmail", "Go", False, 'red'));
        $objTable->generate_line();

        $objTable->add_item("Check database for invalid records");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.checkDatabase", "Go", False, 'red'));
        $objTable->generate_line();

        $objTable->add_item("Duplicate Recipients");
        $objTable->add_item($toolsHelper->buildButton("administrator/index.php?option=com_ra_mailman&task=reports.duplicateRecipients", "Go", False, 'red'));
        $objTable->generate_line();

//        $objTable->add_item("Logfile");
//        $objTable->add_item($toolsHelper->buildLink("administrator/index.php?option=com_ra_tools&task=reports.showLogfile&offset=1", "Go", False, 'btn btn-small button-new'));
//        $objTable->generate_line();

        $objTable->generate_table();
        echo $toolsHelper->backButton($back);
        ?>
        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</div>
</form>
<?php
echo "<!-- End of code from ' . __file . ' -->" . PHP_EOL;
