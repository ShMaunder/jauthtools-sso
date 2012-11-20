<?php
/**
 * JAuthTools: SSO Authentication System
 *
 * This file handles SSO based Authentication
 *
 * Created on Apr 17, 2007
 *
 * @package    JAuthTools
 * @author     Sam Moffatt <pasamio@gmail.com>
 * @license    GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright  2009 - 2012 Sam Moffatt
 * @see        JoomlaCode Project: http://joomlacode.org/gf/project/jauthtools/
 */

defined('JPATH_BASE') or die();
jimport('joomla.base.observable');

/**
 * SSO Auth Handler
 * @package     JAuthTools
 * @subpackage  SSO
 */
class JAuthSSOAuthentication extends JObservable {
	/**
	 * Constructor
	 *
	 * @since  1.5
	 */
	public function __construct() {
		// Import SSO Library Files
		$isLoaded = JPluginHelper :: importPlugin('sso');
		if (!$isLoaded) {
			JLog::add(__CLASS__ . '::__construct: Could not load any SSO plugins.', JLog::ERROR, 'sso');
		}
	}

	/**
	 * Complete an SSO authentication process.
	 *
	 * @param   boolean  $autocreate  Flag to control if users should be automatically created if they don't exist.
	 *
	 * @return  void
	 * 
	 * @since   1.5
	 */
	public function doSSOAuth($autocreate=false)
	{
		// Load up SSO plugins and iterate through them.
		$plugins = JPluginHelper::getPlugin('sso');
		foreach ($plugins as $plugin)
		{
			$className = 'plg' . $plugin->type . $plugin->name;
			if (class_exists($className))
			{
				$plugin = new $className ($this, (array)$plugin);
			}
			else
			{
				JLog::add(__CLASS__ . '::doSSOAuth: Could not load ' . $className, JLog::INFO, 'sso');
				continue;
			}

			// Try to authenticate remote user.
			$username = $plugin->detectRemoteUser();
			
			// If authentication is successful break out of the loop
			if (!empty($username) && strlen($username))
			{
				// Check if we need to create the user and use the user source system for this.
				if($autocreate)
				{
					jimport('jauthtools.usersource');
					$usersource = new JAuthUserSource();
					$usersource->doUserCreation($username);
				}

				// Create the user's session and we're done.
				$this->doSSOSessionSetup($username);
				break;
			}
		}
	}

	/**
	 * Handle creating the user session for this user account.
	 *
	 * @param   string  $username  The username to create the session.
	 *
	 * @return  void
	 *
	 * @since   1.5.0
	 */
	public function doSSOSessionSetup($username)
	{
		// Get Database and find user
		$database = JFactory::getDBO();
		$query = $database->getQuery(1);
		$query->select('*')->from('#__users')->where($query->qn('username') . ' = ' . $query->q($username));
		$database->setQuery($query);
		$result = $database->loadAssocList();

		// If the user already exists, create their session. We don't create users here.
		if (count($result))
		{
			JLog::add(sprintf('Triggering session setup for "%s"', $username), JLog::DEBUG, 'sso');
			$result = $result[0];
			$options = array();
			$app =& JFactory::getApplication();
			if ($app->isAdmin())
			{
				// See if they can log into the admin
				$options['action'] = 'core.login.admin';
			}
			else
			{
				// See if they can log into the site
				$options['action'] = 'core.login.site';
			}
				
			// Make sure users are not autoregistered as we will handle this.
			$options['autoregister'] = false;
				
			// Fake the type for plugins that rely on this.
			$result['type'] = 'sso';

			// Import the user plugin group.
			JPluginHelper::importPlugin('user');
			$dispatcher =& JDispatcher::getInstance();

			// Log out the existing user if someone is logged into this client
			$user =& JFactory::getUser();
			if ($user->id)
			{
				// Build the credentials array
				$parameters['username'] = $user->get('username');
				$parameters['id']       = $user->get('id');
				$dispatcher->trigger('onUserLogout', Array($parameters, Array('clientid'=>Array($app->getClientId()))));
			}

			// OK, the credentials are authenticated.  Lets fire the onLogin event!
			$results = $dispatcher->trigger('onUserLogin', array($result, $options));

			// Validate that the login plugins were all happy.
			if (!in_array(false, $results, true))
			{
				JLog::add(sprintf('SSO system logged in user "%s".', $username), JLog::DEBUG, 'sso');
				return true;
			}

			// Fail the login if one of the login plugins failed.
			$dispatcher->trigger('onLoginFailure', array($result));
			JLog::add(sprintf('SSO system was unable to login user "%s".', $username), JLog::NOTICE, 'sso');
			return false;
		}
	}

