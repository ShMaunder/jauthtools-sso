<?php
/**
 * SSO Manager Component Bootstrap 
 * 
 * PHP5
 *  
 * Created on Nov 21, 2008
 * 
 * @package     JAuthTools.SSO
 * @subpackage  com_ssomanager
 * @author      Sam Moffatt <pasamio@gmail.com>
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright   2012 (C) Sam Moffatt 
 */
 
 
// no direct access
defined('_JEXEC') or die('No direct access allowed ;)');

jimport('joomla.application.component.controller');

// Grab an instance of the controller
$controller = JController::getInstance('SSOManager');

// Perform the Request task
$controller->execute( JRequest::getVar( 'task' ) );

// Redirect if set by the controller
$controller->redirect();

