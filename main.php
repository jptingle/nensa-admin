<?php
/**
 * Plugin Name: NENSA Admin
 * Plugin URI: www.nensa.net
 * Description: Manage NENSA Results and Rankings.
 * Version: 0.1
 * Author: Jeffrey Tingle
 * Author URI: https://www.linkedin.com/in/jeffreytingle
 * License: GPL2
*/
include ("import_functions.php");
include ("load_tables.php");
include ("connection.php");
include ("neon_fetch.php");
include ("neon_retrieve.php");
include ("create_event.php");

class nensa_admin {

	// Setup options variables
	protected $option_name = 'nensa_admin';  // Name of the options array
	protected $data = array(  // Default options values
		'jq_theme' => 'smoothness'
	);

	public function __construct() {

		global $wpdb1;

		$wpdb1 = new wpdb(RESULTS_DB_USER, RESULTS_DB_PASSWORD, RESULTS_DB_NAME, RESULTS_DB_HOST);
		
		// Check if is admin
		// We can later update this to include other user roles
		if (is_admin()) {
      add_action( 'plugins_loaded', array( $this, 'nensa_admin_plugins_loaded' ));//Handles tasks that need to be done at plugins loaded stage.
			add_action( 'admin_menu', array( $this, 'nensa_admin_register' ));  // Create admin menu page
			add_action( 'admin_init', array( $this, 'nensa_admin_settings' ) ); // Create settings
			register_activation_hook( __FILE__ , array($this, 'nensa_admin_activate')); // Add settings on plugin activation
		}

	}
	
  public function nensa_admin_plugins_loaded(){
      
  }
        
	public function nensa_admin_activate() {
		update_option($this->option_name, $this->data);
	}
	
	public function nensa_admin_register(){
    $nensa_admin_page = add_submenu_page( 'options-general.php', __('NENSA Admin','nensa_admin'), __('NENSA Admin','nensa_admin'), 'manage_options', 'nensa_admin_menu_page', array( $this, 'nensa_admin_menu_page' )); // Add submenu page to "Settings" link in WP
		add_action( 'admin_print_scripts-' . $nensa_admin_page, array( $this, 'nensa_admin_admin_scripts' ) );  // Load our admin page scripts (our page only)
		add_action( 'admin_print_styles-' . $nensa_admin_page, array( $this, 'nensa_admin_admin_styles' ) );  // Load our admin page stylesheet (our page only)
	}
	
	public function nensa_admin_settings() {
		register_setting('nensa_admin_options', $this->option_name, array($this, 'nensa_admin_validate'));
	}
	
	public function nensa_admin_validate($input) {
		$valid = array();
		$valid['jq_theme'] = $input['jq_theme'];
		return $valid;
	}
	
	public function nensa_admin_admin_scripts() {
		wp_enqueue_script('media-upload');  // For WP media uploader
		wp_enqueue_script('thickbox');  // For WP media uploader
		wp_enqueue_script('jquery-ui-tabs');  // For admin panel page tabs
		wp_enqueue_script('jquery-ui-dialog');  // For admin panel popup alerts

		wp_enqueue_script( 'ajax-script', plugins_url( '/js/nensa_ajax.js', __FILE__ ), array('jquery') );
		wp_enqueue_script( 'nensa_admin', plugins_url( '/js/admin_page.js', __FILE__ ), array('jquery') );  // Apply admin page scripts

		wp_localize_script( 'nensa_admin', 'nensa_admin_pass_js_vars', array( 'ajax_image' => plugin_dir_url( __FILE__ ).'images/loading.gif', 'ajaxurl' => admin_url('admin-ajax.php'), 'upload_url' => admin_url('async-upload.php'), 'nonce' => wp_create_nonce('media-form') ) );
		//wp_localize_script( 'nensa_admin', 'wp_csv_to_db_pass_js_vars', array( 'ajax_image' => plugin_dir_url( __FILE__ ).'images/loading.gif', 'ajaxurl' => admin_url('admin-ajax.php') ) );
	}
	
	public function nensa_admin_admin_styles() {
		wp_enqueue_style('thickbox');  // For WP media uploader
		wp_enqueue_style('sdm_admin_styles', plugins_url( '/css/admin_page.css', __FILE__ ));  // Apply admin page styles
		
		// Get option for jQuery theme
		$options = get_option($this->option_name);
		$select_theme = isset($options['jq_theme']) ? $options['jq_theme'] : 'smoothness';
		?><link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/<?php echo $select_theme; ?>/jquery-ui.css"><?php  // For jquery ui styling - Direct from jquery
	}

