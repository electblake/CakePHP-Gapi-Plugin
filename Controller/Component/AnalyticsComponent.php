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
App::import('Vendor', 'Analytics.Gapi',
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

		//  configure a cache
		Cache::config('analytics', array(
		    'engine'   => 'File',
		    'duration' => '+4 hours',
		    'path'     => CACHE . 'ga' . DS,
		    'prefix'   => ''
		));
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
	    $cache_id = md5($this->profile_id . serialize(func_get_args()));

		$return = Cache::read($cache_id, 'analytics');

		if(!$return) {
			// no cache item found, so we grab it via gapi class.
			if(!$sort) {
				$sort = current($dimensions);
			}

			ini_set('display_errors',true);

			$ga = $this->Controller->gapi;
			$ga->requestReportData($this->profile_id, $dimensions, $metrics, $sort,$filter,$start_date,$end_date,null,100);

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

			foreach($metrics as $m){
				$return[$m] = $ga->{'get'.$m}();
			}

			$return['timestamp'] = $ga->getUpdated();
			Cache::write($cache_id, $return, 'analytics');
		}

		return $return;
	}
}
