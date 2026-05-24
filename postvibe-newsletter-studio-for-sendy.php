<?php
/**
 * Plugin Name: PostVibe Newsletter Studio for Sendy
 * Plugin URI:  https://github.com/gunjanjaswal/postvibe-newsletter-studio-for-sendy
 * Description: Connects WordPress to Sendy (via Amazon SES) to create and send newsletters from your content. Visual builder, scheduling, multi-list, and editorial newsletter formats.
 * Version:     1.6.0
 * Author:      Gunjan Jaswal
 * Author URI:  https://gunjanjaswal.me
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: postvibe-newsletter-studio-for-sendy
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 7.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants.
define('PVNSS_VERSION', '1.6.0');
define('PVNSS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PVNSS_PLUGIN_URL', plugin_dir_url(__FILE__));

/* -------------------------------------------------------------------------
 * One-time migration from the legacy "Simple SES Bridge for Sendy" install.
 *
 * In 1.6.0 the plugin was renamed to "PostVibe Newsletter Studio for Sendy"
 * and all prefixes moved from `SSSB_`/`sssb_` to `PVNSS_`/`pvnss_`. To keep
 * existing installs working without data loss, this migration copies:
 *   - all `sssb_*` and `sssb_lists_*` options to their `pvnss_*` counterparts;
 *   - the `sssb_campaign` custom-post-type rows to `pvnss_campaign`;
 *   - every `_sssb_*` post-meta key to `_pvnss_*`;
 *   - any pending `sssb_send_scheduled_campaign` cron events to
 *     `pvnss_send_scheduled_campaign`.
 *
 * Guarded by the `pvnss_migrated_from_sssb` option so it runs at most once.
 * ---------------------------------------------------------------------- */
function pvnss_migrate_from_sssb() {
	if ( get_option( 'pvnss_migrated_from_sssb' ) ) {
		return;
	}
	global $wpdb;

	// 1) Copy known scalar/array options sssb_* → pvnss_*
	$option_keys = array(
		'sssb_settings',
		'sssb_remembered_lists',
		'sssb_footer_logo_url',
		'sssb_footer_copyright',
		'sssb_footer_custom_text',
		'sssb_more_articles_link',
		'sssb_social_instagram',
		'sssb_social_linkedin',
		'sssb_social_twitter',
		'sssb_social_youtube',
		'sssb_editorial_greeting',
		'sssb_editorial_intro',
		'sssb_editorial_hero_label',
		'sssb_editorial_grid_heading',
		'sssb_editorial_why_heading',
		'sssb_editorial_why_body',
		'sssb_editorial_collab_heading',
		'sssb_editorial_collab_body',
		'sssb_editorial_about_heading',
		'sssb_editorial_about_body',
		'sssb_cron_ssl_verify',
	);
	foreach ( $option_keys as $old_key ) {
		$new_key = 'pvnss_' . substr( $old_key, 5 );
		$value   = get_option( $old_key, null );
		if ( null !== $value && false === get_option( $new_key, false ) ) {
			update_option( $new_key, $value );
		}
	}

	// 1b) Sweep dynamic option keys (e.g. sssb_lists_*).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration, must hit DB directly.
	$dynamic = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'sssb\\_%' ESCAPE '\\\\'" );
	if ( $dynamic ) {
		foreach ( $dynamic as $old_key ) {
			$new_key = 'pvnss_' . substr( $old_key, 5 );
			$value   = get_option( $old_key, null );
			if ( null !== $value && false === get_option( $new_key, false ) ) {
				update_option( $new_key, $value );
			}
		}
	}

	// 2) Move sssb_campaign posts to pvnss_campaign.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration.
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s", 'pvnss_campaign', 'sssb_campaign' ) );

	// 3) Rename every _sssb_* post-meta key to _pvnss_* in one statement.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration.
	$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_key = REPLACE(meta_key, '_sssb_', '_pvnss_') WHERE meta_key LIKE '\\_sssb\\_%' ESCAPE '\\\\'" );

	// 4) Reschedule cron events from sssb_send_scheduled_campaign → pvnss_send_scheduled_campaign.
	$crons = _get_cron_array();
	if ( is_array( $crons ) ) {
		foreach ( $crons as $ts => $hooks ) {
			if ( ! isset( $hooks['sssb_send_scheduled_campaign'] ) ) {
				continue;
			}
			foreach ( $hooks['sssb_send_scheduled_campaign'] as $sig => $event ) {
				$args = isset( $event['args'] ) ? (array) $event['args'] : array();
				wp_unschedule_event( $ts, 'sssb_send_scheduled_campaign', $args );
				if ( ! wp_next_scheduled( 'pvnss_send_scheduled_campaign', $args ) ) {
					wp_schedule_single_event( $ts, 'pvnss_send_scheduled_campaign', $args );
				}
			}
		}
	}

	// 5) Flush rewrites so the renamed CPT registers cleanly.
	if ( function_exists( 'flush_rewrite_rules' ) ) {
		flush_rewrite_rules( false );
	}

	update_option( 'pvnss_migrated_from_sssb', PVNSS_VERSION );
}
add_action( 'plugins_loaded', 'pvnss_migrate_from_sssb', 1 );

