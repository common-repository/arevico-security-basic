<?php 

class ArevicoSecurityAdmin
{
	//an array of all slug which are being used and need javascript
	protected $slugs 		= array("top"=>"arevico-security-top");

	function __construct()
	{	

		add_action( 'admin_menu', array($this,'add_menus' ) );
		add_action( 'admin_enqueue_scripts', array($this,'add_admin_assets') );
		add_action( 'admin_init', array($this,'reg_set'));
	}

	/*
	 * Add all menus
	 */
	public function add_menus(){
    	add_submenu_page( 'options-general.php','Arevico Security', 'Arevico Security', 'manage_options', $this->slugs['top'], array($this,'do_page'));
	}

	/*
	 * Add all javascript and css on those pages which need them
	 */
	public function add_admin_assets(){
		if (!empty($_GET['page']) && in_array($_GET['page'], array_values($this->slugs) ) ){
			wp_enqueue_style( 'arevico-tab-css-admin',plugins_url('/admin-assets/style.css',__FILE__ ));
			wp_enqueue_script('arevico-tab-js-admin',plugins_url('/admin-assets/tabs.js',__FILE__), array( 'jquery' ) );

		}
	}
	/*
	 * Whitelist our settigns so we may store them
	 */
	public function reg_set(){
		register_setting("arevico-security-grp", 'arevico-security', array($this, 'dummysanitize') ); 
	}

