<?php 
/*
	Plugin Name: Arevico Security Basic
	Plugin URI: http://wordpress.org/plugins/arevico-security-basic/
	Description: Arevico Security Basic
	Author: Arevico
	Version: 1.0
	Author URI: http://arevico.com/
 */

if (!class_exists('SQA'))
		require('admin-assets/moscow.php');

add_action( 'plugins_loaded', array('ArevicoSecurity','init') );

class ArevicoSecurity
{
	protected $user_email ="";
	protected $attempts = 0;
	protected $credential = "";
	protected $options = array();

	public static function init(){
		
		if (is_admin() && is_super_admin() ){ //is_admin refers to an admin page while is_super_admin refers to the highst level on either a network or a network disabled site
			require('opt-security-admin.php');
			$ArevicoSecurityAdmin = new ArevicoSecurityAdmin();
		
		} elseif (!is_admin()) {
			$ArevicoSecurity = new ArevicoSecurity();
		
		}
	
	}


	function __construct()
	{
		$this->options = get_option('arevico-security');
		
		add_action('wp_login_failed'		, array($this,'login_failed'));
		add_action('wp_login', 				array($this,'logon' ));//remove transients and stuff
		add_filter('login_errors'			, array($this,'login_error_message'));
		add_filter('validate_password_reset', array($this,'strong_password'));
		add_action( 'user_profile_update_errors', array($this,'strong_password_profile'), 0, 3 )	;
		add_action('profile_update' 		,array($this,'profile_update'),10,2 );
		if ($this->is_login() ){
			add_action('init',array($this,'email_login') )	;
		}

		if (is_admin()|| $this->is_login() )
			add_action('init', array($this,'force_change'))		;

	}

	public function email_login(){
		global $wpdb;
		
		if (!SQA::is_post()){
			$this->reset_locked();
			return;
		}

		if (empty($_POST['pwd']))	
			$_POST['pwd']="-1";

		if (empty($_POST['log']) || ((!is_email($_POST['log'])) && isset($this->options['pwd'])) ){
			$_POST['log']= "-1";

		} else  {
			if (isset($this->options['pwd'])){
				$email = $_POST['log'];
				$this->email_to_username($email);
			} else {
				$email = $this->user_to_email($_POST['log']);
			}

			if ($this->options['pwd_req_inval'])
				$this->credential = $_POST['pwd']; //this is used for check if current credentials are adequate
			
			$this->lock_account($_POST['log'],$email_login);
		}


	}
	
	private function user_to_email($user_name){
		$user_data = get_user_by('login',$_POST['log']);
		return $user_data->user_email;
	}

	private function reset_locked(){

		$user_name=(get_transient("llr_{$_REQUEST['arv_reset']}"));
		if ($user_name==false)
			return ;

		if((!SQA::is_post()) && (!empty($_REQUEST['arv_reset'])) && strcmp($_REQUEST['arv_reset'],$this->generate_reset_hash($user_name))==0){
			$this->del_transients($user_name);
		}

	}
	
	private function email_to_username($email){
		$this->user_email 	= $_POST['log']; //no hustling with casesensitive mails (due to hash function)
		$user 	 			= get_user_by('email',$_POST['log']);
		$user 				= ($user ==false) ? "-1" : $user->user_login;	
		$_POST['log'] 		= $user;
		return $user;
	}
	
	private function generate_reset_hash($user_name){
		$reset_hash	= sha1($user_name . "Salt&Pepper" . wp_create_nonce('reset_attempts') );
		return $reset_hash;
	}

	private function lock_account($user_name, $email){

		$hash = sha1(strtolower($user_name));
		$reset_hash	= $this->generate_reset_hash($user_name);
		$reset_url 	= wp_login_url(). "?arv_reset={$reset_hash}";

		$merge_tags  	= array(array(
			"user_name" 	=> $user_name,
			"link"  	=> $reset_url
			));
	
		$attempts = (empty($this->options['lockout'])) ? 0 : $this->options['lockout'];
		

		if(isset($this->options['do_lockout']) && $attempts>0 && get_transient( "llf_{$hash}")>$attempts){
			if ( isset($this->options['reset_link']) && get_transient("llr_{$reset_hash}")==false ){
				$this->send_login_email($reset_hash, $user_name,$merge_tags);
			}
			wp_die($this->options['lock_message']);
			exit();
		}
	}


