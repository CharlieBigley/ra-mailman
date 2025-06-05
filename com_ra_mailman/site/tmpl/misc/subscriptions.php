
<?php

/**
 * @version     1.0.9
 * @package     com_ra_tools
 * @copyright   Copyright (C) 2020. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Charlie Bigley <webmaster@bigley.me.uk>
 * 20/02/21 Created
 * 15/07/22 CB correct building of link
 * 18/07/22 CB Neighbouring Areas
 * 19/07/22 CB Check that menu parameters are not null
 * 02/01/23 CB show group-code even if not record found in ra_groups
 * 05/01/23 CB  Don't show programme if group name not found
 * 31/08/23 CB use view programme to show walks
 */
// No direct access
use Joomla\CMS\HTML\HTMLHelper;
use Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsTable;

defined('_JEXEC') or die;
$objHelper = new ToolsHelper;
//if ($this->params->get('show_page_heading') == 1) {
echo '<h2>' . $this->params->get('page_title') . '</h2>';
//}


if ($intro != '') {
    echo $intro . "<br>";
}

$objTable = new ToolsTable();
$objTable->add_header('Group,Title,Expiry date,Reminder, Action');

$target_info = 'index.php?option=com_ra_mailman&view=misc&template=subscription&list=';

$sql = 'SELECT s.id, ';
$sql .= 'u.name AS `Subscriber`, ';
$sql .= 'DATE(s.created) AS `Created`, ';
$sql .= 's.modified, s.expiry_date, s.reminder_sent,';
$sql .= 'l.group_code AS `group`, l.name AS `list`, ';
$sql .= 'm.name AS `Method`, ma.name as Access ';
$sql .= 'FROM `#__ra_mail_subscriptions` AS s ';
$sql .= 'INNER JOIN `#__ra_mail_methods` AS `m` ON m.id = s.method_id ';
$sql .= 'LEFT JOIN `#__users` AS `u` ON u.id = s.user_id ';
$sql .= 'LEFT JOIN `#__ra_mail_lists` AS `l` ON l.id = s.list_id ';
$sql .= 'LEFT JOIN #__ra_mail_access AS ma ON ma.id = s.record_type ';
$sql .= 'LEFT JOIN #__ra_profiles as p ON p.id = s.user_id ';

//       $sql .= 'WHERE s.user_id IS NULL ';
$sql .= 'ORDER BY l.goup_code, l.name ';
//        $rows = $objHelper->getRows($sql);
if ($objHelper->rows == 0) {
    echo 'No subscriptions <br>';
} else {
    $count = 0;
    foreach ($rows as $row) {
        $objTable->add_item($count);
        $objTable->add_item($row->group_code);
        $objTable->add_item($row->list);
        $objTable->add_item($row->HTMLHelper::_('date', $row->expiry_date, 'd-M-Y')); // $pretty_date = HTMLHelper::_('date', $row->expiry_date, 'd-M-Y');
        $objTable->add_item($row->HTMLHelper::_('date', $row->reminder_sent, 'd-M-Y'));
        $details = $objHelper->buildButton($target_info . $row->id);
        $objTable->add_item($details);
        $objTable->generate_line();
        $count++;
    }
}

$objTable->generate_table();

echo $mailhelper->mailshotDetails($row->id);

