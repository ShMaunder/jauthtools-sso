<?php
/**
 * SSO Manager Component Main Controller
 * 
 * PHP5
 *  
 * Created on Sep 28, 2007
 * 
 * @package    JAuthTools.SSO
 * @subpackage com_ssomanager 
 * @author     Sam Moffatt <pasamio@gmail.com>
 * @license    GNU/GPL http://www.gnu.org/licenses/gpl.html
 * @copyright  2012 (C) Sam Moffatt 
 */
 
 // no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 * SSO Manager Component Controller
 * @package     JAuthTools.SSO
 * @subpackage  com_ssomanager
 * @since       1.5
 */
class SSOManagerController extends JController
{
	/**
	 * Constructor!
	 *
	 * @since  1.5
	 */
	function __construct() {
		parent::__construct();
		$this->registerTask('new', 'newprovider');
		$this->registerTask( 'unpublish', 'publish');
	}
	
    /**
     * Method to display the view
     *
     * @return  void
     *
     * @since   1.5
     */
    public function display()
    {
    	switch($this->getTask()) {
    		case 'cancel':
    			$this->entries();
    			break;
    		default:
    			JToolbarHelper::title('SSO Manager');
	    		$this->configuration(); // default to configuration manager
    			break;
    	}
    }
   
    /**
     * Get the entries in a list view.
     *
     * @return  void
     *
     * @since   1.5
     */
    public function entries() {
    	$mode = $this->getMode();
    	$this->setToolbarFromMode($mode);
    	$model =& $this->getModelFromMode($mode);
    	JToolbarHelper::title(JText::_('SSO Manager') . ' - '. JText::_($this->getNameFromMode($mode)));
    	$view = $this->getView('list','html');
    	$view->setModel($model, true);
    	$view->display();
    }
    
    /**
     * Refresh the configuration and reload the page.
     *
     * @return  void
     *
     * @since   1.5
     */
    public function refresh() {
    	$model =& $this->getModel();
    	$mode = $this->getMode();
    	$count = $model->refreshPlugins();
    	$this->setRedirect('index.php?option=com_ssomanager&task=configuration',JText::sprintf('Refreshed %d plugins successfully and failed to update %d plugins', $count['success'], $count['failure']));
    }
    
    /**
     * Display a list view
     *
     * @param  string  $mode  The mode list to display (A, B, BG, C)
     *
     * @return void
     *
     * @since  1.5
     */
    public function listView($mode='A') {
    	JRequest::setVar('task', 'type'.$mode);
    	JToolbarHelper::title(JText::sprintf('SSO - Type %s plugins', ucfirst($mode)));
    	if($mode == 'B') {
    		JToolbarHelper::addNew('new');
    		JToolbarHelper::editList('edit','Edit');
    		JToolbarHelper::deleteList('delete');
    	} else {
    		JToolbarHelper::editList('edit','Edit');
    	}
    	
    	$model =& $this->getModel();
    	$model->setMode($mode);
    	$view =& $this->getView('list','html');
    	$view->setModel( $model, true);
    	$view->display();
    }
    
    /**
     * Edit view
     *
     * @return  void
     *
     * @since   1.5
     */
    public function edit() {
    	JToolBarHelper::title( JText::_( 'SSO' ) .': <small><small>[' .JText::_('Edit'). ']</small></small>', 'plugin.png' );
		JToolBarHelper::save();
		JToolBarHelper::cancel( 'cancel', 'Close' );
    	JRequest::setVar('hidemainmenu',1);
    	$mode = $this->getMode();
    	$model =& $this->getModelFromMode($mode);
    	$view =& $this->getViewFromMode($mode);
    	$view->setModel( $model, true);
    	$view->setLayout('form');
    	$view->display();
    }
   