	private function send_login_email($reset_hash,$user_name,$merge_tags){
		$lock_out 	= min( $this->options['lockout_time'],60*60*24);
		set_transient("llr_{$reset_hash}",$user_name, $lock_out) ;
		$email = $this->user_to_email($user_name);

		$subject 	= SQA::arr_val_map($this->options['lock_subject'] 	, $merge_tags, false, false);
		$body 		= SQA::arr_val_map($this->options['lock_email'] 	, $merge_tags, false, false);
	
		wp_mail( $email, $subject , $body);								
	}


	public function login_error_message($error){
    	//check if that's the error you are looking for
    	if (isset($this->options['cem']))
	    	return $this->options['cem_message'];
		return $error;
	}

	public function login_failed($user){
		if ($user != "-1" && isset($this->options['do_lockout'])){
				$hash = sha1(strtolower($user)	);
				$lck_time = ( $this->options['lockout_time']);

				set_transient( "llf_{$hash}",get_transient("llf_{$hash}")+1 ,$lck_time );
				
				if (!empty($this->user_email))
					$user = $this->user_email;		
				}
				$_POST['log']= $this->user_email;
			}

	public function is_login(){
		return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ); 
	}

	public function is_profile(){
		return in_array( $GLOBALS['pagenow'], array( 'profile.php' ) ); 
	}


	public function strong_password($errors, $user_data){
		$password = $_POST[ 'pass1' ];
		$user_id = isset( $user_data->ID ) ? $user_data->ID : false;
		$username = isset( $_POST["user_login"] ) ? $_POST["user_login"] : $user_data->user_login;


		if (!empty($this->options['pwd_req_cap']) && strcmp(strtolower($password),$password)==0)
			$errors->add( 'pass',"<strong>ERROR:</strong> Password must contain a capital letter</strong>");

		if (!empty($this->options['pwd_req_spec']) && !preg_match('/([^\\w ]|_)/i', $password))
			$errors->add( 'pass',"<strong>ERROR:</strong> Password must contain a special character</strong>");

		if (!empty($this->options['pwd_req_number']) && !preg_match('/([0-9])/i', $password))
			$errors->add( 'pass',"<strong>ERROR:</strong> Password must contain a number</strong>");

		if ( $errors->get_error_data("pass") || $password === false)
			return $errors;	

		return $errors;
	}

	public function strong_password_profile($errors, $update, $user_data ) {
		return $this->strong_password( $errors, $user_data );
	}

	public function force_change(){
		if (!is_user_logged_in() || $this->is_profile() || $this->is_login())
			return;

		$user  		= wp_get_current_user()->ID;
		$forced		= get_user_meta($user,'force_change',true);	

		if (isset($this->options['pwd_force_expire']))
			$this->check_password_expired($user);

		if (isset($this->options['pwd_req_inval']))
			$this->check_strong_cred($user);

		if 	($forced==1)
			$this->require_change();

	}

	private function check_strong_cred($user){
		$user_login = get_user_by('login',$user);
		$user_login = $user_login->ID;
		$password = $this->credential;

		if (empty($this->credential))
			return;

		if (
		(!empty($this->options['pwd_req_cap']) && strcmp(strtolower($password),$password)==0)
		|| (!empty($this->options['pwd_req_spec']) && !preg_match('/([^\\w ]|_)/i', $password))
		|| (!empty($this->options['pwd_req_number']) && !preg_match('/([0-9])/i', $password))
		)
		update_user_meta($user_id,'force_change',1 );
	
	}

	private function check_password_expired($user){
	$last_pass 	= get_user_meta( $user,'llf-last-pass',true);

		if (empty($last_pass)){
			update_user_meta($user,'llf-last-pass',time() );

		} 
		if ( ((time()-$last_pass) >= 60*60*24*($this->options['pwd_req_inval_time'])) )  {
			$this->require_change();
		}
	}

	private function require_change(){
			$adminlink=admin_url( 'profile.php');
			wp_die("The administrator requires you to update your password!<br /><a href=\"{$adminlink}\">Click here</a> to update your password.");
			exit;
	}

	public function profile_update($user_id, $old_user_data){
		update_user_meta($user_id,'llf-last-pass',time() );
		delete_user_meta($user_id,'force_change');
	}
	private function del_transients($user_name){
		$hash = sha1(strtolower($user_name));
		delete_transient("llr_{$_REQUEST['arv_reset']}");
		delete_transient("llf_{$hash}");
		delete_transient("llr_{$_REQUEST['arv_reset']}");
	}

	public function logon($user_login /* username */){
		$this->del_transients($user_login);
	}

}

 ?>