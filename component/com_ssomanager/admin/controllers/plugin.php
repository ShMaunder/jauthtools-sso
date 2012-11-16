<?php
/**
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

/**
 * Plugin controller class.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_plugins
 * @since		1.6
 */
class SSOManagerControllerPlugin extends JControllerForm
{
    /**
     * Override the default edit function to add in the mode
     *
     * @return  void
     *
     * @since   2.5.0
     */
    public function edit()
    {
        parent::edit();
        $redirect = new JURI($this->redirect);
        $redirect->setVar('mode', JRequest::getCmd('mode'));
        $this->setRedirect($redirect);
    }

    /**
     * Override the default save controller and send them somewhere else.
     *
     * @return  void
     *
     * @since   2.5.0
     */
    public function save()
    {
        parent::save();
        $mode = JRequest::getCmd('mode', 'sso');
        $this->setRedirect('index.php?option=com_ssomanager&task=entries&mode=' . $mode);
    }
 
    /**
     * Override the default cancel controller and send them somewhere else.
     *
     * @return  void
     *
     * @since   2.5.0
     */
    public function cancel()
    {
        parent::cancel();
        $mode = JRequest::getCmd('mode', 'sso');
        $this->setRedirect('index.php?option=com_ssomanager&task=entries&mode=' . $mode);
    }
}