    /**
     * Save the data and redirect back to the original view.
     *
     * @return  void
     *
     * @since   1.5
     */ 
    public function save() {
    	// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );
    	$mode = $this->getMode();
    	$model =& $this->getModelFromMode($mode);
    	if(!$model) {
    		$this->setRedirect('index.php?option=com_ssomanager', 'Save failed: Could not find  valid model','error');
    		return false;
    	}
    	
    	
    	if($model->store()) {
    		$msg = 'Saved';
    	} else {
    		$msg = 'Store failed';
    	}
    	if($mode == 'config') {
    		$link = 'index.php?option=com_ssomanager';
    	} else {
    		$link = 'index.php?option=com_ssomanager&task=entries&mode='. $mode;
    	}
    	$this->setRedirect($link, $msg);
    }
   
    /**
     * Get the model for this request from the mode.
     *
     * @param   string  $mode  The mode to use (e.g. sso, user, usersource, etc).
     *
     * @return  JModel  An instance of the model to use for this request.
     *
     * @since   1.5
     */ 
    protected function &getModelFromMode($mode) {
        switch($mode) {
    		case 'sso':
    		case 'identityprovider':
			case 'user':
    		case 'usersource':
    		case 'authentication':   
    		case 'config': // config is a special instance of the System - SSO plugin 			
    			$model =& $this->getModel('list');
    			break;
    		case 'serviceprovider':
    			$model =& $this->getModel('provider');
    			break;
    		default:
    			$model = false;
    			break;    			
    	}
    	return $model;
    }
    
    /**
     * Get the view for this request from the mode.
     *
     * @param   string  $mode  The mode to use (e.g. sso, user, usersource, etc).
     *
     * @return  JView  An instance of the view to use for this request.
     *
     * @since   1.5
     */
    protected function &getViewFromMode($mode) {
    	switch($mode) {
    		case 'sso':
    		case 'identityprovider':
			case 'user':
    		case 'usersource':
    		case 'authentication':    			
    			$view =& $this->getView('plugin', 'html');
    			break;
    		case 'serviceprovider':
    			$view =& $this->getView('provider','html');
    			break;
    		default:
    			$view = false;
    	}
    	return $view;
    }
    
    /** 
     * Method to map textual name to descriptive name for display.
     *
     * @param   string  $mode  The mode to use (e.g. sso, user, user source, etc).
     *
     * @return  string  The friendly name for the mode type.
     *
     * @since   1.5
     */
    protected function getNameFromMode($mode) {
    	switch($mode) {
    		case 'identityprovider':
    			return 'Identity Providers';
    			break;
    		case 'serviceprovider':
    			return 'Service Providers';
    			break;
    		case 'sso':
    			return 'SSO Plugins';
    			break;
    		case 'configuration':
    			return 'Configuration';
    		case 'authentication':
    			return 'Authentication Plugins';
    			break;
    		case 'user':
    			return 'User Plugins';
    			break;
    		case 'usersource':
    			return 'User Sources Plugins';
    			break;
    		default:
    			return 'Unknown';
    			break;
    	}	
    }
   
    /**
     * Setup the Joomla! toolbar based on the given mode.
     *
     * @param   string  $mode  The mode to configure based upon (e.g. sso, user, user source, etc).
     *
     * @return  void
     *
     * @since   1.5
     */
    protected function setToolbarFromMode($mode) {
    	switch($mode) {
    		case 'serviceprovider':
    			JToolbarHelper::addNew('new');
    			JToolbarHelper::editList('edit','Edit');
    			JToolbarHelper::deleteList('delete');
    			break;
    		case 'sso':
    		case 'identityprovider':
    		case 'user':
    		case 'usersource':
    		case 'authentication':
    			JToolbarHelper::editList('edit','Edit');
    			break;
    	}
    }
    
