<?php
/**
 * WPOauth_Admin Class
 * Add admin functionkaity to the backend of WordPress
 */
class WPOAuth_Admin {

	/**
	 * WO Options Name
	 * @var string
	 */
	protected $option_name = 'wo_options';

	/**
	 * [_init description]
	 * @return [type] [description]
	 */
	public static function init() {
		add_action('admin_init', array(new self, 'admin_init'));
		add_action('admin_menu', array(new self, 'add_page'), 1);
	}

	/**
	 * [admin_init description]
	 * @return [type] [description]
	 */
	public function admin_init() {
		register_setting('wo_options', $this->option_name, array($this, 'validate'));

		require_once(dirname(__FILE__). '/admin/page-server-status.php');
	}

	/**
	 * [add_page description]
	 */
	public function add_page() {
		add_menu_page('OAuth2 Server', 'OAuth2 Server', 'manage_options', 'wo_settings',array($this, 'oauth_options_page'), 'dashicons-groups');

		add_submenu_page( 'wo_settings', 'OAuth2 Server', 'OAuth2 Server', 'manage_options', 'wo_settings', array($this, 'oauth_options_page' )); 

		add_submenu_page( 'wo_settings', 'Server Status', 'Server Status', 'manage_options', 'wo_server_status', 'wo_server_status_page'); 
	}

	/**
	 * loads the plugin styles and scripts into scope
	 * @return [type] [description]
	 */
	public function admin_head() {
		wp_enqueue_style('wo_admin');
		wp_enqueue_script('wo_admin');
		wp_enqueue_script('jquery-ui-tabs');
	}

