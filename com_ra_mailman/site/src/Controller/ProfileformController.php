<?php

/**
 * @version    4.5.7
 * @package    com_ra_mailman
 * @author     Charlie Bigley <charlie@bigley.me.uk>
 * @copyright  2025 Charlie Bigley
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Ramblers\Component\Ra_mailman\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Profile class.
 *
 * @since  4.5.7
 */
class ProfileformController extends FormController {

    /**
     * Method to check out an item for editing and redirect to the edit form.
     *
     * @return  void
     *
     * @since   4.5.7
     *
     * @throws  Exception
     */
    public function edit($key = NULL, $urlVar = NULL) {
        // Get the previous edit id (if any) and the current edit id.
        $previousId = (int) $this->app->getUserState('com_ra_mailman.edit.profile.id');
        $editId = $this->input->getInt('id', 0);

        // Set the user id for the user to edit in the session.
        $this->app->setUserState('com_ra_mailman.edit.profile.id', $editId);

        // Get the model.
        $model = $this->getModel('Profileform', 'Site');

        // Check out the item
        if ($editId) {
            $model->checkout($editId);
        }

        // Check in the previous user.
        if ($previousId) {
            $model->checkin($previousId);
        }

        // Redirect to the edit screen.
        $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=profileform&layout=edit', false));
    }

    /**
     * Method to save data.
     *
     * @return  void
     *
     * @throws  Exception
     * @since   4.5.7
     */
    public function save($key = NULL, $urlVar = NULL) {
        // Check for request forgeries.
        $this->checkToken();

        // Initialise variables.
        $model = $this->getModel('Profileform', 'Site');
        if (is_null($model)) {
            $this->app->enqueueMessage('Unable to get Model', 'error');
            return false;
        }
        // Get the user data.
        $data = $this->input->get('jform', array(), 'array');

        // Validate the posted data.
        $form = $model->getForm();

        if (!$form) {
            throw new \Exception($model->getError(), 500);
        }

        // Send an object which can be modified through the plugin event
        $objData = (object) $data;
        $this->app->triggerEvent(
                'onContentNormaliseRequestData',
                array($this->option . '.' . $this->context, $objData, $form)
        );

        $data = (array) $objData;

        // Validate the posted data.
        $data = $model->validate($form, $data);

        // Check for errors.
        if ($data === false) {
            // Get the validation messages.
            $errors = $model->getErrors();

            // Push up to three validation messages out to the user.
            for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
                if ($errors[$i] instanceof \Exception) {
                    $this->app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                } else {
                    $this->app->enqueueMessage($errors[$i], 'warning');
                }
            }

            $jform = $this->input->get('jform', array(), 'ARRAY');

            // Save the data in the session.
            $this->app->setUserState('com_ra_mailman.edit.profile.data', $jform);

            // Redirect back to the edit screen.
            $id = (int) $this->app->getUserState('com_ra_mailman.edit.profile.id');
            $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=profileform&layout=edit&id=' . $id, false));

            $this->redirect();
        }
        // Attempt to create a new user and a new profile
        $new_user_id = $model->save($data);

        // Check for errors.
        if ($new_user_id === false) {
            // Save the data in the session.
            $this->app->setUserState('com_ra_mailman.edit.profile.data', $data);

            // Redirect back to the edit screen.
            $menu = Factory::getApplication()->getMenu();
            $item = $menu->getActive();
            $url = $item->link . '&Itemid=' . $item->id;
//            var_dump($item->id);
//            die($url);
            $this->setRedirect(Route::_($url, false));
            $this->redirect();
        }

        if ($new_user_id == 0) {
            $this->app->enqueueMessage('Unable to create User', 'error');
//            die('Unable to create user');
            return false;
        }

        // Clear the profile id from the session.
        $this->app->setUserState('com_ra_mailman.edit.profile.id', null);

        // Flush the data from the session.
        $this->app->setUserState('com_ra_mailman.edit.profile.data', null);