/**
 * Main Plugin Class
 */
class PVNSS_Core
{

	/**
	 * Instance of the class.
	 *
	 * @var PVNSS_Core
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return PVNSS_Core
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
        $this->instantiate_classes();
    }

    /**
     * Include required files.
     */
    private function includes()
    {
        require_once PVNSS_PLUGIN_DIR . 'includes/class-sendy-api.php';
        require_once PVNSS_PLUGIN_DIR . 'includes/class-admin-settings.php';
        require_once PVNSS_PLUGIN_DIR . 'includes/class-newsletter-builder.php';
    }

    /**
     * Instantiate classes.
     */
    private function instantiate_classes()
    {
        if (is_admin()) {
            new PVNSS_Admin_Settings();
            new PVNSS_Newsletter_Builder();
        }
    }

    /**
     * Init hooks.
     */
    private function init_hooks()
    {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('init', array($this, 'register_post_type'));
        add_action('pvnss_send_scheduled_campaign', array($this, 'send_scheduled_campaign'));
        add_action('admin_notices', array($this, 'check_overdue_campaigns'));
        add_action('admin_init', array($this, 'handle_manual_send'));
    }

    /**
     * Register Custom Post Type for Campaigns
     */
    public function register_post_type()
    {
        register_post_type(
            'pvnss_campaign',
            array(
                'labels'      => array(
                    'name'          => __('Campaigns', 'postvibe-newsletter-studio-for-sendy'),
                    'singular_name' => __('Campaign', 'postvibe-newsletter-studio-for-sendy'),
                ),
                'public'      => false,
                'show_ui'     => true, // Show in admin to let user see history/status
                'show_in_menu' => 'postvibe_newsletter',
                'supports'    => array('title', 'custom-fields', 'editor'), // editor can hold HTML content
                'capability_type' => 'post',
                'capabilities' => array(
                    'create_posts' => false, // Only created via code
                ),
                'map_meta_cap' => true,
            )
        );

        // Add custom columns
        add_filter('manage_pvnss_campaign_posts_columns', array($this, 'add_campaign_columns'));
        add_action('manage_pvnss_campaign_posts_custom_column', array($this, 'manage_campaign_columns'), 10, 2);
    }

    public function add_campaign_columns($columns) {
        $columns['pvnss_status'] = __('Status', 'postvibe-newsletter-studio-for-sendy');
        $columns['pvnss_scheduled'] = __('Scheduled For', 'postvibe-newsletter-studio-for-sendy');
        $columns['pvnss_error'] = __('Error', 'postvibe-newsletter-studio-for-sendy');
        return $columns;
    }

