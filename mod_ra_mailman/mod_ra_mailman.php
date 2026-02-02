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

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Ramblers\Module\Ra_mailman\Site\Helper\Ra_mailmanHelper;

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wr = $wa->getRegistry();
$wr->addRegistryFile('media/mod_ra_mailman/joomla.asset.json');
$wa->useStyle('mod_ra_mailman.style')
    ->useScript('mod_ra_mailman.script');

require ModuleHelper::getLayoutPath('mod_ra_mailman', $params->get('content_type', 'blank'));
