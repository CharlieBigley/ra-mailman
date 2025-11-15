<?php
/**
 * 4.1.12
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 22/10/24 CB use separate tab for publishing
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
        ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');

if ($this->item->group_primary == '') {
    echo '<p><b>This is not the Primary List</b></p>';
} else {
    echo '<p><b>This is the Primary List and can be used for official communications<i class="fas fa-star fa-fw"></i></b></p>';
}
?>

<form
    action="<?php echo Route::_('index.php?option=com_ra_mailman&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="mail_lst-form" class="form-validate form-horizontal">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'Maillist')); ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Maillist', 'Mail list'); ?>
    <div class="row-fluid">
        <div class="span10 form-horizontal">
            <fieldset class="adminform">
                <?php echo $this->form->renderField('group_code'); ?>
                <?php echo $this->form->renderField('name'); ?>
                <?php echo $this->form->renderField('owner_id'); ?>
                <?php echo $this->form->renderField('record_type'); ?>
                <?php echo $this->form->renderField('home_group_only'); ?>
                <?php echo $this->form->renderField('chat_list'); ?>
                <?php echo $this->form->renderField('footer'); ?>
            </fieldset>
        </div>
    </div>
    <input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
    <input type="hidden" name="jform[state]" value="<?php //echo $this->item->state;            ?>" />
    <input type="hidden" name="jform[ordering]" value="<?php //echo $this->item->ordering;            ?>" />
    <input type="hidden" name="jform[checked_out_time]" value="<?php //echo $this->item->checked_out_time;            ?>" />
    <input type="hidden" name="jform[contact_details]" value="<?php //echo $this->item->contact_details;            ?>" />
    <input type="hidden" name="jform[url]" value="<?php //echo $this->item->url;            ?>" />
    <input type="hidden" name="jform[url_description]" value="<?php //echo $this->item->url_description;            ?>" />
    <input type="hidden" name="jform[attachment]" value="<?php //echo $this->item->attachment;            ?>" />
    <input type="hidden" name="jform[attachment_description]" value="<?php //echo $this->item->attachment_description;            ?>" />
    <?php
    echo HTMLHelper::_('uitab.endTab');
    echo HTMLHelper::_('uitab.addTab', 'myTab', 'event2', Text::_('Publishing', true));
    echo $this->form->renderField('created_by');
    echo $this->form->renderField('created');
    echo $this->form->renderField('modified_by');
    echo $this->form->renderField('modified');
    echo $this->form->renderField('state');
    echo $this->form->renderField('group_primary');
    echo $this->form->renderField('id');
    echo HTMLHelper::_('uitab.endTab');
    echo HTMLHelper::_('uitab.endTabSet');
    ?>
    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>

</form>
