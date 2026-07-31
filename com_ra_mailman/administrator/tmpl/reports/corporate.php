<?php
/**
 * @version     4.7.10
 * @package     com_ra_mailman
 * @copyright   Copyright (C) 2020. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Charlie Bigley <webmaster@bigley.me.uk> - https://www.developer-url.com
 * 15/04/26 CB created
 * 01/06/26 CB remove members by Group (in membership reports)
 * 16/07/26 CB new formatting
 */
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');
$wa->registerAndUseStyle('dashboard', 'com_ra_tools/dashboard.css');

$back = 'administrator/index.php?option=com_ra_tools&view=dashboard';
$breadcrumbs = $this->toolsHelper->buildLink('administrator/index.php', 'Dashboard');
$breadcrumbs .= '>' . $this->toolsHelper->buildLink($back, 'RA Dashboard');
echo $breadcrumbs;

// find current scope
$mailHelper = new MailHelper;
$code = $mailHelper->getDefaultGroup();

if (!empty($code) && $code !== 'N') {
    $sql = 'SELECT id, name ';
    $sql .= 'FROM #__ra_organisations ';
    $sql .= 'WHERE code="' . $code . '"';
    $item = $this->toolsHelper->getItem($sql);  
    $subheading =  $code . ' ' . (!empty($item->name) ? htmlspecialchars($item->name) : 'N/A');
} else {
    $subheading = 'All records';
}   
/*
$admin_reports = [
    // only show these reports to superusers
//    'Search all Logfile records' => 'administrator/index.php?option=com_ra_tools&view=logfiles&callback=dashboard',
    'Show recent Logfile records' => 'administrator/index.php?option=com_ra_tools&task=reports.showLogfile&option=com_ra_mailman',
];
if ($this->user_id == 1) { 
    $admin_reports['Clusters'] = 'administrator/index.php?option=com_ra_tools&view=clusters';
}
*/
$reports = [
    'Mailshots by Month' => 'administrator/index.php?option=com_ra_mailman&task=reports.showMailshotsByMonth',
    'Recent Mailshots' => 'administrator/index.php?option=com_ra_mailman&task=reports.recentMailshots',
    'Subscriptions summary' => 'administrator/index.php?option=com_ra_mailman&task=reports.subscriptionsSummary',  
    'Preview Email' => 'administrator/index.php?option=com_ra_mailman&task=reports.emailPreview',
   
//    'Duplicate Recipients' => 'administrator/index.php?option=com_ra_mailman&task=reports.duplicateRecipients',
];
//$reports['Future bookable Events'] = 'administrator/index.php?option=com_ra_mailman&task=reports.bookableEvents';

$allReports = array();

if ($this->toolsHelper->isSuperuser()) {
    $allReports = $reports;
}

$areaReports = array();

foreach ($reports as $caption => $task) {
    $areaReports[$caption] = $task . '&scope=A';
}

$groupReports = array();

foreach ($reports as $caption => $task) {
    $groupReports[$caption] = $task . '&scope=G';
}
?>
<form action="<?php echo JRoute::_('index.php?option=com_ra_tools&view=reports'); ?>" method="post" name="reportsForm" id="reportsForm">
    <div id="j-main-container" class="span10">
        <div class="clearfix"> </div>
        <?php
        echo '<h4>Scope '  . $subheading . '</h4>';
        echo '<div class="dashboard-grid">';
        echo $this->toolsHelper->buildDashboardReportBlock('Scope All records', $allReports);
        echo $this->toolsHelper->buildDashboardReportBlock('Area reports', $areaReports);
        echo $this->toolsHelper->buildDashboardReportBlock('Group reports', $groupReports);
        echo '</div>';
        echo $this->toolsHelper->backButton($back);
        ?>
        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </div>
</div>
</form>
