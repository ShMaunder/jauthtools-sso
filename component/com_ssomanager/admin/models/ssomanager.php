<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Dec 4, 2008
 * 
 * @package package_name
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Sam Moffatt 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport( 'joomla.application.component.model' );
jimport( 'joomla.filesystem.file');
jimport( 'joomla.filesystem.folder');
jimport( 'jauthtools.sso' );

/**
 * Core SSO Manager model
 * @package     JAuthTools.SSO
 * @subpackage  com_ssomanager
 * @since       1.5
 */
class SSOManagerModelSSOManager extends JModel {
	/**
	 * @var    string  Mode for the SSO Manager plugin type (e.g. A, B or C)
	 * @since  1.5
	 */
	private $_mode = 'A';

	/**
	 * @var    string  Data
	 * @since  1.5
	 */	
	private $_data; 
	
	/**
	 * Get the list of plugins
	 *
	 * @return  array  List of plugins
	 *
	 * @since   1.5
	 */
	public function getList() {
		$dbo =& JFactory::getDBO();
		
		$query  = 'SELECT p.name AS name, p.state AS state, sp.filename AS type, p.ordering AS ordering, p.extension_id AS id ';
		switch($this->_mode) {
			case 'A':
			case 'C':
			case 'BG':
				$query .= ' FROM #__sso_plugins AS sp LEFT JOIN #__extensions AS p on sp.extension_id = p.extension_id';
				break;
			case 'B':
				$query .= ' FROM #__sso_providers AS p LEFT JOIN #__sso_plugins AS sp ON p.extension_id = sp.extension_id';
				break;
		}
		
		if($this->_mode) {
			if($this->_mode == 'BG') {
				// BG is a special type of 'B' for the global list
				$query .= ' WHERE sp.type = "B"';				
			} else {
				$query .= ' WHERE sp.type = "'. $this->_mode .'"';
			}
		}
		
		
		$dbo->setQuery($query);
		
		$res = $dbo->loadObjectList();
		return $res;
	}
	
	/** 
	 * Set the mode for this model
	 *
	 * @param   string  $mode  The mode to set (e.g. A, B, BG or C)
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function setMode($mode) {
		$this->_mode = $mode;
	}

	/**
	 * Get the mode for this model
	 *
	 * @return  string  The current mode (e.g. A, B, BG or C)
	 *
	 * @since   1.5
	 */ 
	public function getMode() {
		return $this->_mode;
	}

	/**
	 * Refresh the list of cached plugins
	 *
	 * @return  array  Number of successful and failed plugins that were refreshed.
	 *
	 * @since   1.5
	 */
	function refreshPlugins() {
		$dbo =& JFactory::getDBO();	
		$query = 'INSERT INTO #__sso_plugins (extension_id,filename) SELECT `extension_id`,`element` FROM #__extensions WHERE `extension_id` NOT IN (SELECT `extension_id` FROM #__sso_plugins) AND `folder` = "sso"';
		$dbo->setQuery($query);
		$results = $dbo->Query();
		$query = 'DELETE FROM #__sso_plugins WHERE extension_id NOT IN (SELECT extension_id FROM #__extensions WHERE folder = "sso")';
		$dbo->setQuery($query);
		$dbo->Query();
		$query = 'SELECT extension_id FROM #__sso_plugins';
		$dbo->setQuery($query);
		$results = $dbo->loadResultArray();
		$result = Array();
		$retval['success'] = 0;
		$retval['failure'] = 0;
		foreach($results as $result) {
			$table =& JTable::getInstance('ssoplugin');
			$table->load($result);
			$table->refresh();
			if($table->store()) {
				++$retval['success'];
			} else {
				++$retval['failure'];
			}
		}
		return $retval;
	}

	/**
	 * Get the data from this model.
	 *
	 * @return  string  Model data.
	 *
	 * @since   1.5
	 */	
	public function getData() {
		return $this->_data;
	}

	/**
	 * Load the data for this model.
	 *
	 * @param   integer  $index  The plugin identifier.
	 *
	 * @return  string  Model data.
	 *
	 * @since   1.5
	 */
	public function loadData($index) {
		if($index) {
			$dbo =& JFactory::getDBO();
			$query  = 'SELECT p.name AS name, p.state AS state, sp.filename AS type, p.ordering AS ordering, p.id AS id, p.params AS params ';
			switch($this->_mode) {
				case 'A':
				case 'C':
				case 'BG':
					$query .= ' FROM #__sso_plugins AS sp LEFT JOIN #__extensions AS p on sp.extension_id = p.id';
					break;
				case 'B':
					$query .= ' FROM #__sso_providers AS p LEFT JOIN #__sso_plugins AS sp ON p.extension_id = sp.extension_id';
					break;
			}
			$query .= ' WHERE sp.type = "'. $this->_mode .'" AND p.id = '. $index;
			$dbo->setQuery($query);
			$this->_data = $dbo->loadObject();
		} else {
			// Fake this for new object
			$this->_data = new stdClass();
			$type = JRequest::getVar('type','');
			$this->_data->name = 'New '. $type .' plugin';
			$this->_data->state = 0;
			$this->_data->type = $type;
			$this->_data->ordering = 999;
			$this->_data->id = 0;
			$this->_data->params = '';
		}
		return $this->_data;
	}
	
	/**
	 * Store the data in the database
	 *
	 * @return  boolean  Result of the store operation.
	 *
	 * @since   1.5
	 */
	public function store() {
		// The mode should have been set by the controller, so all we need to do 
		// is pull the data out of the request
		switch($this->_mode) {
			case 'A':
			case 'C':
			case 'BG':
				// type A or C plugins use the #__extensions table to store data
				// type BG is the type B global params
				// TODO: type B plugin non-instance params are global, so need to set this appropriately
				$row =& JTable::getInstance('extension');
				$id = JRequest::getVar('cid',0);
				$row->load($id);
				
				// Check for request forgeries
				JRequest::checkToken() or jexit( 'Invalid Token' );
		
				$db   =& JFactory::getDBO();
				$row  =& JTable::getInstance('extension');
		
				$client = JRequest::getWord( 'filter_client', 'site' );
		
				if (!$row->bind(JRequest::get('post'))) {
					JError::raiseError(500, $row->getError() );
				}
				if (!$row->check()) {
					JError::raiseError(500, $row->getError() );
				}
				if (!$row->store()) {
					JError::raiseError(500, $row->getError() );
				}
				$row->checkin();

				if ($client == 'admin') {
					$where = "client_id=1";
				} else {
					$where = "client_id=0";
				}

				$row->reorder( 'folder = '.$db->Quote($row->folder).' AND ordering > -10000 AND ordering < 10000 AND ( '.$where.' )' );
				return true;
				break;
			case 'B':
				// type B plugins are instance plugins
				
				break;
		}
	}
}
