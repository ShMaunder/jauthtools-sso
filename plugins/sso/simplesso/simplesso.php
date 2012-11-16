<?php
/**
 * SSO JAuthTools SimpleSSO Plugin 
 * 
 * This file handles Simple SSO 
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
jimport('jauthtools.sso'); // should be included already

/**
 * SSO SimpleSSO
 * Attempts to match a user based on a key which is valid with SimpleSSO
 */
class plgSSOSimpleSSO extends JPlugin {
	/**
	 * Detect a remote user from a SimpleSSO request.
	 *
	 * @return  string  The username of the remote user or false if unknown.
	 *
	 * @since   1.5.0
	 */
	public function detectRemoteUser() {
		$providers = JAuthSSOAuthentication::getProvider('simplesso');
		foreach($providers as $provider) {
			$user = $this->_detectUser($provider);
			if ($user !== false)
			{
				return $user;
			}
		}
		
		return false;
	}

	/**
	 * Detect the user for a given instance
	 *
	 * @param   object  $instance  An object representing the SimpleSSO provider.
	 *
	 * @return  string  The detected username.
	 *
	 * @since   1.5.0
	 */	
	private function _detectUser($instance) {
		$key = JRequest::getVar('authkey','');
		$params = new JRegistry();
		$params->loadJSON($instance->params);
 	 	$supplier = $params->getValue('supplier',''); 
		$suffix = $params->getValue('suffix','');

		// grab the file; check the supplier and key are set to something
		if(function_exists('curl_init') && $supplier && $key)
		{
			$url = $supplier.'/?token='.$key;
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);				
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			$result = curl_exec($curl);
			$xml =& JFactory::getXMLParser('Simple');
			$xml->loadString($result);
			$rootAttr = $xml->document->attributes();
			if(isset($rootAttr['type']) && $rootAttr['type'] == 'user') {
				$children = $xml->document->children();
				foreach($children as $child) {
					if($child->name() == 'user') {
						$userattr = $child->attributes();
						$userdetails = new stdClass();
						$userdetails->username = str_replace($suffix,'',$userattr['username']);
						$userdetails->name = $userattr['name'];
						$userdetails->email = $userattr['email'];
						
						$session =& JFactory::getSession();
						$sessiondetails =& $session->get('UserSourceDetails',Array());
						$sessiondetails[] = $userdetails;
						$session->set('UserSourceDetails', $sessiondetails);
						return $userdetails->username;
					}	
				}
			}
		}
	}

	/**
	 * Get service provider link for a given instance.
	 *
	 * @param   object  $instance  An object representing an SSO instance.
	 *
	 * @return  string  A link to the service provider.
	 *
	 * @since   1.5.0
	 */	
	public function getSPLink($instance) {
		$instance_params = new JRegistry();
		$instance_params->loadJSON($instance->params);
		$params = clone($this->params); // take a copy of this to prevent the instance overloading the default
		$params->merge($instance_params); // merge over the new params
		$supplier = $params->get('supplier');
		
		$base = JAuthSSOAuthentication::getBaseURL($params->get('prefer_component',true),'simplesso');
		if(strpos($supplier, '?')) {
			$supplier .= '&landingpage='. $base;
		} else {
			$supplier .= '?landingpage='. $base;
		}
		return $supplier;
	}
}