    public function manage_campaign_columns($column, $post_id) {
        switch ($column) {
            case 'pvnss_status':
                $status = get_post_meta($post_id, '_pvnss_status', true);
                if (!$status) $status = 'draft';
                
                $color = '#999';
                if ($status === 'sent') $color = '#46b450';
                if ($status === 'scheduled') $color = '#ffb900';
                
                echo '<span style="font-weight:bold; color:' . esc_attr($color) . ';">' . esc_html(ucfirst($status)) . '</span>';
                break;

            case 'pvnss_scheduled':
                $scheduled = get_post_meta($post_id, '_pvnss_scheduled_time', true);
                if ($scheduled) {
                    echo esc_html($scheduled);
                } else {
                    echo '-';
                }
                break;

            case 'pvnss_error':
                $error = get_post_meta($post_id, '_pvnss_send_error', true);
                if ($error) {
                    echo '<span style="color: #d63638;">' . esc_html($error) . '</span>';
                } else {
                    echo '-';
                }
                break;
        }
    }

    /**
     * Handle Scheduled Campaign Sending
     */
    public function send_scheduled_campaign($post_id)
    {
        // Check if already sent to avoid duplicates (though CPT status should handle this)
        if (get_post_meta($post_id, '_pvnss_status', true) === 'sent') {
            return;
        }

        $campaign_data = array(
            'from_name' => get_post_meta($post_id, '_pvnss_from_name', true),
            'from_email' => get_post_meta($post_id, '_pvnss_from_email', true),
            'reply_to' => get_post_meta($post_id, '_pvnss_from_email', true), // Use from_email as reply_to
            'subject' => get_the_title($post_id),
            'html_text' => get_post_field('post_content', $post_id),
            'plain_text' => get_post_meta($post_id, '_pvnss_plain_text', true),
            'list_ids' => get_post_meta($post_id, '_pvnss_list_id', true),
            'send_campaign' => 1 // Always send when triggered by schedule
        );

        $sendy_api = new PVNSS_Sendy_API();
        $result = $sendy_api->create_campaign($campaign_data);

        if (is_wp_error($result)) {
            // Log error
            update_post_meta($post_id, '_pvnss_send_error', $result->get_error_message());
            update_post_meta($post_id, '_pvnss_status', 'failed');
        } else {
            // Mark as sent
            update_post_meta($post_id, '_pvnss_status', 'sent');
            update_post_meta($post_id, '_pvnss_sent_time', current_time('mysql'));
        }
    }

