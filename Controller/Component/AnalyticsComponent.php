<?php

/**
 * Google Analytics Component
 *
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org
 * @package       debug_kit
 * @subpackage    debug_kit.controllers.components
 * @since         DebugKit 0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
 
#App::uses('FB', 'Facebook.Lib');
#App::uses('FacebookInfo', 'Facebook.Lib');
App::import('Vendor', 'Gapi.gapi',
  array(
    'file' => 'gapi.class.php'
  )
);
class AnalyticsComponent extends Component {

/**
	* Initialize, load the api, decide if we're logged in
	* Sync the connected Facebook user with your application
	* @param Controller object to attach to
	* @param settings for Connect
	* @return void
	* @access public
	*/
	public function initialize(&$Controller, $settings = array()){
  	Configure::load('gapi');
		$this->Controller = $Controller;
		$this->_set($settings);
		$user = Configure::read('Gapi.email');
		$pass = Configure::read('Gapi.password');
		$this->profile_id = Configure::read('Gapi.profileId');
		$this->Controller->gapi = new gapi($user, $pass);
	}
	
	/**
	 * Thanks to http://snipplr.com/view/45911/ for the start
	 */
	function getData($dimensions = array('pageTitle'),
	                 $metrics = array('pageviews','visits','UniquePageviews'),
	                 $sort = null,
	                 $filter = null,
	                 $start_date = null,
	                 $end_date = null){

    $profile_id = $this->profile_id;                
	            
    $cache_id = md5(serialize(func_get_args()));
    $return = false;
    
    // check if the cache item exists.
    $temp_folder = TMP.'cache/ga';
    
    if(!is_dir($temp_folder)) { mkdir($temp_folder); }

		if (!is_writable(dirname($temp_folder))) {
			trigger_error(__d('cake_dev', '"%s" directory is NOT writable.', dirname($temp_folder)), E_USER_NOTICE);
		}
		

    $filename = $temp_folder.$cache_id;
    
    if(is_file($filename)){ // if cache entry exists
      if(filemtime($filename) > (time() - 1440)){ // check if it's older than 4 hours
        $return = unserialize(file_get_contents($filename)); // grab the cached content.
      }
    }
      
    if(!$return) {
      // no cache item found, so we grab it via gapi class.
      if(!$sort) {
        $sort = current($dimensions);
      }
      
      ini_set('display_errors',true);
      
      $ga = $this->Controller->gapi;
      $ga->requestReportData($profile_id, $dimensions, $metrics, $sort,$filter,$start_date,$end_date,null,100);
      
      $return = array();
      $return['data'] = array();

      foreach($ga->getResults() as $result){
        $data = array();

          foreach($dimensions as $d){
            $data[$d] = $result->{'get'.$d}();
              foreach($metrics as $m){
                $data[$m] = $result->{'get'.$m}();
              }
          }
          $return['data'][] = $data;
      }
      
/*       $return['total'] = $ga->getTotalResults(); */
      
      foreach($metrics as $m){
        $return[$m] = $ga->{'get'.$m}();
      }
      
      $return['timestamp'] = $ga->getUpdated();
    }
      
    // save cache item.
    if (!file_put_contents($filename, serialize($return))) {
      trigger_error(__d('cake_dev', 'Failed to write "%s"', $temp_folder), E_USER_NOTICE);
    }
    return $return;
  }

}
