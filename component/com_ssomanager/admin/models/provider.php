<?php
/**
 * Document Description
 * 
 * Document Long Description 
 * 
 * PHP4/5
 *  
 * Created on Dec 8, 2008
 * 
 * @package package_name
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Sam Moffatt 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
 
jimport('joomla.application.component.model');

/**
 * SSO Manager Provider (Type B) Model
 * @package     JAuthTools.SSO
 * @subpackage  com_ssomanager
 * @since       1.5
 */
class SSOManagerModelProvider extends JModel {
	/**
	 * @var    array  List of providers.
	 * @since  1.5
	 */
	private $_data = null;
	
	/**
	 * Get a list of SSO providers.
	 *
	 * @return  array  List of providers.
	 *
	 * @since   1.5
	 */
	function getList() {
		if(!$this->_data) {
			$dbo =& JFactory::getDBO();
			$query  = 'SELECT p.name AS name, p.state AS state, sp.filename AS type, p.ordering AS ordering, p.id AS id, p.params AS params ';
			$query .= ' FROM #__sso_providers AS p LEFT JOIN #__sso_plugins AS sp ON p.extension_id = sp.extension_id';
			$dbo->setQuery($query);
			$this->_data = $dbo->loadObjectList();
		}
		return $this->_data;
	}

	/**
	 * Get the mode.
	 *
	 * @return  string  The mode of the request (e.g. A, B, BG or C)
	 *
	 * @since   1.5
	 */	
    public function getMode() {
		// TODO: Change the way that this behaves
    	static $mode = null;
    	if($mode === null) {
    		$mode = JRequest::getVar('mode','');
    	}
    	return $mode;
    }	
   
	/**
	 * Store the SSO provider instance.
	 *
	 * @return  boolean  The result of the store operation.
	 *
	 * @since   1.5
	 */ 
	public function store() {
		$row =& JTable::getInstance('ssoprovider');
		if (!$row->bind(JRequest::get('post'))) {
			JError::raiseError(500, $row->getError() );
		}
		if (!$row->check()) {
			JError::raiseError(500, $row->getError() );
		}
		if (!$row->store()) {
			JError::raiseError(500, $row->getError() );
		}
		return true;
    }

    /**
     * Delete this SSO provider instance.
     *
     * @return  boolean  The result of the delete operation.
     *
     * @since   1.5
     */
    public function delete($cid) {
    	if(!is_array($cid)) {
    		$cid = Array($cid);
    	}
    	$dbo =& JFactory::getDBO();
    	$query = 'DELETE FROM #__sso_providers WHERE id IN ('. implode(',', $cid) .')';
    	$dbo->setQuery($query);
    	return $dbo->Query();
     }
}
