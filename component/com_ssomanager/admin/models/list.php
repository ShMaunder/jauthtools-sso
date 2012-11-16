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
 * @package SSO_Manager
 * @author Sam Moffatt <pasamio@gmail.com>
 * @license GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright 2009 Sam Moffatt 
 * @version SVN: $Id:$
 * @see http://joomlacode.org/gf/project/   JoomlaCode Project:    
 */
 
jimport('joomla.application.component.models');

/**
 * SSO Manager Plugin model.
 * Used when managing a plugin.
 * @package     JAuthTools.SSO
 * @subpackage  com_ssomanager
 * @since       1.5
 */
class SSOManagerModelList extends JModel {
	/**
	 * @var    The model data.
	 * @since  1.5
	 */
	private $_data = null;

	/**
	 * Get the list of plugins.
	 *
	 * @return  array  The plugin list.
	 *
	 * @since   1.5
	 */
	public function getList() {
		if(!$this->_data) {
			$dbo =& JFactory::getDBO();
			$query  = 'SELECT p.name AS name, p.state AS state, p.state AS published, sp.type AS type, p.ordering AS ordering, p.extension_id AS id, p.params AS params ';
			$query .= ' FROM #__extensions AS p LEFT JOIN #__sso_plugins AS sp on sp.extension_id = p.extension_id';
			$mode = $this->getMode();
			if($mode) {
				$query .= ' WHERE folder = "'. $mode .'"';
			}
			$dbo->setQuery($query);
			$this->_data = array_map(array($this, 'buildEditLink'), $dbo->loadObjectList());
		}
		return $this->_data;
	}

	/**
	 * Get the mode that this model is operating within.
	 *
	 * @return  string  The mode (e.g. A, B, BG or C)
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
	 * Store the plugin details.
	 *
	 * @return  boolean  Result of operation.
	 *
	 * @since   1.5
	 */
    public function store() {
		$db   =& JFactory::getDBO();
		$row  =& JTable::getInstance('extension');
		$mode = $this->getMode();

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
		$row->reorder( 'folder = '.$db->Quote($row->folder).' AND ordering > -10000 AND ordering < 10000' );
		return true;
    }
    
    /**
     * Deleting a plugin from the interface.
     * TODO: If this isn't supported, need to work out why this is here.
     *
     * @return  void
     *
     * @since   1.5
     */
    public function delete() {
    	JError::raiseError(500, 'Plugins cannot be deleted through this interface');
    }
    
    /**
     * Build the edit link for this item
     *
     * @return  object  Modified object.
     *
     * @since   2.5.0
     */
    public function buildEditLink($item)
    {
	    $mode = $this->getMode();
		$item->editlink = 'index.php?option=com_ssomanager&task=plugin.edit&mode='. $mode . '&extension_id=' . $item->id . '&cid=' . $item->id;
	    return $item;
    }
}
