<?php
/**
 * Example Type C authentication plugin.
 *
 * This is an example of how an SSO type C plugin works.
 *
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

/**
 * SSO Type C authentication plugin example
 *
 * @package     JAuthTools
 * @subpackage  SSO
 */
class plgSSOTypeC extends JPlugin {
	/**
	 * Return the SSO plugin type
	 *
	 * @return  string  The type of plugin.
	 *
	 * @since   1.5.0
	 */
	public function getSSOPluginType() {
		return 'C';
	}

	/**
	 * Detect a remote user from the request variable "remote_username".
	 *
	 * @return  string  The username of the user or false.
	 *
	 * @since   1.5.0
	 */
	function detectRemoteUser() {
		$remote_user = JRequest::getVar('remote_username','');
		if($remote_user) {
			return $remote_user;
		} else {
			return false;
		}
	}

	/**
	 * Get a form for this plugin to be displayed in a module or component.
	 *
	 * @return  string  The rendered HTML to display.
	 *
	 * @since   1.5.0
	 */
	public function getForm() {
		$component = JComponentHelper::getComponent('com_sso', true);
		$result = '<form method="post" action="'. JURI::base() .'">'
			. 'Requested Username: '
			. '<input type="text" name="remote_username" value="" />'
			. '<input type="submit" value="Login" />';
		if($component->enabled) {
			$result .= '<input type="hidden" name="option" value="com_sso">'
			. '<input type="hidden" name="task" value="delegate">'
			. '<input type="hidden" name="plugin" value="typec">';
		}
		$result .= '</form>';
		return $result;
	}
} 
