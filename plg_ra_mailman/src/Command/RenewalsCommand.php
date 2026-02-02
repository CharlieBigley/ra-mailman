<?php

/**
 * @version    1.0.3
 * @package    plg_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2025 Charlie Bigley
 *
 * @copyright   Copyright (C) 2005 - 2021 Clifford E Ford. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * 08/08/25 CB created from WalkslistCommand
 * 18/08/25 CB various changes
 */

namespace Ramblers\Plugin\System\Ra_mailman\Command;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Console\Command\AbstractCommand;
use \Ramblers\Component\Ra_mailman\Site\Helpers\Mailhelper;
use \Ramblers\Component\Ra_tools\Site\Helpers\ToolsHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class RenewalsCommand extends AbstractCommand {

    /**
     * The default command name
     *
     * @var    string
     *
     * @since  4.0.0
     */
    protected static $defaultName = 'ra_mailman:renewals';

    /**
     * @var InputInterface
     * @since version
     */
    private $cliInput;

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
        $messages = [];
    }

    /**
     * Initialise the command.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    protected function configure(): void {
        $help = "<info>%command.name%</info> Send reminder emails when renewal of subscription is approaching
            \nUsage: <info>php %command.full_name%
            \nNo parameters are available</info>";

        $this->setDescription('Called by cron to process renewals.');
        $this->setHelp($help);
        //      Set up maximum time of 10 mins (should be parameter in config
        $max = 10 * 60;
        set_time_limit($max);
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
     *
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

        $params = ComponentHelper::getParams('com_ra_mailman');
        $notify_interval = $params->get('notify_interval', 7);
        $body = 'Processing ' . $max_emails . ' emails';
        $this->ioStyle->comment($body);

        $mailHelper = new Mailhelper;
        $toolsHelper = new ToolsHelper;

        $toolsHelper->createLog('RA Mailman', 2, $id, $body);

        $message = "Seeking Subscriptions in " . $notify_interval . ' days time';
        $this->ioStyle->comment($message);

        $sql = "SELECT s.user_id, MIN(datediff(expiry_date, CURRENT_DATE)) ";
        $sql .= "FROM `#__ra_mail_subscriptions` AS s ";
        $sql .= "WHERE (s.state =1) ";
        $sql .= "AND ((datediff(expiry_date, CURRENT_DATE) < " . $notify_interval . ') ';
        $sql .= " AND (s.reminder_sent IS NULL)) ";
        $sql .= "GROUP BY s.user_id ";
        $sql .= "ORDER BY s.user_id ";
        $sql .= 'LIMIT 5';
        //       echo $sql . PHP_EOL;
        $rows = $this->toolsHelper->getRows($sql);
        if ($this->toolsHelper->rows == 0) {
            $this->ioStyle->comment('No renewals outstanding');
            return 1;
        }
        $message = $this->toolsHelper->rows . ' records found <br>';
        $this->ioStyle->comment($message);

//        $this->logMessage("R3", 3, "Number of Subscriptions due=" . $toolsHelper->rows);
        $sql = 'UPDATE `#__ra_mail_subscriptions` SET reminder_sent=CURRENT_DATE WHERE id=';

        foreach ($rows as $row) {
            echo "id=$row->user_id<br>";
//                $this->logMessage("R4", $row->user_id, "id:" . $row->id . "," . $row->expiry_date);
            if ($Mailhelper->sendRenewal($row->user_id)) {
                echo $row->user_id . ' renewed ' . '<br>';
            } else {
                echo $row->user_id . ' failed ' . '<br>';
            }
        }


        if ($id == 0) {
            $this->ioStyle->comment('No emails outstanding');
            return 1;
        }
        $message = 'Sending ' . $emails_outstanding . ' emails for ' . $name;
        $this->ioStyle->comment($message);
        $toolsHelper->createLog('RA Mailman', 2, $id, $message);
        $body .= $message;
        $mailHelper->user_id = 1;
        $last_mailshot = $mailHelper->lastMailshot($id);
        $this->ioStyle->comment('Sending emails for ' . $last_mailshot->id);
        $mailHelper->sendEmails($last_mailshot->id);

        foreach ($mailHelper->messages as $message) {
            $this->ioStyle->comment($message);
            $body .= $message . '<br>';
        }
//        $body .= $mailHelper->message;
        $this->ioStyle->comment($body);

        $to = 'hyperbigley@gmail.com';
        $reply_to = 'hyperbigley@gmail.com';
        $title = 'Emails sent';

        $toolsHelper->sendEmail($to, $reply_to, $title, $body);
        $this->ioStyle->comment('Finished');
        return 1;
    }

}
