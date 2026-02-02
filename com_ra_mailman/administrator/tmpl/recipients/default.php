<?php
/**
 * @version    4.5.8
 * @package    com_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2023 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 11/11/25 CB Created
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');

// Import CSS
$wa = $this->document->getWebAssetManager();
$wa->registerAndUseStyle('ramblers', 'com_ra_tools/ramblers.css');

$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
$canEdit = $this->user->authorise('core.edit', 'com_ra_mailman');
$objHelper = new ToolsHelper;
$self = 'index.php?option=com_ra_mailman&view=recipients';
$target_mailshot = 'administrator/index.php?option=com_ra_mailman&task=mailshot.showMailshot&callback=recipients&tmpl=component&id=';
;
?>

<form action="<?php echo Route::_($self); ?>" method="post"
      name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

                <div class="clearfix"></div>
                <?php
                echo '<table class="table mintcake table-striped" id="mailshotList">' . PHP_EOL;
                echo '<thead>' . PHP_EOL;
                echo '<tr>' . PHP_EOL;
                echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Sent', 'm.date_sent', $listDirn, $listOrder) . '</th>';

                echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Mail list', 'mail_list.name', $listDirn, $listOrder) . '</th>';
                echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Mailshot', 'm.title', $listDirn, $listOrder) . '</th>';
                echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'User', 'p.preferred_name', $listDirn, $listOrder) . '</th>';
                echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'Email', 'e.email', $listDirn, $listOrder) . '</th>';
                echo '<th class="left">' . HTMLHelper::_('searchtools.sort', 'ID', 'a.id', $listDirn, $listOrder) . '</th>';
                echo '</tr>' . PHP_EOL;
                echo '</thead>' . PHP_EOL;
                echo '<tbody>' . PHP_EOL;
                foreach ($this->items as $i => $item) :
                    echo '<tr class="' . $i % 2 . '">';

                    echo '<td>';
                    if (is_null($item->date_sent)) {
                        echo '(not sent)';
                    } else {
                        echo HTMLHelper::_('date', $item->date_sent, 'd/m/y H:i:s ');
                    }
                    echo '</td>' . PHP_EOL;

                    echo '<td>' . $item->list_name . '</td>';
                    echo '<td>';
                    echo $objHelper->buildLink($target_mailshot . $item->mailshot_id, $item->title);
//                    echo $item->title . '</a>';
                    echo '</td>' . PHP_EOL;
                    echo '<td>' . $item->preferred_name . '</td>';
                    echo '<td>' . $item->email . '</td>';
                    echo '<td>' . $item->id . '</td>';
                    ?>

                    </tr>
                <?php endforeach; ?>
                </tbody>
                </table>

                <input type="hidden" name="task" value=""/>
                <input type="hidden" name="boxchecked" value="0"/>
                <input type="hidden" name="list[fullorder]" value="<?php echo $listOrder; ?> <?php echo $listDirn; ?>"/>
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>