<?php
require('Client.php');
require('GrantType/IGrantType.php');
require('GrantType/AuthorizationCode.php');	

class StandardAuthentication
{	
	protected $option_name = 'wo_options';
		
	//Initialize
	public function _init()
	{
		$this->_login();
		$this->_post_slugs();
		$this->_run_callbacks();
		add_filter('wo_endpoints', array($this, '_update_endpoint'), 2);
	}
	
	/**
	 * _update_endpoint function.
	 * 
	 * Create sample endpoint to update user display name from iOS app
	 *
	 * @access public
	 * @param mixed $methods
	 * @return void
	 */
	public function _update_endpoint($methods)
	{		
		$methods['update-me'] = array(
			'func'=> array($this, 'run_update_method'), // Function name to run
		    'public' => false // True to be public
		);
		
		return $methods;
	}
	
	/**
	 * run_update_method function.
	 * 
	 * method used to update and retrieve display name
	 *
	 * @access public
	 * @param mixed $token (default: null)
	 * @return void
	 */
	public function run_update_method($token = null)
	{
		$response = new OAuth2\Response();
		
		if (!isset($token['user_id']) || $token['user_id'] == 0) {
			
			$response->setError(400, 'invalid_request', 'Missing or invalid access token');
			$response->send();
			exit;
		}
		
		$user_id = &$token['user_id'];
		
		if( !current_user_can('edit_user', $user_id) ){
			$response->setError(400, 'invalid_request', 'You are not allowed to edit this user');
			$response->send();
			exit;
		}

		$user_id = wp_update_user( 
			array( 
				'ID' => $user_id, 
				'display_name' => sanitize_text_field($_POST['name'])
			) 
		);
		
		if ( is_wp_error( $user_id ) ) {
			// There was an error, probably that user doesn't exist.
			$response->setError(400, 'invalid_request', 'There was an error updating me');
			$response->send();
			exit;
			
		} else {
			$return = array('success'=>'updated-me');
			$response = new OAuth2\Response($return);
			$response->send();
			exit();
		}
	}
	
	/**
	 * _get_tokens function.
	 * 
	 * Get tokens for standard callback
	 * 
	 * @access private
	 * @param mixed $client
	 * @return void
	 */
	private function _get_tokens($client){
		if(empty($client)){ return; }
	
		$response = wp_remote_post( home_url('oauth/token'), 
		array(
			'timeout' => 45,
			'body' => array( 
				'code' => sanitize_text_field($_GET['code']), 
				'redirect_uri' => $client['redirect_uri'],
				'grant_type' => 'authorization_code',
				'client_id' => $client['client_id'],
				'client_secret' => $client['client_secret']
				),
		    )
		);
				
		if ( is_wp_error( $response ) ) {
			return json_encode(array('error' => 'get_tokens'));
		} else {
			$body = wp_remote_retrieve_body($response);
			return $body;
		}
	}
	
	/**
	 * _run_callbacks function.
	 * 
	 * run standard callbacks or place custom callbacks in an action
	 *
	 * @access public
	 * @return void
	 */
	public function _run_callbacks(){
		
		if(!empty($_GET['_wpoauth_callback']))
		{					
			$headers = getallheaders();
			$cookie = explode('=',$headers['Cookie']);
			
			$callback = sanitize_text_field( $_GET['_wpoauth_callback'] );
			$clients = $this->_get_clients();
			$slug = get_transient( 'wpoauth_slug_'.$cookie[0] );
			
			$action = array_filter($clients, function($v) use($slug){
				return($v['slug'] == $slug);
			});
			
			delete_transient( 'wpoauth_slug_'.$cookie[0] );
						
			if(empty($action)){ return; }
			
			$response = $this->_get_tokens($action[0]);
			
			if($callback == 'standard'){
				ob_start();
				echo $response;
				header('Content-type: application/json');
				ob_end_flush();
				exit();
			}else{		
				do_action('wpoauth_finish', array(
					'callback' => $callback, 
					'response' => $response
				));
			}
		}
	}
	
	/**
	 * _login function.
	 * 
	 * Login method
	 * @access public
	 * @return void
	 */
	public function _login()
	{			
		if( isset($_POST['wpoauth_login']) ){ //!Encrypt Password
			$this->_user_data($_POST);
			exit();
		}
	}
	