	/**
	 * [options_do_page description]
	 * @return [type] [description]
	 */
	public function oauth_options_page(){
		$options = get_option($this->option_name);
		$this->admin_head();		
		error_reporting(0);
		add_thickbox();
		
		?>
		<div class="wrap">
	      	<h2>WP OAuth2 Server</h2>
	     	<br/> 
	      	<form method="post" action="options.php">
				<?php settings_fields('wo_options');?>
	        		<div id="wo_tabs">
		        		
					<ul>
						<li><a href="#general-settings">General Settings</a></li>
						<li><a href="#advanced-configuration">Advanced Configuration</a></li>
						<li><a href="#clients">Clients</a></li>
					</ul>

					<!-- GENERAL SETTINGS -->
					<div id="general-settings">
				  		<table class="form-table">
					  		
							<tr valign="top">
								<th scope="row">API Enabled:</th>
								<td>
								<input type="checkbox" name="<?php echo $this->option_name?>[enabled]" value="1" <?php echo $options["enabled"] == "1" ? "checked='checked'" : "";?> />
								<p class="description">If the API is not enabled, it will present requests with an "Unavailable" message.</p>
								</td>
							</tr>
							
				  		</table>
					</div>
					
					<div id="advanced-configuration">
						<h2>Advanced Configuration</h2>
						<h3>Grant Types <hr></h3>
			            <p>Control which Grant Types that the server will accept.</p>
			            
			            <table class="form-table">
							<tr valign="top">
								<th scope="row">Authorization Code:</th>
								<td>
									<input type="checkbox" name="<?php echo $this->option_name?>[auth_code_enabled]" value="1" <?php echo $options["auth_code_enabled"] == "1" ? "checked='checked'" : "";?> />
									<p class="description">HTTP redirects and WP login form when authenticating.</p>
								</td>
							</tr>
							
							<tr valign="top">
								<th scope="row">Client Credentials:</th>
								<td>
									<input type="checkbox" name="<?php echo $this->option_name?>[client_creds_enabled]" value="1" <?php echo $options["client_creds_enabled"] == "1" ? "checked='checked'" : "";?> />
									<p class="description">Enable "Client Credentials" Grant Type</p>
								</td>
							</tr>
							
							<tr valign="top">
								<th scope="row">User Credentials:</th>
								<td>
									<input type="checkbox" name="<?php echo $this->option_name?>[user_creds_enabled]" value="1" <?php echo $options["user_creds_enabled"] == "1" ? "checked='checked'" : "";?> />
									<p class="description">Enable "User Credentials" Grant Type</p>
								</td>
							</tr>
							
							<tr valign="top">
								<th scope="row">Refresh Tokens:</th>
								<td>
									<input type="checkbox" name="<?php echo $this->option_name?>[refresh_tokens_enabled]" value="1" <?php echo $options["refresh_tokens_enabled"] == "1" ? "checked='checked'" : "";?> />
									<p class="description">Enable "Refresh Token" Grant Type</p>
								</td>
							</tr>
							
							<tr valign="top">
								<th scope="row">Allow Implicit:</th>
								<td>
									<input type="checkbox" name="<?php echo $this->option_name?>[implicit_enabled]" value="1" <?php echo $options["implicit_enabled"] == "1" ? "checked='checked'" : "";?> />
									<p class="description">Enable "Authorization Code (Implicit)" <a href="http://wp-oauth.com/documentation/server-api/which-grant-type-to-use/">What is this?</a></p>
								</td>
							</tr>
			            </table>
			            
			            <h3>Misc Settings <hr></h3>
			            
						<table class="form-table">
							
							<tr valign="top">
								<th scope="row">Key Length</th>
								<td>
									<input type="number" name="<?php echo $this->option_name?>[client_id_length]" min="10" value="<?php echo $options["client_id_length"];?>" />
									<p class="description">Length of Client ID and Client Secrets when generated.</p>
								</td>
							</tr>
							
							<tr valign="top">
								<th scope="row">Require Exact Redirect URI:</th>
								<td>
									<input type="checkbox" name="<?php echo $this->option_name?>[require_exact_redirect_uri]" value="1" <?php echo $options["require_exact_redirect_uri"] == "1" ? "checked='checked'" : "";?> />
									<p class="description">Enable if exact redirect URI is required when authenticating.</p>
								</td>
							</tr>
						
							<tr valign="top">
								<th scope="row">Enforce State Parameter:</th>
								<td>
									<input type="checkbox" name="<?php echo $this->option_name?>[enforce_state]" value="1" <?php echo $options["enforce_state"] == "1" ? "checked='checked'" : "";?>/>
									<p class="description">Enable if the "state" parameter is required when authenticating. </p>
								</td>
							</tr>
							
						</table>
						
						<!-- OpenID Connect -->
						<h3>OpenID Connect 1.0a <hr></h3>
							<p>
						The OpenID Connect 1.0a works with other systems like Drupal and Moodle.
						</p>
						
						<table class="form-table">
							
							<tr valign="top">
								<th scope="row">Enable OpenID Connect:</th>
								<td>
								<input type="checkbox" name="<?php echo $this->option_name?>[use_openid_connect]" value="1" <?php echo $options["use_openid_connect"] == "1" ? "checked='checked'" : "";?>/>
								<p class="description">Enable if your server should generate a id_token when OpenID request is made.</p>
								</td>
							</tr>
							
							<tr valign="top">
								<th scope="row">ID Token Lifetime</th>
								<td>
								<input type="number" name="<?php echo $this->option_name?>[id_token_lifetime]" value="<?php echo $options["id_token_lifetime"];?>" placeholder="3600" />
								<p class="description">How long an id_token is valid (in seconds).</p>
								</td>
							</tr>
						
						</table>
			            
						<h3>Token Lifetimes <hr></h3>
							<p>
						Take control of your token lifetimes easily. By default Access Tokens are valid for 1 hour
						and Refresh Tokens are valid for 24 hours.
						</p>
						
						<table class="form-table">
							<tr valign="top">
								<th scope="row">Access Token Lifetime</th>
								<td>
									<input type="number" name="<?php echo $this->option_name?>[access_token_lifetime]" value="<?php echo $options["access_token_lifetime"];?>" placeholder="3600" />
									<p class="description">How long an access token is valid (seconds) - Leave blank for default (1 hour)</p>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">Refresh Token Lifetime</th>
								<td>
								<input type="number" name="<?php echo $this->option_name?>[refresh_token_lifetime]" value="<?php echo $options["refresh_token_lifetime"];?>" placeholder="86400"/>
								<p class="description">How long a refresh token is valid (seconds)- Leave blank for default (24 hours)</p>
								</td>
							</tr>
						</table>
							
						<h3>Firewall<hr></h3>
							<p>
							The firewall is used to secure your OAuth API by allowing you to block all IP's and only allow
							approved IP's through. The firewall supports whitelisting of IPV4 and IPv6 addresses.
							</p>
							
						<table class="form-table">
							<tr valign="top">
								<th scope="row">Block All Incoming Requests but Whitelisted: </th>
								<td>
								<input type="checkbox" name="<?php echo $this->option_name?>[firewall_block_all_incomming]" value="1" <?php echo $options["firewall_block_all_incomming"] == "1" ? "checked='checked'" : "";?>/>
								<p class="description">Block all incomming requests that are not whitelisted below. </p>
								</td>
							</tr>
							
							<tr valign="top">
								<th scope="row">IP Whitelist: </th>
								<td>
								<textarea name="<?php echo $this->option_name?>[firewall_ip_whitelist]" style="margin: 0px;width: 340px;height: 140px;resize: none;" placeholder="127.0.0.1, ::1"><?php echo $options["firewall_ip_whitelist"]; ?></textarea>
								<p class="description">Enter IP addresses separated by commas. IPV4 and IPV6 are supported.</p>
								</td>
							</tr>
						</table>

					</div>
					
					<!-- CLIENTS -->
					<div id="clients">
						<h2>
							Clients
							<a href="<?php echo site_url(); ?>?wpoauthincludes=create&_wpnonce=<?php echo wp_create_nonce( 'wpo-create-client' ); ?>&TB_iframe=true&width=600&height=420" class="add-new-h2 thickbox" title="Add New Client">Add New Client</a>
							
<!-- 							<a href="javascript:void(0)" id="wpoauth-add-new-client" class="add-new-h2" title="Add New Client" data-site_url="<?php echo site_url(); ?>">Add New Client</a> -->
						</h2>
						
						<?php
						$wp_list_table = new WO_Table();
						$wp_list_table->prepare_items();
						$wp_list_table->display();
						?>
					</div>
					
					
					
	        		</div>
	        	
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes')?>" />
				</p>
	      	</form>
		</div>
		
		<?php
	}
	
