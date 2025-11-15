<?php
/**
 * @version    4.5.5
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 12/09/24 CB display existing attachments, add hidden field attachment_hidden
 * 22/10/24 CB use separate tab for publishing
 * 03/10/25 CB add field event_id
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

$toolsHelper = new ToolsHelper;
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
        ->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');
echo '<h4>' . $this->list_name . '</h4>';
$self = 'index.php?option=com_ra_mailman&view=mailshot&layout=edit';
$self .= '&id=' . (int) $this->item->id . '&list_id=' . (int) $this->list_id;
?>

<form
    action="<?php echo Route::_($self); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="mailshot-form" class="form-validate form-horizontal">


    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'Maillist')); ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Maillist', 'Mail list'); ?>
    <div class="row-fluid">
        <div class="span10 form-horizontal">
            <fieldset class="adminform">
                <legend><?php //echo 'Mailshot';                        ?></legend>
                <?php
                echo $this->form->renderField('title');
                echo $this->form->renderField('body');
                if (ToolsHelper::isInstalled('com_ra_events')) {
                    $sql = 'SELECT COUNT(id) FROM `#__ra_events` ';
                    $sql .= 'WHERE bookable=1 AND DATEDIFF(event_date, CURRENT_DATE)>0 ';
                    $sql .= 'AND api_site_id IS NULL ';
                    $count = $toolsHelper->getValue($sql);
                    if ($count > 0) {
                        echo $this->form->renderField('event_id');
                    }
                }
                echo $this->form->renderField('attached_file');

                echo $this->form->renderField('attachment');
                if (!empty($this->item->attachment)) {
                    $attachmentFiles = array();
                    foreach ((array) $this->item->attachment as $fileSingle) {
                        if (!is_array($fileSingle)) {
                            $target = Route::_(Uri::root() . 'images/com_ra_mailman' . DIRECTORY_SEPARATOR . $fileSingle);
                            echo $toolsHelper->buildLink($target, $fileSingle, true);
                        }
                    }
                }
                if (!$this->date_sent == '0000-00-00') {
                    echo $this->form->renderField('date_sent');
                }
                echo $this->form->renderField('mail_list_id');
                ?>

            </fieldset>
        </div>
    </div>

    <?php
    echo HTMLHelper::_('uitab.endTab');
    echo HTMLHelper::_('uitab.addTab', 'myTab', 'publishing', 'Publishing');
    echo $this->form->renderField('created_by');
    echo $this->form->renderField('created');
    echo $this->form->renderField('modified_by');
    echo $this->form->renderField('modified');
    echo $this->form->renderField('id');
    echo $this->form->renderField('record_type');
    echo HTMLHelper::_('uitab.endTab');
    ?>
    <input type="hidden" name="jform[id]" value="<?php //echo $this->item->id;                      ?>" />
    <input type="hidden" name="jform[state]" value="<?php //echo $this->item->state;                        ?>" />

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>

</form>