	/**
	 * _user_data function.
	 * 
	 * Login user and and get user data
	 *
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	public function _user_data($data)
	{	
		$data = array_map('sanitize_text_field', $data);
		$message = array();
		
		//wp_signon sanitizes login input
		$user = wp_signon( array(
			'user_login' => $data['user_login'],
			'user_password' => $data['user_password'],
			'remember' => false
		), true );
		
		if ( is_wp_error($user) )
		{
			//Return error messages
			if(isset($user->errors['invalid_username'])){
				$message['error'] = "Invalid User Name";
			} elseif(isset($user->errors['incorrect_password'])){
				$message['error'] = "Incorrect Password";
			}
					

			ob_start();
			echo json_encode($message);
			header('Content-type: application/json');
			ob_end_flush();

			exit(); 
		}
		else
		{
			/*
			 * Don't return anymore information than needed. 
			 * In this case we only need the user ID
			 * But add more as needed
			 */
			 
			$id = array(
				'ID' => $user->ID
			); 

			ob_start();
			echo json_encode($id);
			header('Content-type: application/json');
			ob_end_flush();
			exit();
		}
	}
	
	/**
	 * _get_clients function.
	 * 
	 * Get oauth clients
	 *
	 * @access private
	 * @return void
	 */
	private function _get_clients(){
		global $wpdb;

		$clients = $wpdb->get_results("
			SELECT * 
			FROM {$wpdb->prefix}oauth_clients"
		, ARRAY_A);
		
		return $clients;
	}
	
	/**
	 * _refresh function.
	 * 
	 * Get Refresh token
	 *
	 * @access public
	 * @param mixed $client
	 * @return void
	 */
	public function _refresh($client){
		
		$result = array();
		
		$refresh = wp_remote_post( home_url('oauth/token'),
	  	array(
	  		'body' => array( 
	  			'grant_type' => 'refresh_token', 
	  			'refresh_token' => sanitize_text_field($_POST['refresh_token']),
	  			'client_id' => $client['client_id'],
	  			'client_secret' => $client['client_secret'],
	  		),
	  	));
	  	
		if ( is_wp_error( $refresh ) ) {
			$result = json_encode($refresh->get_error_message());
		} else {
	
			if( !empty($refresh['body']) ){
				$result = $refresh['body'];
			}
		}
				
		return $result;
	}
	
	/**
	 * _oauth_types function.
	 * 
	 * Handle oauth type requests
	 *
	 * @access public
	 * @param mixed $slug
	 * @param mixed $request
	 * @param mixed $client
	 * @return void
	 */
	public function _oauth_types($slug,$request,$client){
		if(empty($slug) || empty($request) || empty($client)){ return; }
		
		$response_type = sanitize_text_field($request['response_type']);
				
		switch($request[$slug])
		{
			case 2:
				$go = $this->_refresh($client);
				ob_start();
				echo $go;
				header('Content-type: application/json');
				ob_end_flush();
				exit();			
			break;
			
			case 1:

				if( !is_user_logged_in() ){ return; }
				
				$headers = getallheaders();
				$cookie = explode('=',$headers['Cookie']);
				set_transient( 'wpoauth_slug_'.$cookie[0], $client['slug'], HOUR_IN_SECONDS );
			
				$url = home_url('oauth/authorize?response_type='.$response_type.'&client_id='.$client['client_id'].'&redirect_uri='.urlencode($client['redirect_uri']));
				
				header('Location: ' .$url);
				die('Redirect');
			
			break;
		}
	}
	
	/**
	 * _post_slugs function.
	 * 
	 * Filter through clients
	 *
	 * @access public
	 * @return void
	 */
	public function _post_slugs(){
		$options = get_option($this->option_name);
		$clients = $this->_get_clients();
		
		if(!empty($clients)){
						
			foreach($clients as $k => $client)
			{
				if(!$client['use_auth']){return;}
				
				if(!empty($_POST[$client['slug']])){
					$this->_oauth_types($client['slug'],$_POST,$client);
				}
			}
		}
	}
}

add_action('after_setup_theme', array($standard_auth = new StandardAuthentication,'_init') );

add_action('wpoauth_finish', function($data){
	
	exit();
});