	public function options_do_page() {
		$options = get_option($this->option_name);
		$this->admin_head();
		$scopes = apply_filters('WO_Scopes', null);
		
		error_reporting(0);
		add_thickbox();
		?>
		
			<div class="wrap">
	      	<h2>WP OAuth2 Server <strong><small> | v<?php echo _WO()->version; ?></small></strong></h2>
	     	<br/> 
	     	<p>VERSION 3.1.9 MOD</p>
      	<form method="post" action="options.php">
					<?php settings_fields('wo_options');?>
        	<div id="wo_tabs">
						<ul>
					  	<li><a href="#general-settings">General Settings</a></li>
					  	<li><a href="#advanced-configuration">Advanced Configuration</a></li>
					  	<li><a href="#clients">Clients</a></li>
						</ul>

						<!-- GENERAL SETTINGS -->
						<div id="general-settings">
					  	<table class="form-table">


	            <h3>Misc Settings <hr></h3>

					  </div>

					  <!-- CLIENTS -->
					  <div id="clients">
					  	<h2>
					  		Clients
					  		<a href="<?php echo site_url(); ?>?wpoauthincludes=create&_wpnonce=<?php echo wp_create_nonce( 'wpo-create-client' ); ?>&TB_iframe=true&width=600&height=420" class="add-new-h2 thickbox" title="Add New Client">Add New Client</a>
					  	</h2>

							<?php
							$wp_list_table = new WO_Table();
							$wp_list_table->prepare_items();
							$wp_list_table->display();
							?>
						</div>

					</div>

          <p class="submit">
          	<input type="submit" class="button-primary" value="<?php _e('Save Changes')?>" />
          </p>
        </form>
	    </div>
<?php
}

	/**
	 * WO options validation
	 * @param  [type] $input [description]
	 * @return [type]        [description]
	 */
	public function validate($input) {

		// Check box values
		$input["enabled"] = isset($input["enabled"]) ? $input["enabled"] : 0;
		$input["auth_code_enabled"] = isset($input["auth_code_enabled"]) ? $input["auth_code_enabled"] : 0;
		$input["client_creds_enabled"] = isset($input["client_creds_enabled"]) ? $input["client_creds_enabled"] : 0;
		$input["user_creds_enabled"] = isset($input["user_creds_enabled"]) ? $input["user_creds_enabled"] : 0;
		$input["refresh_tokens_enabled"] = isset($input["refresh_tokens_enabled"]) ? $input["refresh_tokens_enabled"] : 0;
		$input["implicit_enabled"] = isset($input["implicit_enabled"]) ? $input["implicit_enabled"] : 0;

		$input["require_exact_redirect_uri"] = isset($input["require_exact_redirect_uri"]) ? $input["require_exact_redirect_uri"] : 0;
		$input["enforce_state"] = isset($input["enforce_state"]) ? $input["enforce_state"] : 0;
		$input["use_openid_connect"] = isset($input["use_openid_connect"]) ? $input["use_openid_connect"] : 0;

		if(!isset($input['id_token_lifetime']))
			$input['id_token_lifetime'] = 3600;

		if(!isset($input['access_token_lifetime']))
			$input['access_token_lifetime'] = 3600;

		if(!isset($input['refresh_token_lifetime']))
			$input['refresh_token_lifetime'] = 86400;

		// Only run with valid license
		$input["blacklist_ip_range_enabled"] = isset($input["blacklist_ip_range_enabled"]) ? $input["blacklist_ip_range_enabled"] : 0;
		$input["block_all_incomming"] = isset($input["block_all_incomming"]) ? $input["block_all_incomming"] : 0;

		return $input;
	}
}
WPOAuth_Admin::init();