        // If self registering, redirect to thank you screen
        // else show available mailing lists
        $current_user = $this->app->getSession()->get('user')->id;
        //    var_dump($current_user);
        //    die;
        if ($current_user == 0) {   // Self registering, redirect to welcome screen
            $url = 'index.php?option=com_ra_mailman&task=profile.showWelcome&user_id=' . $new_user_id;
        } else {                    // Administrator, redirect to allow selection of lists
            $url = 'index.php?option=com_ra_mailman&view=list_select&user_id=' . $new_user_id;
        }
//        $this->app->enqueueMessage('Redirecting to ' . $url, 'info');
        $this->setRedirect(Route::_($url, false));
        $this->redirect();
//        // Attempt to save the data.
//        $return = $model->save($data);
//
//        // Check for errors.
//        if ($return === false) {
//            // Save the data in the session.
//            $this->app->setUserState('com_ra_mailman.edit.profile.data', $data);
//
//            // Redirect back to the edit screen.
//            $id = (int) $this->app->getUserState('com_ra_mailman.edit.profile.id');
//            $this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
//            $this->setRedirect(Route::_('index.php?option=com_ra_mailman&view=profileform&layout=edit&id=' . $id, false));
//            $this->redirect();
//        }
//
//        // Check in the profile.
//        if ($return) {
//            $model->checkin($return);
//        }
//
//        // Clear the profile id from the session.
//        $this->app->setUserState('com_ra_mailman.edit.profile.id', null);
//
//        // Redirect to the list screen.
//        if (!empty($return)) {
//            $this->setMessage(Text::_('COM_RA_MAILMAN_ITEM_SAVED_SUCCESSFULLY'));
//        }
//
//        $menu = Factory::getApplication()->getMenu();
//        $item = $menu->getActive();
//        $url = (empty($item->link) ? 'index.php?option=com_ra_mailman&view=profiles' : $item->link);
//        $this->setRedirect(Route::_($url, false));
//
//        // Flush the data from the session.
//        $this->app->setUserState('com_ra_mailman.edit.profile.data', null);
//
//        // Invoke the postSave method to allow for the child class to access the model.
//        $this->postSaveHook($model, $data);
    }

    /**
     * Method to abort current operation
     *
     * @return void
     *
     * @throws Exception
     */
    public function cancel($key = NULL) {
        // Get the current edit id.
        $editId = (int) $this->app->getUserState('com_ra_mailman.edit.profile.id');

        // Get the model.
        $model = $this->getModel('Profileform', 'Site');

        // Check in the item
        if ($editId) {
            $model->checkin($editId);
        }

        $this->setRedirect('index.php');
        $this->redirect();
    }

    /**
     * Method to remove data
     *
     * @return  void
     *
     * @throws  Exception
     *
     * @since   4.5.7
     */
    public function remove() {
        $model = $this->getModel('Profileform', 'Site');
        $pk = $this->input->getInt('id');

        // Attempt to save the data
        try {
            // Check in before delete
            $return = $model->checkin($return);
            // Clear id from the session.
            $this->app->setUserState('com_ra_mailman.edit.profile.id', null);

            $menu = $this->app->getMenu();
            $item = $menu->getActive();
            $url = (empty($item->link) ? 'index.php?option=com_ra_mailman&view=profiles' : $item->link);

            if ($return) {
                $model->delete($pk);
                $this->setMessage(Text::_('COM_RA_MAILMAN_ITEM_DELETED_SUCCESSFULLY'));
            } else {
                $this->setMessage(Text::_('COM_RA_MAILMAN_ITEM_DELETED_UNSUCCESSFULLY'), 'warning');
            }


            $this->setRedirect(Route::_($url, false));
            // Flush the data from the session.
            $this->app->setUserState('com_ra_mailman.edit.profile.data', null);
        } catch (\Exception $e) {
            $errorType = ($e->getCode() == '404') ? 'error' : 'warning';
            $this->setMessage($e->getMessage(), $errorType);
            $this->setRedirect('index.php?option=com_ra_mailman&view=profiles');
        }
    }

    /**
     * Function that allows child controller access to model data
     * after the data has been saved.
     *
     * @param   BaseDatabaseModel  $model      The data model object.
     * @param   array              $validData  The validated data.
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function postSaveHook(BaseDatabaseModel $model, $validData = array()) {

    }

}
