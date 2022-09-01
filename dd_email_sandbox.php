<?php
/*
Plugin Name: Email Sandbox
Plugin URI: http://
Description: Gives ability to Enable/Disable sending out emails via wp_mail hook. Also ability to redirect emails to a specified sandbox one.
Version: 1.0.2
Author: Stoycho Stoychev
Depends:
Bitbucket Plugin URI:
--------------------------------------------------------------------------------
*/
defined('ABSPATH') || die('do not access this file directly');

class dd_email_sandbox {

	// class instance
	private static $instance;
	private static $is_staging;

	public function __construct() {
		if (is_admin()) {
			self::$is_staging = class_exists('dd_on_every_client') ? dd_on_every_client::$is_staging : (strpos(defined('WP_SITEURL') ? WP_SITEURL : get_site_url(), 'staging') !== false);
			add_action('admin_menu', function () {
				$hook = add_submenu_page(
					'options-general.php',
					'Email Sandbox',
					'Email Sandbox',
					'manage_options',
					'dd_email_sandbox',
					array($this, 'dd_email_sandbox_page')
				);
				add_action("load-$hook", array($this, 'screen_option'));
			});
			if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'dd_email_sandbox')
			add_action('admin_enqueue_scripts', function () {
				$plugin_data = get_plugin_data(__FILE__);
				wp_register_style('dd_email_sandbox-admin', '/wp-content/plugins/dd_email_sandbox/css/admin.css', array(), $plugin_data['Version']);
				wp_enqueue_style('dd_email_sandbox-admin');
				wp_register_style('dd_email_sandbox-jquery-ui', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', array(), $plugin_data['Version']);
				wp_enqueue_style('dd_email_sandbox-jquery-ui');
				
				wp_register_script('dd_email_sandbox-admin', '/wp-content/plugins/dd_email_sandbox/js/admin.js', array(), $plugin_data['Version']);
				wp_enqueue_script('dd_email_sandbox-admin');
				wp_register_script('dd_email_sandbox-jquery-ui', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js', array(), $plugin_data['Version']);
				wp_enqueue_script('dd_email_sandbox-jquery-ui');
			});
			add_action('wp_loaded', array($this, 'wp_loaded_dd_email_sandbox'));
			add_action('admin_notices', array($this, 'admin_notices_dd_email_sandbox'));
		}
		add_filter('wp_mail', array($this, 'dd_email_sandbox_wp_mail'),1000);
		add_filter('bp_email_use_wp_mail', function ($use_it) {
			$use_it=true;
			return $use_it;
		});
		/*add_action('phpmailer_init', function ($phpmailer) {
			//disable WP phpmailer as well in case it is used directly i.e. BP_PHPMailer
			$phpmailer->clearAllRecipients();
			$phpmailer->clearAttachments();
			$phpmailer->clearCustomHeaders();
			$phpmailer->clearReplyTos();
			$phpmailer->Sender = '';
		});*/
	}
	
	public function dd_email_sandbox_wp_mail($atts) {
		if (empty($atts['to']) || $atts['to']=='errors@example.com') return $atts;
		if (get_option(__CLASS__.'_disable_sending_out_emails')) {
			$atts['to'] = array();
		} else {
			if (get_option(__CLASS__.'_enable_email_sandbox')) {
				$sandbox_emails = get_option(__CLASS__.'_emails');
				$exclude_emails = get_option(__CLASS__.'_exclude_emails');
				if (empty($sandbox_emails)) {
					$new_to=array();
				} else {
					$new_to = array_map('trim',explode(',', $sandbox_emails));
				}
				if (!empty($exclude_emails)) {
					$to=$atts['to'];
					if (!is_array($to))	$to = array_map('trim',explode(',', $to));
					$exclude_emails2 = array_map('trim',explode(',',$exclude_emails));
					foreach ($exclude_emails2 as $excl_em) {
						if (substr($excl_em,0,1)=='*') {
							$excl_em2 = explode('@', $excl_em);
							if (count($excl_em2)<2) continue;
							$excl_em2=$excl_em2[1];
							foreach ($to as $em_tosendto) {
								$em_tosendto2 = explode('@', $em_tosendto);
								if (count($em_tosendto2)<2) continue;
								$em_tosendto2=$em_tosendto2[1];
								if ($excl_em2==$em_tosendto2 && !in_array($em_tosendto, $new_to)) $new_to[]=$em_tosendto;
							}
						} else {
							if (in_array($excl_em, $to) && !in_array($excl_em, $new_to)) {
								$new_to[]=$excl_em;
							}
						}
					}
				}
				$atts['to'] = $new_to;
			}
		}
		return $atts;
	}
	
	
	private static function show_icon_information($with_tooltip='') { ?>
		<img src="/<?=PLUGINDIR.'/'.__CLASS__?>/images/icon_information.gif" align="absmiddle"<?=$with_tooltip!='' ? ' title="'. esc_attr($with_tooltip).'"' : ''?> class="icon_information" />
	<?php
	}
	public function dd_email_sandbox_page() {
		$disable_sending_out_emails = get_option(__CLASS__.'_disable_sending_out_emails');
		$enable_email_sandbox = get_option(__CLASS__.'_enable_email_sandbox');
		$sandbox_emails = get_option(__CLASS__.'_emails');
		$exclude_emails = get_option(__CLASS__.'_exclude_emails'); ?>
		<h2 style="margin-top: 25px;margin-left:15px;">Email Sandbox Settings</h2>
		<div class="dd_email_sandbox-wrapper">
			<form method="post" action="" class="dd_email_sandbox-admin-form" autocomplete="off">
			<?php wp_nonce_field('***', 'nonce'); ?>
			<div class="container">
				<div class="row">
					<div class="col-sm-4"><label for="<?=__CLASS__?>_disable_sending_out_emails">Disable sending out emails?</label><?php self::show_icon_information('If ticked - No emails will be sent out from the system.'); ?></div>
					<div class="col-sm-8"><input type="checkbox" name="<?=__CLASS__?>_disable_sending_out_emails" id="<?=__CLASS__?>_disable_sending_out_emails"<?=$disable_sending_out_emails ? ' checked' : ''?> value="1" /></div>
				</div>
				<div class="row dd-email-sandbox-enable-email">
					<div class="col-sm-4"><label for="<?=__CLASS__?>_enable_email_sandbox">Enable Email Sandbox?</label><?php self::show_icon_information('If ticked - All emails will be redirected to the "Sandbox email(s)" specified below, except those in "Exclude emails from sandbox". If no Sandbox email(s) are specified - no emails will be sent out (except those in "Exclude emails from sandbox").'); ?></div>
					<div class="col-sm-8"><input type="checkbox" name="<?=__CLASS__?>_enable_email_sandbox"<?=$enable_email_sandbox ? ' checked' : ''?> id="<?=__CLASS__?>_enable_email_sandbox" value="1" /></div>
				</div>
				<div class="row dd-email-sandbox-emails">
					<div class="col-sm-4"><label for="<?=__CLASS__?>_emails">Sandbox email(s)</label><?php self::show_icon_information('The Sandbox email(s) where all emails will be redirected to. It can be more than 1, comma separated.'); ?></div>
					<div class="col-sm-8"><input type="text" name="<?=__CLASS__?>_emails" id="<?=__CLASS__?>_emails" value="<?=$sandbox_emails?>"/></div>
				</div>
				<div class="row dd-email-sandbox-exclude-emails">
					<div class="col-sm-4"><label for="<?=__CLASS__?>_exclude_emails">Exclude emails from sandbox</label><?php self::show_icon_information('Email(s) that the system will send emails to. I.e. jim@gmail.com, *@yahoo.com (i.e. all emails under the yahoo.com domain will receive emails). Comma separated.'); ?></div>
					<div class="col-sm-8"><input type="text" name="<?=__CLASS__?>_exclude_emails" id="<?=__CLASS__?>_exclude_emails" value="<?=$exclude_emails?>" /></div>
				</div>
				<div class="row">
					<div class="col-sm-4"></div>
					<div class="col-sm-8"><input type="submit" name="submit_dd_email_sandbox_page" class="button button-primary button dd_email_sandbox-large-button" value="Save" /></div>
				</div>
			</div>
			</form>
		</div>
	<?php
	}
	public function admin_notices_dd_email_sandbox() {
		if (isset($_REQUEST['msg_dd_email_sandbox'])) {
			$class = 'notice';
			$class .= isset($_REQUEST['msg_type_dd_email_sandbox']) && $_REQUEST['msg_type_dd_email_sandbox']=='error' ? ' notice-error' : ' updated';
			printf('<div class="%1$s"><p>%2$s</p></div>', $class, $_REQUEST['msg_dd_email_sandbox']);
		}
		$disable_sending_out_emails = get_option(__CLASS__.'_disable_sending_out_emails');
		$enable_email_sandbox = get_option(__CLASS__.'_enable_email_sandbox');
		$sandbox_emails = get_option(__CLASS__.'_emails');
		$msg = '';
		if ($disable_sending_out_emails) {
			$class = 'notice notice-error';
			$msg .= 'Sending emails out is disabled. Please click <a href="/wp-admin/options-general.php?page=dd_email_sandbox">here</a> to change it.';
		} else if ($enable_email_sandbox && empty($sandbox_emails)) {
			$class = 'notice notice-error';
			if (self::$is_staging) {
				$msg .= 'This is a staging environment and Email Sandbox is enabled by default. ';
			}
			$msg .= 'Email Sandbox is enabled but no Sandbox email(s) are specified hence no emails will be sent out. Please click <a href="/wp-admin/options-general.php?page=dd_email_sandbox">here</a> to correct it.';
		}
		if ($msg!='') printf('<div class="%1$s"><p style="font-size:22px;">%2$s</p></div>', $class, $msg);
	}
	public function wp_loaded_dd_email_sandbox() {
		if (isset($_POST['submit_dd_email_sandbox_page']) && $_POST['submit_dd_email_sandbox_page']=='Save' && isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], '***')) {
			update_option(__CLASS__.'_disable_sending_out_emails', isset($_POST[__CLASS__.'_disable_sending_out_emails']) ? $_POST[__CLASS__.'_disable_sending_out_emails'] : 0);
			update_option(__CLASS__.'_enable_email_sandbox', isset($_POST[__CLASS__.'_enable_email_sandbox']) ? $_POST[__CLASS__.'_enable_email_sandbox'] : 0);
			update_option(__CLASS__.'_emails', isset($_POST[__CLASS__.'_emails']) ? stripslashes($_POST[__CLASS__.'_emails']) : '');
			update_option(__CLASS__.'_exclude_emails', isset($_POST[__CLASS__.'_exclude_emails']) ? stripslashes($_POST[__CLASS__.'_exclude_emails']) : '');
			wp_safe_redirect('/wp-admin/options-general.php?page=dd_email_sandbox&msg_dd_email_sandbox='.urlencode('Email Sandbox Settings have been saved'));exit;
		}
		add_filter('plugin_row_meta', array($this, 'plugin_row_meta_dd_email_sandbox'), 10, 2);
	}
	public function plugin_row_meta_dd_email_sandbox($links, $file) {
		if ($file == plugin_basename( __FILE__ )) {
			$links[] = '<a href="" target="_blank">Bitbucket</a>';
		}
		return $links;
	}
	public function screen_option() {
		$option = 'per_page';
		$args   = array(
			'label'   => 'Users',
			'default' => 5,
			'option'  => 'users_per_page'
		);
		add_screen_option( $option, $args );
	}
	public static function dd_email_sandbox_activation() {
		if (self::$is_staging) {
			update_option(__CLASS__.'_enable_email_sandbox', 1);
		}
	}
	public static function dd_email_sandbox_deactivation() {
		delete_option(__CLASS__.'_disable_sending_out_emails');
		delete_option(__CLASS__.'_enable_email_sandbox');
		delete_option(__CLASS__.'_emails');
		delete_option(__CLASS__.'_exclude_emails');
	}
	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
register_activation_hook(__FILE__, array('dd_email_sandbox','dd_email_sandbox_activation'));
register_deactivation_hook(__FILE__, array('dd_email_sandbox','dd_email_sandbox_deactivation'));
add_action('plugins_loaded', function () {
	dd_email_sandbox::get_instance();
});