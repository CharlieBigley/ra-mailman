<?php

/**
 * @package     Ramblers.Walks
 * @subpackage  System.ra_mailman
 *
 * @copyright   (C) 2024 Charlie Bigley
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 08/08/25 CB created
 */

namespace Ramblers\Plugin\System\Ra_mailman\Extension;

//namespace Ramblers\Plugin\System\Onoffbydate\Extension;
\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Ramblers\Plugin\System\Ra_mailman\Command\SendemailsCommand;

class Ra_mailman extends CMSPlugin {

    protected $app;

    public function __construct(&$subject, $config = []) {
        parent::__construct($subject, $config);

        if (!$this->app->isClient('cli')) {
            return;
        }

        $this->registerCLICommands();
    }

    public static function getSubscribedEvents(): array {
        if ($this->app->isClient('cli')) {
            return [
                Joomla\Application\ApplicationEvents\ApplicationEvents::BEFORE_EXECUTE => 'registerCLICommands',
            ];
        }
    }

    public function registerCLICommands() {

        $commandObject = new SendemailsCommand;
        $this->app->addCommand($commandObject);
    }

}
