<?php

/**
 * @version    1.0.9
 * @package    plg_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2026 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *
 * 08/04/26 CB created
 * 
 * This should have an option to specify the number of users to load at a time, to avoid memory issues.
 */

namespace Ramblers\Plugin\System\Ra_mailman\Command;

\defined('JPATH_PLATFORM') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Console\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use \Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;

class LoadusersCommand extends AbstractCommand {

    /**
     * The default command name
     *
     * @var    string
     *
     * @since  4.0.0
     */
    protected static $defaultName = 'ra_mailman:loadusers';

    /**
     * @var InputInterface
     * @since version
     */
    private $app;
    private $cliInput;
    private $db;
    private $mailHelper;
    private $toolsHelper;
    
    /**
     * SymfonyStyle Object
     * @var SymfonyStyle
     * @since 4.0.0
     */
    private $ioStyle;

    /**
     * Instantiate the command.
     *
     * @since   4.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->db = Factory::getDbo();
        $this->toolsHelper = new ToolsHelper;
        $this->mailHelper = new Mailhelper;
        $this->app = Factory::getApplication();
    }

    /**
     * Initialise the command.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    protected function configure(): void {
        $help = "<info>%command.name%</info> Load users
            \nUsage: <info>php %command.full_name% [--code=<code>]</info>
            \nOptions:
            \n  --code  Optional area code (2 chars) or group code (4 chars) to restrict loading. Loads all areas if omitted.";

        $this->setDescription('Load users into the mailing system.');
        $this->setHelp($help);
        $this->addOption('code', null, InputOption::VALUE_OPTIONAL, 'Area code (2 chars) or group code (4 chars) to load users for (loads all areas if omitted)', null);
    }

    /**
     * Configures the IO
     *
     * @param   InputInterface   $input   Console Input
     * @param   OutputInterface  $output  Console Output
     *
     * @return void
     *
     * @since 4.0.0
     */
    private function configureIO(InputInterface $input, OutputInterface $output) {
        $this->cliInput = $input;
        $this->ioStyle = new SymfonyStyle($input, $output);
    }

    /**
     * Internal function to execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  integer  The command exit code
     *
     * @since   4.0.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int {
        $this->configureIO($input, $output);
        $this->logit('Processing started', '1');
        $this->ioStyle->comment('Processing started');

        $code = $input->getOption('code');
       
        if ($code == null) {
            $sql = 'SELECT code FROM #__ra_areas';
            $sql .= ' ORDER BY code';

            $sql .= ' LIMIT 4'; // TEMP - for testing <<<<<<<<<<<<<<<<<<
        } else {
            $codeLen = strlen($code);
            if ($codeLen !== 2 && $codeLen !== 4) {
                $this->ioStyle->error('--code must be exactly 2 characters (area) or 4 characters (group).');
                return 1;
            }
            if ($codeLen === 2) {
                $check = $this->toolsHelper->getRows('SELECT code FROM #__ra_areas WHERE code = ' . $this->db->quote($code));
                if (empty($check)) {
                    $this->ioStyle->error('Area code ' . $code . ' not found in ra_areas.');
                    return 1;
                }
            } else {
                $check = $this->toolsHelper->getRows('SELECT code FROM #__ra_groups WHERE code = ' . $this->db->quote($code));
                if (empty($check)) {
                    $this->ioStyle->error('Group code ' . $code . ' not found in ra_groups.');
                    return 1;
                }
            }
            $sql = 'SELECT ' . $this->db->quote($code) . ' AS code';
        }

        $rows = $this->toolsHelper->getRows($sql);
        foreach ($rows as $row) {
            $this->logit('Processing ' . $row->code, '2');
            $this->mailHelper->loadUsers($row->code);
            foreach ($this->mailHelper->messages as $message) {
                $this->ioStyle->comment($message);
                $this->logit($message, '3');       
            }
        }        
        $this->ioStyle->comment('Processing complete');
        $this->logit('Processing complete', '9');
        return 0;
    }

        /**
     *   Store a log entry
     */
    public function logit($text, $record_type = '3', $ref = 'loadusers') {

        $query = $this->db->getQuery(true);

        $query->insert('#__ra_logfile')
                ->set("record_type = " . $this->db->quote($record_type))
                ->set("sub_system = " . $this->db->quote('RA Mailman'))
                ->set("message = " . $this->db->quote($text))
                ->set("ref = " . $this->db->quote($ref))
        ;

        $result = $this->db->setQuery($query)->execute();
    }

}
