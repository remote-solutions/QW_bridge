<?php
   /*
   Plugin Name: Quickbase to Wordpress bridge
   Plugin URI: 
   description: A plugin to create a connection between two awesome sites
   Version: 1.0
   Author: El Luis
   Author URI: 
   License: 
   */

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

if( ! class_exists( 'QW_List_Table' ) ) {
    require_once( ABSPATH . 'wp-content/plugins/qw-bridge/qw-extended-list-table.php' );
}

if ( !class_exists( 'QW_Bridge' ) ) {
  class QW_Bridge
  {
    protected $glob;
    protected $qw_table_settings = 'qw_bridge_settings';
    public $userID;
    public $PUGLIN_NAME = 'qw-bridge-settings';
    public $QB_CREATOR_ID = "bqvbvsb7n";
    public $QB_PROJECT_ID = "bqvb5bnk5";
    public $USER_TOKEN;
    public $APP_TOKEN;

    public function __construct() {

      global $wpdb;
      $this->wpdb =& $wpdb;

      // Register user in QB upon WP registration
      // add_action( 'init', array( $this, 'qw_pre_setup' ) );
      add_action( 'init', array( $this, 'qw_pre_setup' ) );
      // add_action( 'user_register', array( $this, 'registerOnQB' ) );
      
      add_action( 'admin_post_qw_form_response', array( $this, 'qw_settings_form_response' ) );

      // Add menu page
      add_action( 'admin_menu', array( $this, 'qw_add_menu_pages' ) );
    }
 
    public function qw_add_menu_pages() {
      add_menu_page( 'QW Bridge', 'QW Bridge', 'manage_options', 'qw-bridge', array($this, 'render_dashboard_page'), 'dashicons-feedback', 2 );
      add_submenu_page( 'qw-bridge', 'Dashboard', 'Dashboard', 'manage_options', 'qw-bridge');
      add_submenu_page( 'qw-bridge', 'Settings', 'Settings', 'manage_options', $this->PUGLIN_NAME, array($this, 'qw_render_settings_page'));
    }

    public function qw_render_settings_page(){

      $qw_user_token = get_user_meta($this->userID, 'qw_user_token');
      $qw_app_token = get_user_meta($this->userID, 'qw_app_token');
      $qw_qb_url = get_user_meta($this->userID, 'qw_qb_url');
      $qw_qb_table = get_user_meta($this->userID, 'qw_qb_table');

      // create nonce
      $qw_render_settings_values_nonce = wp_create_nonce( 'qw_render_settings_values' );

      if($qw_user_token && $qw_app_token && $qw_qb_url)
        $tables = new SimpleXMLElement($this->qw_get_tables());

      ?>
      <div class="wrap">

        <h2>Settings</h2>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
          <input type="hidden" name="action" value="qw_form_response">
          <input type="hidden" name="qw_save_settings_nonce" value="<?php echo $qw_render_settings_values_nonce ?>" />

          <div id="poststuff"> 
            <div id="post-body" class="metabox-holder columns-2">

              <!-- --------------------------------------------------- -->
              <!--                    MAIN AREA                        -->
              <!-- --------------------------------------------------- -->

              <div id="postbox-container-2" class="postbox-container">
                <div id="normal-sortables" class="meta-box-sortables ui-sortable">

                  <table class="form-table" role="presentation">

                    <tbody>
                      <tr>
                        <th scope="row"><label for="qw_user_token">User Token</label></th>
                        <td><input name="qw_user_token" type="text" id="qw_user_token" value="<?php echo $qw_user_token[0]; ?>" class="regular-text"></td>
                      </tr>

                      <tr>
                        <th scope="row"><label for="qw_app_token">App Token</label></th>
                        <td><input name="qw_app_token" type="text" id="qw_app_token" value="<?php echo $qw_app_token[0]; ?>" class="regular-text"></td>
                      </tr>

                      <tr>
                        <th scope="row"><label for="qw_qb_url">Quickbase URL</label></th>
                        <td><input name="qw_qb_url" type="text" id="qw_qb_url" value="<?php echo $qw_qb_url[0]; ?>" class="regular-text"></td>
                      </tr>

                      <?php 
                        if($tables){
                        ?>
                          <tr>
                            <th scope="row"><label for="qw_qb_table">Quickbase Table</label></th>
                            <td>
                              <select name="qw_qb_table">
                                <option>Select a Table</option>
                                <?php 
                                foreach($tables->databases->dbinfo as $table){
                                  if($qw_qb_table[0] == $table->dbid){
                                    echo "<option selected value='{$table->dbid}'>{$table->dbname}</option>";
                                  }else{
                                    echo "<option value='{$table->dbid}'>{$table->dbname}</option>";
                                  }
                                }
                                ?>
                              </select>
                            </td>
                            <!-- <td><input name="qw_qb_table" type="text" id="qw_qb_table" value="<?php echo get_user_meta($this->userID, 'qw_qb_table')[0]; ?>" class="regular-text"></td> -->
                          </tr>
                        <?php 
                        }
                      ?>
                    </tbody>
                  </table>

                </div>
              </div>
              <!-- /MAIN -->

            </div><!-- /post-body --> 

            <br class="clear"> 

          </div><!-- /poststuff -->

          <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
        </form>


      </div>
      <?php
    }

    public function qw_settings_form_response() {
    
      if( isset( $_POST['qw_save_settings_nonce'] ) && wp_verify_nonce( $_POST['qw_save_settings_nonce'], 'qw_render_settings_values') ) {

        // $table = $this->wpdb->prefix.$this->qw_table_settings;
        // $data = array('qw_auth_token' => $_POST['qw_auth_token'], 'qw_app_token' => $_POST['qw_app_token'], 'qw_qb_url' => $_POST['qw_qb_url']);
        // $format = array('%s','%s','%s');
        // $this->wpdb->insert($table, $data, $format);
        // $my_id = $this->wpdb->insert_id;
        // sanitize the input
        // $nds_user_meta_key = sanitize_key( $_POST['nds']['user_meta_key'] );
        // $nds_user_meta_value = sanitize_text_field( $_POST['nds']['user_meta_value'] );
        // $nds_user =  get_user_by( 'login',  $_POST['nds']['user_select'] );
        // $nds_user_id = absint( $nds_user->ID ) ;
        // $this->print_data($_POST);
        // $this->print_data($this->userID);

        update_user_meta( $this->userID, 'qw_user_token', $_POST['qw_user_token'] );
        update_user_meta( $this->userID, 'qw_app_token', $_POST['qw_app_token'] );
        update_user_meta( $this->userID, 'qw_qb_url', $_POST['qw_qb_url'] );
        update_user_meta( $this->userID, 'qw_qb_table', $_POST['qw_qb_table'] );
        // do the processing

        // add the admin notice
        $admin_notice = "success";

        // redirect the user to the appropriate page
        wp_redirect( 'admin.php?page=' . $this->PUGLIN_NAME );
        exit;
      }     
      else {
        wp_die( __( 'Invalid nonce specified', $this->PUGLIN_NAME ), __( 'Error', $this->PUGLIN_NAME ), array(
              'response'  => 403,
              'back_link' => 'admin.php?page=' . $this->PUGLIN_NAME,

          ) );
      }
    }

    public function render_dashboard_page(){
      // $projects = new SimpleXMLElement($this->get_all_projects());
      // $projects = [['title1', 'content1'],['title2', 'content2']];

      ?>
        <div class="wrap">
          <h2>Dashboard</h2>

          <!-- // if($projects){
          //   foreach($projects->record as $keys => $record){

          //     $this->print_data($record);

          //     // foreach($record as $k => $v){
          //     //   $this->print_data($k);
          //     // }
          //   }

          //   $this->list_table_page($projects);
          // } -->
        </div>
      <?php
    }

    public function list_table_page($data)
    {
        $exampleListTable = new QW_List_Table();
        $exampleListTable->prepare_items($data, 5);
        ?>
            <div class="wrap">
                <h2>Example List Table Page</h2>
                <?php $exampleListTable->display(); ?>
            </div>
        <?php
    }

    public function qw_get_tables() {
      $xml = "<qdbapi>
                 <udata>mydata</udata>
                 <usertoken>{$this->USER_TOKEN}</usertoken>
                 <includeancestors>1</includeancestors>
                 <excludeparents>1</excludeparents>
              </qdbapi>";


      $response = $this->qb_call('main', "API_GrantedDBs", $xml);

      return $response;
    }

    public function get_all_projects(){

      $xml = "<qdbapi>
                <udata>mydata</udata>
                <usertoken>".$this->USER_TOKEN."</usertoken>
                <apptoken>".$this->APP_TOKEN."</apptoken>
                <query></query>
                <options>num-10</options>
                <includeRids>1</includeRids>
              </qdbapi>";


      $response = $this->qb_call($this->QB_PROJECT_ID, "API_DoQuery", $xml);

      return $response;
    }

    public function registerOnQB() {

      $xml = "<qdbapi>
                <udata>mydata</udata>
                <usertoken>".$this->USER_TOKEN."</usertoken>
                <apptoken>".$this->APP_TOKEN."</apptoken>
                <field fid='7'>luis.raul.c@outlook.com</field>
              </qdbapi>";

      $response = $this->qb_call($this->QB_CREATOR_ID, "API_AddRecord", $xml);

      // echo '<pre>'; print_r($xml); echo '</pre>';
      // echo '<pre>'; print_r($response); echo '</pre>';
      // die();
    }

    public function qw_pre_setup() {
      $this->userID = get_current_user_id();
      $this->USER_TOKEN = get_user_meta($this->userID, 'qw_user_token')[0];
      $this->APP_TOKEN  = get_user_meta($this->userID, 'qw_app_token')[0];
      $this->QB_URL  = get_user_meta($this->userID, 'qw_qb_url')[0];
    }

    public function qw_pre_setupOLD() {

      $qw_tablename = "{$this->wpdb->prefix}{$this->qw_table_settings}";
      $main_sql_create = "CREATE TABLE {$qw_tablename} (
                            qw_user_token varchar(255), 
                            qw_app_token varchar(255),
                            qw_qb_url varchar(255)
                          );";

      return maybe_create_table( $qw_tablename, $main_sql_create );
    }

    public function authenticate(){
      $xml = "<qdbapi>
               <udata>mydata</udata>
               <ticket>auth_ticket</ticket>
               <apptoken>app_token</apptoken>
            </qdbapi>";
    }

    public function qb_call($dbId = null, $action, $query){
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => $this->QB_URL."/db/" . $dbId,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $query,
        CURLOPT_HTTPHEADER => array(
        "quickbase-action: ".$action,
        "Content-Type: application/xml"
        ),
      ));

      $response = curl_exec($curl);

      curl_close($curl);

      return $response;
    }

    public function print_data($data){
      echo '<pre>'; print_r($data); echo '</pre>';
    }

  }
 
  $var = new QW_Bridge;
}