    /**
     * Check for overdue scheduled campaigns and show admin notice
     */
    public function check_overdue_campaigns()
    {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'pvnss_campaign') === false) {
            return;
        }

        // Check for overdue scheduled campaigns
        $args = array(
            'post_type' => 'pvnss_campaign',
            'post_status' => 'publish',
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary for filtering scheduled campaigns
                array(
                    'key' => '_pvnss_status',
                    'value' => 'scheduled',
                ),
            ),
            'posts_per_page' => -1,
        );

        $campaigns = get_posts($args);
        $overdue = array();

        foreach ($campaigns as $campaign) {
            $scheduled_time = get_post_meta($campaign->ID, '_pvnss_scheduled_time', true);
            if ($scheduled_time && strtotime($scheduled_time) < current_time('timestamp')) {
                $overdue[] = $campaign;
            }
        }

        if (!empty($overdue)) {
            foreach ($overdue as $campaign) {
                // Automatically send the overdue campaign
                $this->send_scheduled_campaign($campaign->ID);
                
                // Check if it was sent successfully
                $status = get_post_meta($campaign->ID, '_pvnss_status', true);
                
                if ($status === 'sent') {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p><strong>Overdue Campaign Sent:</strong> "' . esc_html($campaign->post_title) . '" was automatically sent.</p>';
                    echo '</div>';
                } else {
                    // If it failed, show error with retry button
                    $error = get_post_meta($campaign->ID, '_pvnss_send_error', true);
                    $retry_url = add_query_arg(array(
                        'action' => 'pvnss_retry_send',
                        'campaign_id' => $campaign->ID,
                        'nonce' => wp_create_nonce('pvnss_retry_send_' . $campaign->ID)
                    ), admin_url('admin.php'));

                    echo '<div class="notice notice-warning is-dismissible">';
                    echo '<p><strong>Campaign Failed to Auto-Send:</strong> "' . esc_html($campaign->post_title) . '"</p>';
                    if ($error) {
                        echo '<p><strong>Error:</strong> ' . esc_html($error) . '</p>';
                    }
                    echo '<p><a href="' . esc_url($retry_url) . '" class="button button-primary">Retry Send</a></p>';
                    echo '</div>';
                }
            }
        }

        // Check for failed campaigns
        $failed_args = array(
            'post_type' => 'pvnss_campaign',
            'post_status' => 'publish',
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary for filtering failed campaigns
                array(
                    'key' => '_pvnss_status',
                    'value' => 'failed',
                ),
            ),
            'posts_per_page' => -1,
        );

        $failed_campaigns = get_posts($failed_args);

        if (!empty($failed_campaigns)) {
            foreach ($failed_campaigns as $campaign) {
                $error = get_post_meta($campaign->ID, '_pvnss_send_error', true);
                $retry_url = add_query_arg(array(
                    'action' => 'pvnss_retry_send',
                    'campaign_id' => $campaign->ID,
                    'nonce' => wp_create_nonce('pvnss_retry_send_' . $campaign->ID)
                ), admin_url('admin.php'));

                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>Campaign Failed:</strong> "' . esc_html($campaign->post_title) . '"</p>';
                if ($error) {
                    echo '<p><strong>Error:</strong> ' . esc_html($error) . '</p>';
                }
                echo '<p><a href="' . esc_url($retry_url) . '" class="button button-primary">Retry Send</a></p>';
                echo '</div>';
            }
        }
    }

    /**
     * Handle manual send request
     */
    public function handle_manual_send()
    {
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
        
        if ($action !== 'pvnss_manual_send' && $action !== 'pvnss_retry_send') {
            return;
        }

        if (!isset($_GET['campaign_id']) || !isset($_GET['nonce'])) {
            return;
        }

        $campaign_id = intval($_GET['campaign_id']);
        
        $nonce_action = $action === 'pvnss_retry_send' ? 'pvnss_retry_send_' . $campaign_id : 'pvnss_manual_send_' . $campaign_id;
        
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), $nonce_action)) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // Clear previous error if retrying
        if ($action === 'pvnss_retry_send') {
            delete_post_meta($campaign_id, '_pvnss_send_error');
        }

        // Trigger the send
        $this->send_scheduled_campaign($campaign_id);

        // Redirect back
        wp_safe_redirect(admin_url('edit.php?post_type=pvnss_campaign&sent=1'));
        exit;
    }


    /**
     * Load text domain.
     */
    public function load_textdomain()
    {
        // load_plugin_textdomain('postvibe-newsletter-studio-for-sendy', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on our plugin pages
        $screen = get_current_screen();
        
        // Check if we are on the settings page or builder page
        $is_plugin_page = false;
        if ($screen && (
            strpos($screen->base, 'postvibe_newsletter') !== false || 
            strpos($screen->base, 'pvnss_newsletter_builder') !== false ||
            'pvnss_campaign' === $screen->post_type
        )) {
            $is_plugin_page = true;
        }

        if (!$is_plugin_page) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('pvnss-admin-style', PVNSS_PLUGIN_URL . 'admin/css/style.css', array(), PVNSS_VERSION);
        wp_enqueue_script('pvnss-admin-script', PVNSS_PLUGIN_URL . 'admin/js/script.js', array('jquery', 'jquery-ui-datepicker'), PVNSS_VERSION, true);

        
        $options = get_option('pvnss_settings');

        // Auto-fetch lists from Sendy (cached). Allows force refresh via ?pvnss_refresh_lists=1.
        // Read-only presence check on the GET flag — no state change happens from the flag alone,
        // it only bypasses the 10-minute cache for the subsequent get_lists() call. The enclosing
        // admin page already requires the manage_options capability, so a nonce is not needed here.
        $known_lists = array();
        if (class_exists('PVNSS_Sendy_API')) {
            $sendy_api  = new PVNSS_Sendy_API();
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only cache-bust flag on an admin-only page; capability check applies upstream.
            $force      = isset($_GET['pvnss_refresh_lists']);
            $known_lists = $sendy_api->get_lists($force);
        }
        
        wp_localize_script('pvnss-admin-script', 'pvnss_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pvnss_newsletter_nonce'),
            'site_url' => home_url(),
            'known_lists'      => $known_lists,
            'remembered_lists' => array_values((array) get_option('pvnss_remembered_lists', array())),
            'settings' => array(
                'footer_logo' => get_option('pvnss_footer_logo_url'),
                'footer_copyright' => get_option('pvnss_footer_copyright', '© {year} ' . get_bloginfo('name')),
                'more_articles_link' => get_option('pvnss_more_articles_link'),
                'social_instagram' => get_option('pvnss_social_instagram'),
                'social_linkedin' => get_option('pvnss_social_linkedin'),
                'social_twitter' => get_option('pvnss_social_twitter'),
                'social_youtube' => get_option('pvnss_social_youtube'),
                'footer_custom_text' => wp_kses_post(nl2br(get_option('pvnss_footer_custom_text'))),
                'show_article_excerpt' => isset($options['show_article_excerpt']) ? $options['show_article_excerpt'] : '',
                // Editorial format texts (HTML allowed; newlines converted to <br>)
                'editorial_greeting'       => wp_kses_post(nl2br(get_option('pvnss_editorial_greeting', ''))),
                'editorial_intro'          => wp_kses_post(nl2br(get_option('pvnss_editorial_intro', ''))),
                'editorial_hero_label'     => wp_kses_post(get_option('pvnss_editorial_hero_label', '')),
                'editorial_grid_heading'   => wp_kses_post(get_option('pvnss_editorial_grid_heading', '')),
                'editorial_why_heading'    => wp_kses_post(get_option('pvnss_editorial_why_heading', '')),
                'editorial_why_body'       => wp_kses_post(nl2br(get_option('pvnss_editorial_why_body', ''))),
                'editorial_collab_heading' => wp_kses_post(get_option('pvnss_editorial_collab_heading', '')),
                'editorial_collab_body'    => wp_kses_post(nl2br(get_option('pvnss_editorial_collab_body', ''))),
                'editorial_about_heading'  => wp_kses_post(get_option('pvnss_editorial_about_heading', '')),
                'editorial_about_body'     => wp_kses_post(nl2br(get_option('pvnss_editorial_about_body', ''))),
            )
        ));
    }
}

