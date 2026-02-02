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

$element = Ra_mailmanHelper::getItem($params);
?>

<?php if (!empty($element)) : ?>
	<div>
		<?php $fields = get_object_vars($element); ?>
		<?php foreach ($fields as $field_name => $field_value) : ?>
			<?php if (Ra_mailmanHelper::shouldAppear($field_name)): ?>
				<div class="row">
					<div class="span4">
						<strong><?php echo Ra_mailmanHelper::renderTranslatableHeader($params->get('item_table'), $field_name); ?></strong>
					</div>
					<div
						class="span8"><?php echo Ra_mailmanHelper::renderElement($params->get('item_table'), $field_name, $field_value); ?></div>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
<?php endif;
