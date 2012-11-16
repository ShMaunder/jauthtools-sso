<?php
/**
 * SSO JAuthTools Token Login Plugin 
 * 
 * This file handles token logins 
 *  
 * Created on July 3, 2008
 * 
 * @package JAuthTools
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2012 Sam Moffatt 
 * @see JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');
jimport('jauthtools.token');

/**
 * SSO SimpleSSO
 * Attempts to match a user based on a key which is valid with SimpleSSO
 */
class plgSSOTokenLogin extends JPlugin {
	/**
	 * Detect a remote user using a token in the request.
	 *
	 * @return  string  The detected user or false if there was no user.
	 *
	 * @since   1.5.0
	 */
	public function detectRemoteUser() {
		$key = JRequest::getVar('logintoken','');
		if($key) {
			$result = JAuthToolsToken::validateToken($key);
			if($result) {
				return $result->username;
			}
		}
		return false;
	}
}