    /**
     * Remove an item and redirect back to the entry page.
     *
     * @return  boolean  The result of the operation.
     *
     * @since   1.5
     */
    public function remove() {
    	$mode = JRequest::getVar('mode','');
    	$model =& $this->getModelFromMode($mode);
    	if(!$model) {
    		$this->setRedirect('index.php?option=com_ssomanager', 'Failed to find model', 'error');
    	} else {
    		$cid 	= JRequest::getVar( 'cid', array(0), '', 'array' );
    		if($model->delete($cid)) {
    			$this->setRedirect('index.php?option=com_ssomanager&task=entries&mode='. $mode, 'Delete successful!');
    		} else {
    			$this->setRedirect('index.php?option=com_ssomanager&task=entries&mode='. $mode, 'Delete failed', 'error');
    		}
    	}
    	return true;
    }
    
    /**
     * Get the mode from the request.
     *
     * @return  string  The mode.
     *
     * @since   1.5
     */
    public function getMode() {
    	static $mode = null;
    	if($mode === null) {
    		$mode = JRequest::getVar('mode','');
    		if($mode == 'sources' || $mode == 'source') { // replace this as j! gets confused
    			$mode = 'usersource';
    		}
    	}
    	return $mode;
    }
    
    /** 
     * Display the configuration screen.
     *
     * @return  void
     *
     * @since   1.5
     */
    public function configuration() {
    	JHtml::stylesheet('toolbar.css', 'administrator/components/com_ssomanager/media/css/');
    	JToolbarHelper::title(JText::_('SSO Manager'). ' - '.  JText::_('Configuration'));
    	JToolBarHelper::custom( 'refresh', 'refresh', 'refresh','Refresh Plugin List',false,false);
		parent::display();
    	/*
		JToolBarHelper::save();
		JToolBarHelper::cancel( 'cancel', 'Close' );
		$dbo =& JFactory::getDBO();
		$dbo->setQuery('SELECT extension_id id FROM #__extensions WHERE folder = "system" AND element = "sso"');
		$result = $dbo->loadResult();
		if($result) {
	    	JRequest::setVar('cid',$result);
	    	JRequest::setVar('mode','config');
	    	$model =& $this->getModel('plugin');
	    	$view =& $this->getView('plugin','html');
	    	$view->setModel( $model, true);
	    	$view->setLayout('edit');
	    	$view->display();	
		} else {
			parent::display();
		}
		*/
    }
    
    /**
     * Publish an item from a model.
     *
     * @return  void
     *
     * @since   1.5
     */
	public function publish( )
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );
		$mode = $this->getMode();
		$model =& $this->getModelFromMode($mode);
		
		
		
		$cid     = JRequest::getVar( 'cid', array(0), 'post', 'array' );
		JArrayHelper::toInteger($cid, array(0));
		$publish = ( $this->getTask() == 'publish' ? 1 : 0 );
		

		if (count( $cid ) < 1) {
			$action = $publish ? JText::_( 'publish' ) : JText::_( 'unpublish' );
			JError::raiseError(500, JText::_( 'Select a plugin to '.$action ) );
		}
		
		$db		=& JFactory::getDBO();
		$user	=& JFactory::getUser();
		
		$cids = implode( ',', $cid );

		$table = ''; $key = '';
		switch($mode) {
			case 'serviceprovider':
				$table = '#__sso_providers';
				break;
			default:
				$table = '#__extensions';
				break;
		}
		$query = 'UPDATE '. $table .' SET state = '.(int) $publish
			. ' WHERE extension_id IN ( '.$cids.' )'
			;
		$db->setQuery( $query );
		if (!$db->query()) {
			JError::raiseError(500, $db->getErrorMsg() );
		}

		$this->setRedirect( 'index.php?option=com_ssomanager&task=entries&mode='. $mode );
	}
    
    /** 
     * Create a new SSO provider.
     *
     * @return  void
     *
     * @since   1.5
     */
    public function newprovider() {
    	$view =& $this->getView('selecttype', 'html');
    	JRequest::setVar('mode', 'sso');
    	$model =& $this->getModel('plugin');
    	$view->setModel($model, true);
    	$view->display();
    }
}