// Initialize the plugin.
function pvnss_init()
{
    return PVNSS_Core::get_instance();
}
add_action('plugins_loaded', 'pvnss_init');

// Add Settings + Support on Ko-fi links to plugin action links (next to Deactivate).
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pvnss_plugin_action_links');
function pvnss_plugin_action_links($links)
{
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=postvibe_newsletter')) . '">' . esc_html__('Settings', 'postvibe-newsletter-studio-for-sendy') . '</a>';
    $kofi_link     = '<a href="https://ko-fi.com/gunjanjaswal" target="_blank" style="color:#0073aa; font-weight:bold;">' . esc_html__('Support on Ko-fi', 'postvibe-newsletter-studio-for-sendy') . '</a>';
    array_unshift($links, $settings_link, $kofi_link);
    return $links;
}

// Plugin row meta: Plugin Support (WP.org forum) + Contact Developer.
add_filter('plugin_row_meta', 'pvnss_plugin_row_meta', 10, 2);
function pvnss_plugin_row_meta($links, $file)
{
    if (plugin_basename(__FILE__) === $file) {
        $links[] = '<a href="https://wordpress.org/support/plugin/postvibe-newsletter-studio-for-sendy/" target="_blank">' . esc_html__('Plugin Support', 'postvibe-newsletter-studio-for-sendy') . '</a>';
        $links[] = '<a href="mailto:hello@gunjanjaswal.me">' . esc_html__('Contact Developer', 'postvibe-newsletter-studio-for-sendy') . '</a>';
    }
    return $links;
}