	public function nensa_admin_menu_page() {

	  if(!current_user_can('manage_options')){
	      wp_die('Error! Only site admin can perform this operation');
	  }
            
		// Set variables		
		global $wpdb1;
		$error_message = '';
		$success_message = '';
		$message_info_style = '';

		$member_skier_date = get_option('member_skier_date');
	  if ($member_skier_date == false) {
	    add_option('member_skier_date','Never Processed');
	    $member_skier_date = 'Never Processed';
	  }
		
		// If there is a message - info-style
		if(!empty($message_info_style)) {
			echo '<div class="info_message_dismiss">';
			echo $message_info_style;
			echo '<br /><em>('.__('click to dismiss','nensa_admin').')</em>';
			echo '</div>';
		}
		
		// If there is an error message	
		if(!empty($error_message)) {
			echo '<div class="error_message">';
			echo $error_message;
			echo '<br /><em>('.__('click to dismiss','nensa_admin').')</em>';
			echo '</div>';
		}
		
		// If there is a success message
		if(!empty($success_message)) {
			echo '<div class="success_message">';
			echo $success_message;
			echo '<br /><em>('.__('click to dismiss','nensa_admin').')</em>';
			echo '</div>';
		}

		// Could not get final import results submit working with ajax
		// due to access to _FILE to doing an old fashion page load
		$result_load_count = 0;
		if(isset($_POST["import_season"]) && isset($_FILES["async-upload"])){
			$file = $_FILES["async-upload"]["tmp_name"];
			$event_name = $_POST["load_results_race_select"];
			$result_load_count = load_race_data($event_name, $file);
		}

		?>
		<div class="wrap">
        
      <h2><?php _e('NENSA Result, Event and Member Management','nensa_admin'); ?></h2>
      
      <p>This plugin allows you to manage NENSA Result, Member and Event data.</p>
      
      <div id="tabs">
        <ul>
  				<li><a href="#tabs-1"><?php _e('Member Lookup','nensa_admin'); ?></a></li>
  				<li><a href="#tabs-2"><?php _e('Load Results','nensa_admin'); ?></a></li>
          <li><a href="#tabs-3"><?php _e('DataTable Notes','nensa_admin'); ?></a></li>
        </ul>
          <div id="tabs-1">
          	<h1>NENSA Member Update From NEON</h1>
					  </br>
					  <form action=# method="POST" >
					    <input type="hidden" name="searchCriteria" value=true/>
					    <input id="reload_all_from_neon" type="checkbox" name="reload" value=true> Reload All Members</br></br>
					    <input id="neon_member_load" type="input" type="button" class="button-primary" value="<?php _e('Load Member Tables', 'nensa_admin') ?>" /></br>
					  </form>
					  </br>
					  <p id='date_of_last_load'>Date last loaded: <?php echo $member_skier_date ?></p>
					  <p id='skier_load_counts'></p>
					  <p id='season_load_counts'></p>
					  <hr>
          </div> <!-- End tab 1 -->
          <div id="tabs-2">
          	<header>
	          	<h1>Load Race Results</h1>
	          </header>
						<form action=# id="import" name="import" method="post" enctype="multipart/form-data", class="image-form" >
							<table class="form-table">
					      <tr valign="top">
					      	<th scope="row">
					      		<?php _e('Select Season:','nensa_admin'); ?>
					      	</th>
					        <td>
						          <select name="import_season" id="import_season" >
						          	<?php
						          	if(isset($_POST['import_season'])){
						          		$seasons=array("2020","2019","2018","2017","2016");
						          		foreach ($seasons as $season) {
														if($_POST['import_season']==$season) {
															echo "<option selected value='$season'>$season</option>";	
														} else {
															echo "<option value='$season'>$season</option>";
														}
													}
												} else { 
													echo "<option value=2020>2020</option>";
							          	echo "<option value=2019>2019</option>";
							          	echo "<option value=2018>2018</option>";
							            echo "<option value=2017>2017</option>";
							            echo "<option value=2016>2016</option>";
							          }
						            ?>
						          </select>
								  </td>
								</tr>
								<tr valign="top"><th scope="row"><?php _e('Select Event:','nensa_admin'); ?></th>>
									<td>
										<select id="load_results_event_select" name="load_results_event_select" value="">
								      <?php
					          	if(isset($_POST['load_results_event_select'])){
					          		$event=$_POST['load_results_event_select'];
					          		echo "<option selected name='$event' value='$event'>$event</option>";
											} else { 
						            echo "<option name='' value=''></option>";
						          }
					            ?> 
										</select>
									</td>
								</tr>
								<tr valign="top"><th scope="row"><?php _e('Select Race:','nensa_admin'); ?></th>
					        <td>
								    <select id="load_results_race_select" name="load_results_race_select" value="">
								      <?php
					          	if(isset($_POST['load_results_race_select'])){
					          		$race=$_POST['load_results_race_select'];
					          		echo "<option selected name='$race' value='$race'>$race</option>";
											} else { 
						            echo "<option name='' value=''></option>";
						          }
					            ?> 
								    </select>
								  </td>
								</tr>
								 <tr valign="top"><th scope="row"><?php _e('Select CSV File:','nensa_admin'); ?></th>
								  <td>
								  	<input id="results_file" type="file" name="async-upload" class="image-file" />
								  </td>
								</tr>
							</table>
							<p class="submit">
						    <input id="import_race_results" type="submit" class="button-primary" value="<?php _e('Import Race', 'nensa_admin') ?>" />
						  </p>
						  <?php 
						  if(isset($_POST['load_results_race_select'])){
					      echo "$result_load_count results were loaded";
							} 
							?>
					  </form>
          </div> <!-- End tab 2 -->
          <div id="tabs-3">
          	<header>
          		<h2>Notes for NensaAdmin/DataTables Plugin(s) Integration</h1>
          	</header>
            <ul>
                <li>This plugin also contains shortcode wrappers that allow one to embedded wpDataTables in any page.</li>
                <li>There are two shortcodes wrappers available.  One for the results based tables and another for the JN rankings.</li>
                <li>The results datatable is invoked by embedding [nensa_event_results  datatables_id=n] in the page content (visual or text) editor.</li>
                <li>The rankings datatable is invoked by embedding [nensa_jn_ranking  datatables_id=n] in the page content (visual or text) editor.</li>
                <li>Creating a shortcode wrappers is very easy - see load_tables.php for examples.  Seven lines of code plus any processing function.</li>
                <li>The shortcode wrappers and underlying functions are independent of any template code.</li>
            </ul>
          </div> <!-- End tab 4 -->
      </div> <!-- End #tabs -->
    </div> <!-- End page wrap -->
    
    <?php
	}
	
}
$nensa_admin = new nensa_admin();

