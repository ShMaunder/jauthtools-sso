<?php
/**
 * HTTP Based SSO
 * 
 * This plugin uses server variables to identify the user.
 *  
 * Created on Apr 17, 2007
 * 
 * @package JAuthTools
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2012 Sam Moffatt 
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */
 
defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');
/**
 * SSO HTTP Source
 * Attempts to match a user based on the supplied server variables
 * @package     JAuthTools
 * @subpackage  SSO 
 */
class plgSSOHTTP extends JPlugin {
	/**
	 * Detect a remote user from the HTTP request (e.g. web server auth)
	 *
	 * @return  string  The detected user or false.
	 *
	 * @since   1.5.0
	 */	
	public function detectRemoteUser() {
		$params = $this->params;
		$ip_blacklist = $params->get('ip_blacklist','');
		$list = explode("\n", $ip_blacklist);
		if(in_array($_SERVER['REMOTE_ADDR'],$list)) {
			return false;
		}		
		$remote_user = JArrayHelper::getValue($_SERVER,$params->getValue('userkey','REMOTE_USER'),'');
		$replace_set = explode('|', $params->getValue('username_replacement',''));
		foreach($replace_set as $replacement) {
			$remote_user = str_replace($replacement,'',$remote_user);
		}
		return $remote_user;
	}
}