	public function dummysanitize($set=""){
		return $set;
	}

/* = = = = = = = = = = = = = = = = = = THE VIEW PROCEEDS FROM HERE = = = = = = = = = = = = = = = = = = = = = = = = */
public function do_page(){
	$o = get_option('arevico-security',array ( 'pwd_req_inval_time' => '90', 'cem_message' => 'Wrong information! Make sure to login using your email!', 'lockout' => '123', 'lockout_time' => '3600', 'lock_message' => 'Too many failed login attempts, account is locked. Try later this day!', 'lock_subject' => '{$user_name} was locked out', 'lock_email' => "Hi {\$user_name}, Too many failed login attempts were detected, your account was locked. You can use the following link to unlock your account again: \n\{$link}\n\nI'm sorry for the inconvenience\n\nRegards,\nThe Administrator\n"));

?>
<div class="wrap" id="arevico-opt-page">
	

	<div class="formmes">
	<a href="http://wordpress.org/support/view/plugin-reviews/arevico-security-basic">Rate & review</a> this plugin.
</div>
	<form method="post" action="options.php" autocomplete="off">
	<?php 	settings_fields("arevico-security-grp");
?>
		<div class="tabbed">
			<div class="slheadcontainer">
				<a class="sltabhead">Authentication</a>
				<a class="sltabhead">Lockout</a>
			</div>
<div class="sltab">
	<span class="lblwide ilb"> Login Obscurity</span>
		<span class="lblmiddle ilb">
			<input type="checkbox" name="arevico-security[pwd]" value="1" <?php checked(SQA::val('pwd',$o,false,false),1); ?> /> Require users to login with registered email.<br />
		</span> <br />&nbsp;<br />
	<span class="lblwide ilb"> Password Strength</span>

		<span class="lblmiddle ilb"><i>Require strong password</i><br>
		<input type="checkbox" name="arevico-security[pwd_req_cap]" value="1" <?php checked(SQA::val('pwd_req_cap',$o,false,false),1); ?> /> Capital letter.<br />
		<input type="checkbox" name="arevico-security[pwd_req_spec]" value="1" <?php checked(SQA::val('pwd_req_spec',$o,false,false),1); ?> /> Special character (~!@#$%^&*()?{}[]/ or \).<br />
		<input type="checkbox" name="arevico-security[pwd_req_number]" value="1" <?php checked(SQA::val('pwd_req_number',$o,false,false),1); ?> /> Number(s).<br />
		<input type="checkbox" name="arevico-security[pwd_req_inval]" value="1" <?php checked(SQA::val('pwd_req_inval',$o,false,false),1); ?> /> Require password reset if current password is not compliant.<br />

	</span><br /><br />		
	<span class="lblwide ilb"> Password Invalidation</span>
		<span class="lblmiddle ilb">
			<input data-check="true" type="checkbox" id="invald" name="arevico-security[pwd_force_expire]" value="1" <?php checked(SQA::val('pwd_force_expire',$o,false,false),1); ?> /> Invalidate password after<br />
			<input type="text" name="arevico-security[pwd_req_inval_time]" value="<?php SQA::val('pwd_req_inval_time',$o,true,true); ?>" data-dep="#invald" style="width:100px;"> days
		</span><br />&nbsp;<br />	

	<span class="lblwide ilb"> Custom Login Error Message</span>
		<span class="lblmiddle ilb">
			<input type="checkbox" data-check="true" name="arevico-security[cem]" id="generalized" value="1" <?php checked(SQA::val('cem',$o,false,false),1); ?> /> Generalize failed login error to:<br />			
			<input style="width:600px;" type="text" name="arevico-security[cem_message]" value="<?php SQA::val('cem_message',$o,true,true); ?>" data-dep="#generalized" >
		</span>
		<br >
</div>

<div class="sltab">
	<span class="lblwide ilb"> Lockout</span>
		<span class="lblmiddle ilb"><input data-check="true" type="checkbox" id="lockcheck" name="arevico-security[do_lockout]" value="1" <?php checked(SQA::val('do_lockout',$o,false,false)); ?>/><i>Lockout an account after</i><br />
			<input data-dep="#lockcheck" style="width:100px;" type="text" name="arevico-security[lockout]" value="<?php SQA::val('lockout',$o,true,true); ?>" /> Attemps for 
			<select data-dep="#lockcheck" name="arevico-security[lockout_time]">
				<option value="60" <?php selected(SQA::val('lockout_time',$o,false,false),60); ?>>1 Minute</option>
				<option value="900" <?php selected(SQA::val('lockout_time',$o,false,false),900); ?>>15 Minutes</option>
				<option value="1800" <?php selected(SQA::val('lockout_time',$o,false,false),1800); ?>>30 Minutes</option>
				<option value="3600" <?php selected(SQA::val('lockout_time',$o,false,false),3600); ?>>1 Hour</option>
				<option value="18000" <?php selected(SQA::val('lockout_time',$o,false,false),18000); ?>>5 Hours</option>
				<option value="43200" <?php selected(SQA::val('lockout_time',$o,false,false),43200); ?>>12 Hours</option>
				<option value="86400" <?php selected(SQA::val('lockout_time',$o,false,false),86400); ?>>1 Day</option>
				<option value="99999999" <?php selected(SQA::val('lockout_time',$o,false,false),99999999); ?>>Indefinitely (user must request reset link)</option>
			</select>
			<br />
		</span> <br />

		<span class="lblwide ilb"> Lockout Message</span>
		<span class="lblmiddle ilb">
			<input style="width:600px;" data-dep="#lockcheck" type="text" name="arevico-security[lock_message]" value="<?php SQA::val('lock_message',$o,true,true); ?>" >
		</span><br />&nbsp;<br />
		<span class="lblwide ilb"> Lockout email subject</span>
		<span class="lblmiddle ilb">
			<input data-dep="#lockcheck" type="checkbox" id="resetcheck" name="arevico-security[reset_link]" value="1" <?php checked(SQA::val('reset_link',$o,false,false),1); ?> /> Send lockout link when a user tries to log into an locked account<br />
		</span><br />
		<span class="lblwide ilb"> Lockout email subject</span>
		<span class="lblmiddle ilb">
			<input style="width:600px;" data-dep="#lockcheck" type="text" name="arevico-security[lock_subject]" value="<?php SQA::val('lock_subject',$o,true,true); ?>" >
		</span><br />
		<span class="lblwide ilb"> Lockout message</span>
		<span class="lblmiddle ilb">
			<textarea style="width:600px;height:200px;" data-dep="#lockcheck" name="arevico-security[lock_email]"><?php SQA::val('lock_email',$o,true,true); ?></textarea>
		</span>
</div>

</div>

	<p class="submit">
		<input class="msubmit" type="submit" style="margin-right:100px;" class="button-primary" value=" Save Changes" />
	</p>
  
 </form>


</div>
<?php 
} 

}

?>