// Add plugin settings link to plugins page
add_filter( 'plugin_action_links', 'nensa_admin_plugin_action_links', 10, 4 );
function nensa_admin_plugin_action_links( $links, $file ) {
	
	$plugin_file = 'nensa_admin/main.php';
	if ( $file == $plugin_file ) {
		$settings_link = '<a href="' .
			admin_url( 'options-general.php?page=nensa_admin_menu_page' ) . '">' .
			__( 'Settings', 'nensa_admin' ) . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}

// Load plugin language localization
add_action('plugins_loaded', 'nensa_admin_lang_init');
function nensa_admin_lang_init() {
	load_plugin_textdomain( 'nensa_admin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}


add_action('wp_ajax_season_select','season_select');
function season_select() {
	global $wpdb1;
	if(isset($_POST["action"]) && $_POST["action"] == 'season_select') {
		$event_season = $_POST["season"];
	} else {
		wp_die();
	}
	
	$season = "";
	$sql = "SELECT  event_name  FROM RACE_EVENT WHERE season='$event_season' AND parent_event_id is null;";
	$results = $wpdb1->get_results($sql);
	foreach($results as $index => $value) {
		foreach($value as $eventName) {
			$season = $season.";".$eventName;
		}
	}

	$response = json_encode( array( 'season' => $season ) );
  header( "Content-Type: application/json" );
  echo $response;
	wp_die();
}

add_action('wp_ajax_race_select','race_select');
function race_select() {
	global $wpdb1;
	if(isset($_POST["action"]) && $_POST["action"] == 'race_select') {
		$event_name = $_POST["event_name"];
	} else {
		wp_die();
	}

	$sql = "SELECT event_id FROM RACE_EVENT WHERE event_name='$event_name';";
	$event_id = $wpdb1->get_var($sql);

	if (is_null($event_id)) {
		wp_die();
	} 
	
	$sql = "SELECT  event_name  FROM RACE_EVENT WHERE parent_event_id='$event_id';";
	$results = $wpdb1->get_results($sql);
	foreach($results as $index => $value) {
		foreach($value as $eventName) {
			$event_list = $event_list.";".$eventName;
		}
	}

	$response = json_encode( array( 'event_list' => $event_list ) );
  header( "Content-Type: application/json" );
  echo $response;
	wp_die();
}


