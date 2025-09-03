<?php

/**
 * Plugin Name: AI Community Discussions
 * Description: Demonstrates AI integration workflow for a custom post type. Adds an "AI Summary" meta box that simulates generating a summary, stores it as post meta, shows it on the front‑end, and provides a settings page for summary length.
 * Version: 1.0.0
 * Author: Marwan Shokry
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain: aicd
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check PHP Version
if (version_compare(phpversion(), '7.4.0', '<')) {
	add_action('all_admin_notices', 'aicd_php_version_notice');
	return;
}

// Check PHP Version
function aicd_php_version_notice() {

	if (!current_user_can('manage_options')) {
		return;
	}

	?>
	<div class="notice notice-error is-dismissible">
		<p><strong><?php esc_html_e('AI Community Discussions', 'aicd'); ?></strong></p>
		<p><?php esc_html_e('AI Community Discussions Plugin has been deactivated.', 'aicd');?></p>
		<?php /* translators: %s: Required PHP version */ ?>
		<p><?php printf(esc_html__('This plugin requires PHP version %s.', 'aicd'), '<strong>7.4+</strong>'); ?></p>
		<?php /* translators: %s: PHP version */ ?>
		<p><?php printf(esc_html__('Your current PHP version is %s.', 'aicd'), '<strong>'.phpversion().'</strong>'); ?></p>
		<p><?php _e('You should update your PHP to use the plugin', 'aicd'); ?></p>
	</div>
	<?php
	deactivate_plugins(__FILE__, true);

}

// Contants
const postType = 'ai_comm_discussions';
const metaKeySummary = '_ai_community_discussions_summary';
const optionSettingsKey = 'ai_community_discussions_settings';
const nonceAction = 'ai_community_discussions_generate_summary_nonce';


// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-ai-community-discussions-activator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-ai-community-discussions-cpt.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-ai-community-discussions-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-ai-community-discussions-front.php';


// Activation
register_activation_hook(__FILE__, ['AICommunityDiscussions_Activator', 'activate']);

add_action('plugins_loaded', function () {
    $cpt = new AICommunityDiscussions_CPT();
    add_action('init', [$cpt, 'register']);

    if (is_admin()) {
        $admin = new AICommunityDiscussions_Admin(plugin_dir_path(__FILE__), plugin_dir_url(__FILE__));
        $admin->hooks();
    }

    $public = new AICommunityDiscussions_Public(plugin_dir_path(__FILE__), plugin_dir_url(__FILE__));
    $public->hooks();
});