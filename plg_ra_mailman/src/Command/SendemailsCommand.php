<?php

/**
 * @version    1.0.7
 * @package    plg_ra_mailman
 * @author     Charlie Bigley <webmaster@bigley.me.uk>
 * @copyright  2025 Charlie Bigley
 *
 * @copyright   Copyright (C) 2005 - 2021 Clifford E Ford. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * This gets copied to plugins/console/ra_mailman/src/Command/SensEmailsCommand.php
 *
 * 08/08/25 CB created from WalkslistCommand
 * 05/12/25 CB tidy message, log finish time
 * 11/10/25/CB correct message logging
 * 16/10/25 CB use ToolsHelper to create logfile record
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

class SendemailsCommand extends AbstractCommand {

    /**
     * The default command name
     *
     * @var    string
     *
     * @since  4.0.0
     */
    protected static $defaultName = 'ra_mailman:sendemails';

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
    protected $ref;
    private $toolsHelper;

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
        $help = "<info>%command.name%</info> Send outstanding emails
            \nUsage: <info>php %command.full_name%
            \nNo parameters are available</info>";

        $this->setDescription('Called by cron to send emails.');
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
        $this->ioStyle->comment('Processing started');
        $mailHelper = new Mailhelper;
        $this->toolsHelper = new ToolsHelper;

        $params = ComponentHelper::getParams('com_ra_mailman');
        $max_emails = $params->get('max_emails', 120);

        $sql = 'SELECT id, group_code, name, emails_outstanding ';
        $sql .= 'FROM #__ra_mail_lists ';
        $sql .= 'WHERE emails_outstanding>0 ORDER BY group_code, name';
        $rows = $this->toolsHelper->getRows($sql);
        $this->ref = 0;
        $id = 0;
        foreach ($rows as $row) {
            if ($id == 0) {
                $id = $row->id;
                $name = $row->group_code . '/' . $row->name;
                $emails_outstanding = $row->emails_outstanding;
            }
        }
        if ($id == 0) {
            $this->ioStyle->comment('No emails outstanding');
            return 1;
        }

        if ($emails_outstanding > $max_emails) {
            $message = 'Sending ' . $max_emails . ' out of ' . $emails_outstanding;
        } else {
            $message = 'Sending ' . $emails_outstanding;
        }
        $message .= ' emails for ' . $name;
        $this->ioStyle->comment($message);
        $this->logit($message, 1);

        $message . '<br>';
        $mailHelper->user_id = 1;           // A value is required when creating subscriptions
//      $this->toolsHelper->user_id = 1;          // A value is required when creating emails
        $last_mailshot = $mailHelper->lastMailshot($id);
        $this->ref = $last_mailshot->id;
        $this->ioStyle->comment('Sending emails for ' . $this->ref);
        $mailHelper->sendEmails($last_mailshot->id);
        $this->ioStyle->comment('After helper ');
        foreach ($mailHelper->messages as $message) {
            $this->ioStyle->comment($message);
            $body .= $message . '<br>';
        }

        $date = Factory::getDate();
        $message = 'Processing finished ' . HTMLHelper::_('date', $date, 'H:i d/m/y') . ' GMT';
        $this->logit($message, 9);

//        $body .= $message . '<br>';
//        $$this->logit('RA Mailman', 2, $id, $message);
//        $this->ioStyle->comment($body);
//        $to = 'hyperbigley@gmail.com';
//        $reply_to = 'hyperbigley@gmail.com';
//        $title = 'Emails sent';
//        $toolsHelper->sendEmail($to, $reply_to, $title, $body);
        $this->ioStyle->comment('Finished');
        return 1;
    }

    /**
     *   Store a log entry
     */
    public function logit($message, $record_type = '3') {
        $this->toolsHelper->createLog('RA Mailman', $record_type, $this->ref, $message);
//    $query = $this->db->getQuery(true);
//
//    $query->insert('#__ra_logfile')
//            ->set("sub_system = " . $this->db->quote('RA Mailman'))
//            ->set("record_type = " . $this->db->quote($record_type))
//            ->set("ref = " . $this->db->quote($this->ref))
//            ->set("message = " . $this->db->quote($text))
//    ;
////        $this->ioStyle->comment($query);
//    $result = $this->db->setQuery($query)->execute();
    }

}
