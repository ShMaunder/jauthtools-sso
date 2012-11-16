<?php
/**
 * SSO Login System
 * 
 * This starts an SSO Login. SSO Login may occur via a variety of sources 
 *  
 * Created on Apr 17, 2007
 * 
 * @package    JAuthTools
 * @author     Sam Moffatt <pasamio@gmail.com>
 * @license    GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright  2012 Sam Moffatt 
 * @see        JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');
jimport('jauthtools.sso');
jimport('jauthtools.usersource');

// Configure the loggers to pick up the sso system
$config = JFactory::getConfig();
$logger = array();
$logger['text_file'] = 'jauthtools.log.php';
$logger['text_file_path'] = $config->get('log_path');
$logger['logger'] = 'formattedtext';
JLog::addLogger($logger, JLog::ALL, array('sso', 'usersource', 'jauthtools'));

/**
 * SSO Initiation
 * Kicks off SSO Authentication
 * @package     JAuthTools
 * @subpackage  SSO 
 * @since       1.5
 */
class plgSystemSSO extends JPlugin {
	/**
	 * Run the onAfterInitialise trigger
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 */
	public function onAfterInitialise() {
		$params = $this->params;
		$ip_blacklist = $params->get('ip_blacklist','');
		$list = explode("\n", $ip_blacklist);
		if(in_array($_SERVER['REMOTE_ADDR'],$list)) {
			return false;
		}	

		if(!$params->get('backend',0)) {
			$app =& JFactory::getApplication();
			if($app->isAdmin()) return false;
		}
	
		if(!$params->get('override',0)) {
			$user =& JFactory::getUser();
			if($user->id) return false;
		}
	
		$sso = new JAuthSSOAuthentication();
		$sso->doSSOAuth($params->getValue('autocreate',false));
	}
}
