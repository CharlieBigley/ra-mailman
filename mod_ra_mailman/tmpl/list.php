<?php
/**
 * @version     CVS: 1.0.0
 * @package     com_ra_mailman
 * @subpackage  mod_ra_mailman
 * @author      Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright   2024 Charlie Bigley
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;

use Ramblers\Module\Ra_mailman\Site\Helper\Ra_mailmanHelper;

$elements = Ra_mailmanHelper::getList($params);

$tableField = explode(':', $params->get('field'));
$table_name = !empty($tableField[0]) ? $tableField[0] : '';
$field_name = !empty($tableField[1]) ? $tableField[1] : '';
?>

<?php if (!empty($elements)) : ?>
	<table class="jcc-table">
		<?php foreach ($elements as $element) : ?>
			<tr>
				<th><?php echo Ra_mailmanHelper::renderTranslatableHeader($table_name, $field_name); ?></th>
				<td><?php echo Ra_mailmanHelper::renderElement(
						$table_name, $params->get('field'), $element->{$field_name}
					); ?></td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php endif;
