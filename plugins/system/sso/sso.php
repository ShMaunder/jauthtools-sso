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
 *
 * @package     JAuthTools
 * @subpackage  SSO
 * @since       1.5
 */
class plgSystemSSO extends JPlugin
{
	/**
	 * Run the onAfterInitialise trigger
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 */
	public function onAfterInitialise()
	{
		// Handle IP addresses that are not permitted to use SSO.
		$ip_blacklist = $this->params->get('ip_blacklist','');
		$list = array_map('trim', explode("\n", $ip_blacklist));

		if (in_array($_SERVER['REMOTE_ADDR'],$list))
		{
			JLog::add('Request from ' . $_SERVER['REMOTE_ADDR'] . ' ignored due to SSO system black list', JLog::DEBUG, 'sso');
			return false;
		}

		// By default we don't enable SSO in the administrator.
		if (!$this->params->get('backend', 0))
		{
			$app =& JFactory::getApplication();
			if($app->isAdmin())
			{
				return false;
			}
		}

		// By default we don't run SSO if the user is logged in.
		if (!$this->params->get('override',0))
		{
			$user =& JFactory::getUser();
			if($user->id)
			{
				return false;
			}
		}

		// Handle SSO auth!
		$sso = new JAuthSSOAuthentication();
		$sso->doSSOAuth($this->params->getValue('autocreate',false));
	}
}
