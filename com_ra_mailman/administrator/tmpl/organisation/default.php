<?php
/**
 * @version    4.7.0
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2024 Charlie Bigley
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 * 09/04/26 Claude Updated to show all table fields with colour display
 */

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

?>

<div class="item_fields">
	<table class="table table-striped">
		<thead>
			<tr>
				<th>Field</th>
				<th>Value</th>
			</tr>
		</thead>
		<tbody>
			<?php
			// Define fields to display in order
			$displayFields = array(
				'id' => 'ID',
				'code' => 'Code',
				'name' => 'Name',
				'nation_id' => 'Nation',
				'details' => 'Details',
				'website' => 'Website',
				'co_url' => 'CO URL',
				'cluster' => 'Cluster',
				'latitude' => 'Latitude',
				'longitude' => 'Longitude',
				'email_header' => 'Email Header',
				'logo' => 'Logo',
				'colour_header' => 'Header Colour',
				'colour_body' => 'Body Colour',
				'colour_footer' => 'Footer Colour',
			);

			// Color fields that should be displayed as background colors
			$colorFields = array('colour_header', 'colour_body', 'colour_footer');

			// Display each field
			foreach ($displayFields as $fieldName => $fieldLabel) {
				if (isset($this->item->$fieldName)) {
					$value = $this->item->$fieldName;
					echo '<tr>' . PHP_EOL;
					echo '<td><strong>' . htmlspecialchars($fieldLabel) . '</strong></td>' . PHP_EOL;

					// Check if this is a color field and if it has a value
					if (in_array($fieldName, $colorFields) && !empty($value)) {
						// Display as background color with rgba styling
						echo '<td style="background-color: ' . htmlspecialchars($value) . '; padding: 20px; min-height: 40px; border-radius: 4px;">';
						echo '<code style="background: rgba(255,255,255,0.8); padding: 4px 8px; border-radius: 2px;">' . htmlspecialchars($value) . '</code>';
						echo '</td>' . PHP_EOL;
					} else {
						// Regular field display
						if ($fieldName === 'website' || $fieldName === 'co_url' || $fieldName === 'logo') {
							// Display URLs as links
							if (!empty($value)) {
								echo '<td><a href="' . htmlspecialchars($value) . '" target="_blank">' . htmlspecialchars($value) . '</a></td>' . PHP_EOL;
							} else {
								echo '<td><em>Empty</em></td>' . PHP_EOL;
							}
						} else {
							// Display regular text
							echo '<td>' . (!empty($value) ? htmlspecialchars($value) : '<em>Empty</em>') . '</td>' . PHP_EOL;
						}
					}
					echo '</tr>' . PHP_EOL;
				}
			}
			?>
		</tbody>
	</table>
</div>