	/**
	 * Retrieve the SSO data from an XML file.
	 *
	 * @param   string  $filename  The filename to process.
	 *
	 * @return  array  The data found in the XML file.
	 *
	 * @since   1.5
	 */
	public function getSsoXmlData($filename) {
		$xml =& JFactory::getXMLParser('Simple');
		if(!$xml->loadFile($filename)) {
			unset($xml);
			return false;
		}
		$sso =& $xml->document->getElementByPath('sso');
		$data = array();

		$element =& $sso->type[0];
		$data['type'] = $element ? $element->data() : 'A'; // type A plugins are the default

		$element =& $sso->key[0];
		$data['key'] = $element ? $element->data() : ''; // default to blank key


		$element =& $sso->valid_states[0];
		if($element)
		{
			$data['state_map'] = isset($element->state) ? self::_processStateMap($element) : array(); // default to blank array
			$data['default_state'] = $element->attributes('default');
		}
		else
		{
			$data['state_map'] = array();
			$data['default_state'] = 0;
		}

		$element =& $sso->operations[0];
		$data['operations'] = $element && isset($element->operation) ? self::_processOperations($element) : array(); // default to blank array
		return $data;
	}

	/**
	 * Retrieve a base URL to use for reutnring from an external authentication provider.
	 *
	 * @param   boolean  $prefer_component  If available, prefer to return via the component.
	 * @param   string   $plugin            A specific plugin to activate upon returning (only valid for the component).
	 *
	 * @return  string  The URL encoded path to return the user.
	 *
	 * @since   1.5
	 */
	public function getBaseUrl($prefer_component=true, $plugin='') {
		if($prefer_component && JComponentHelper::getComponent('com_ssomanager', true)) {
			// if we have a component, use it
			if(!empty($plugin))
			{
				return urlencode(JURI::base() . 'index.php?option=com_ssomanager&task=delegate&plugin='. $plugin);	
			}
			else
			{
				return urlencode(JURI::base() . 'index.php?option=com_ssomanager&task=delegate');	
			}
		}
		else
		{
			// hope that the plugin is active or a module
			return urlencode(JURI::base());
		}
	}

	/**
	 * Get a type of authentication provider or all providers.
	 *
	 * @param   string  $provider  A specific type of provider to return or null for all providers.
	 *
	 * @return  array  A list of authentication providers.
	 *
	 * @since   1.5
	 */
	public function &getProvider($provider = null) {
		$providers =& JAuthSSOAuthentication::_loadProviders();
		if($provider) {
			$results = array();
			$ip = count($providers);
			for($i = 0; $i < $ip; $i++) {
				if($providers[$i]->type == $provider) {
					$results[] = $providers[$i];
				}
			}
			return $results;
		} else {
			return $providers;
		}
	}
	
	/**
	 * Process the available operations from an XML file.
	 *
	 * @param   SimpleXMLElement  $element  The element to process.
	 *
	 * @return  array  A list of valid operations keyed by name and with a given label.
	 *
	 * @since   1.5
	 */
	protected function _processOperations($element) {
		$list = array();
		foreach($element->operation as $operation) {
			$list[$operation->attributes('name')] = $operation->attributes('label');
		}
		return $list;
	}

	/**
	 * Process the available states for a plugin.
	 *
	 * @param   SimpleXMLElement  $element  The element to process.
	 *
	 * @return  array  A mapping of available state transitions.
	 *
	 * @since   1.5
	 */
	protected function _processStateMap($element)
	{
		$map = array();
		foreach ($element->state as $state) 
		{
			$index = $state->attributes('value');
			$map[$index] = array();

			if (!isset($state->operation)) 
			{
				continue;
			}

			foreach ($state->operation as $operation)
			{
				$map[$index][] = $operation->attributes('name');
			}
		}
		return $map;
	}

	/**
	 * Load a list of authentication providers from the database.
	 *
	 * @return  array  A list of authentication providers (plugins).
	 *
	 * @since   1.5
	 */
	protected function &_loadProviders()
	{
		static $plugins;

		if (isset($plugins))
		{
			return $plugins;
		}

		$db = JFactory::getDBO();
		$query = $db->getQuery(1);
		$query->select('element type, sp.*')->from('#__sso_providers sp')
			->rightJoin('#__plugins p ON p.id = sp.plugin_id')
			->where('sp.published >= 1')->where('p.published >= 1')
			->order('ordering');

		$db->setQuery($query);

		$plugins = $db->loadObjectList();
		return $plugins;
	}
}
