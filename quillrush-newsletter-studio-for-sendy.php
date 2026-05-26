<?php
/**
 * Plugin Name: Quillrush Newsletter Studio for Sendy
 * Plugin URI:  https://wordpress.org/plugins/quillrush-newsletter-studio-for-sendy/
 * Description: Connects WordPress to Sendy (via Amazon SES) to create and send newsletters from your content. Visual builder, scheduling, multi-list, and editorial newsletter formats.
 * Version:     1.6.1
 * Author:      Gunjan Jaswal
 * Author URI:  https://gunjanjaswal.me
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: quillrush-newsletter-studio-for-sendy
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
define('QRNSS_VERSION', '1.6.1');
define('QRNSS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QRNSS_PLUGIN_URL', plugin_dir_url(__FILE__));

/* -------------------------------------------------------------------------
 * One-time data migration from earlier prefixes used by predecessor installs.
 *
 * Two legacy option/CPT/meta/cron prefixes are detected and migrated to the
 * current `qrnss_*` namespace on first load:
 *
 *   - `sssb_*`  (initial release line)
 *   - `pvnss_*` (interim release line)
 *
 * For each detected prefix the helper sweeps every option, custom-post-type
 * row, post-meta key, and pending cron event, and rewrites them to the
 * current namespace. Guarded per-prefix by `qrnss_migrated_from_<prefix>`
 * options so each path runs at most once per install.
 * ---------------------------------------------------------------------- */
function qrnss_migrate_legacy_data() {
	qrnss_migrate_from_prefix( 'pvnss' );
	qrnss_migrate_from_prefix( 'sssb' );
}
add_action( 'plugins_loaded', 'qrnss_migrate_legacy_data', 1 );

/**
 * Migrate every option / post / post-meta / cron event from one legacy prefix
 * to the current `qrnss_*` namespace. Safe to call multiple times — guarded
 * by the `qrnss_migrated_from_<prefix>` flag option.
 *
 * @param string $src Source prefix (no trailing underscore). e.g. 'pvnss' or 'sssb'.
 */
function qrnss_migrate_from_prefix( $src ) {
	$src = preg_replace( '/[^a-z0-9]/', '', (string) $src );
	if ( '' === $src || 'qrnss' === $src ) {
		return;
	}
	$flag = 'qrnss_migrated_from_' . $src;
	if ( get_option( $flag ) ) {
		return;
	}

	global $wpdb;
	$src_underscore = $src . '_';
	$src_meta       = '_' . $src . '_';

	// 1) Copy every option starting with "{$src}_" to its qrnss_* counterpart.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration.
	$rows = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( $src_underscore ) . '%'
		)
	);
	if ( $rows ) {
		foreach ( $rows as $old_key ) {
			$new_key = 'qrnss_' . substr( $old_key, strlen( $src_underscore ) );
			// Never overwrite a flag we own.
			if ( 0 === strpos( $new_key, 'qrnss_migrated_from_' ) ) {
				continue;
			}
			$value = get_option( $old_key, null );
			if ( null !== $value && false === get_option( $new_key, false ) ) {
				update_option( $new_key, $value );
			}
		}
	}

	// 2) Move `{$src}_campaign` posts to `qrnss_campaign`.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration.
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s",
			'qrnss_campaign',
			$src . '_campaign'
		)
	);

	// 3) Rewrite every `_{$src}_*` post-meta key to `_qrnss_*`.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration.
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} SET meta_key = REPLACE(meta_key, %s, %s) WHERE meta_key LIKE %s",
			$src_meta,
			'_qrnss_',
			$wpdb->esc_like( $src_meta ) . '%'
		)
	);

	// 4) Reschedule pending cron events `{$src}_send_scheduled_campaign` → `qrnss_send_scheduled_campaign`.
	$old_hook = $src . '_send_scheduled_campaign';
	$crons    = _get_cron_array();
	if ( is_array( $crons ) ) {
		foreach ( $crons as $ts => $hooks ) {
			if ( ! isset( $hooks[ $old_hook ] ) ) {
				continue;
			}
			foreach ( $hooks[ $old_hook ] as $sig => $event ) {
				$args = isset( $event['args'] ) ? (array) $event['args'] : array();
				wp_unschedule_event( $ts, $old_hook, $args );
				if ( ! wp_next_scheduled( 'qrnss_send_scheduled_campaign', $args ) ) {
					wp_schedule_single_event( $ts, 'qrnss_send_scheduled_campaign', $args );
				}
			}
		}
	}

	// 5) Flush rewrites so the current CPT registers cleanly.
	if ( function_exists( 'flush_rewrite_rules' ) ) {
		flush_rewrite_rules( false );
	}

	update_option( $flag, QRNSS_VERSION );
}

/**
 * Main Plugin Class
 */
class QRNSS_Core
{

	/**
	 * Instance of the class.
	 *
	 * @var QRNSS_Core
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return QRNSS_Core
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
        require_once QRNSS_PLUGIN_DIR . 'includes/class-sendy-api.php';
        require_once QRNSS_PLUGIN_DIR . 'includes/class-admin-settings.php';
        require_once QRNSS_PLUGIN_DIR . 'includes/class-newsletter-builder.php';
    }

    /**
     * Instantiate classes.
     */
    private function instantiate_classes()
    {
        if (is_admin()) {
            new QRNSS_Admin_Settings();
            new QRNSS_Newsletter_Builder();
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
        add_action('qrnss_send_scheduled_campaign', array($this, 'send_scheduled_campaign'));
        add_action('admin_notices', array($this, 'check_overdue_campaigns'));
        add_action('admin_init', array($this, 'handle_manual_send'));
    }

    /**
     * Register Custom Post Type for Campaigns
     */
    public function register_post_type()
    {
        register_post_type(
            'qrnss_campaign',
            array(
                'labels'      => array(
                    'name'          => __('Campaigns', 'quillrush-newsletter-studio-for-sendy'),
                    'singular_name' => __('Campaign', 'quillrush-newsletter-studio-for-sendy'),
                ),
                'public'      => false,
                'show_ui'     => true, // Show in admin to let user see history/status
                'show_in_menu' => 'quillrush_newsletter',
                'supports'    => array('title', 'custom-fields', 'editor'), // editor can hold HTML content
                'capability_type' => 'post',
                'capabilities' => array(
                    'create_posts' => false, // Only created via code
                ),
                'map_meta_cap' => true,
            )
        );

        // Add custom columns
        add_filter('manage_qrnss_campaign_posts_columns', array($this, 'add_campaign_columns'));
        add_action('manage_qrnss_campaign_posts_custom_column', array($this, 'manage_campaign_columns'), 10, 2);
    }

    public function add_campaign_columns($columns) {
        $columns['qrnss_status'] = __('Status', 'quillrush-newsletter-studio-for-sendy');
        $columns['qrnss_scheduled'] = __('Scheduled For', 'quillrush-newsletter-studio-for-sendy');
        $columns['qrnss_error'] = __('Error', 'quillrush-newsletter-studio-for-sendy');
        return $columns;
    }

    public function manage_campaign_columns($column, $post_id) {
        switch ($column) {
            case 'qrnss_status':
                $status = get_post_meta($post_id, '_qrnss_status', true);
                if (!$status) $status = 'draft';
                
                $color = '#999';
                if ($status === 'sent') $color = '#46b450';
                if ($status === 'scheduled') $color = '#ffb900';
                
                echo '<span style="font-weight:bold; color:' . esc_attr($color) . ';">' . esc_html(ucfirst($status)) . '</span>';
                break;

            case 'qrnss_scheduled':
                $scheduled = get_post_meta($post_id, '_qrnss_scheduled_time', true);
                if ($scheduled) {
                    echo esc_html($scheduled);
                } else {
                    echo '-';
                }
                break;

            case 'qrnss_error':
                $error = get_post_meta($post_id, '_qrnss_send_error', true);
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
        if (get_post_meta($post_id, '_qrnss_status', true) === 'sent') {
            return;
        }

        $campaign_data = array(
            'from_name' => get_post_meta($post_id, '_qrnss_from_name', true),
            'from_email' => get_post_meta($post_id, '_qrnss_from_email', true),
            'reply_to' => get_post_meta($post_id, '_qrnss_from_email', true), // Use from_email as reply_to
            'subject' => get_the_title($post_id),
            'html_text' => get_post_field('post_content', $post_id),
            'plain_text' => get_post_meta($post_id, '_qrnss_plain_text', true),
            'list_ids' => get_post_meta($post_id, '_qrnss_list_id', true),
            'send_campaign' => 1 // Always send when triggered by schedule
        );

        $sendy_api = new QRNSS_Sendy_API();
        $result = $sendy_api->create_campaign($campaign_data);

        if (is_wp_error($result)) {
            // Log error
            update_post_meta($post_id, '_qrnss_send_error', $result->get_error_message());
            update_post_meta($post_id, '_qrnss_status', 'failed');
        } else {
            // Mark as sent
            update_post_meta($post_id, '_qrnss_status', 'sent');
            update_post_meta($post_id, '_qrnss_sent_time', current_time('mysql'));
        }
    }

    /**
     * Check for overdue scheduled campaigns and show admin notice
     */
    public function check_overdue_campaigns()
    {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'qrnss_campaign') === false) {
            return;
        }

        // Check for overdue scheduled campaigns
        $args = array(
            'post_type' => 'qrnss_campaign',
            'post_status' => 'publish',
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary for filtering scheduled campaigns
                array(
                    'key' => '_qrnss_status',
                    'value' => 'scheduled',
                ),
            ),
            'posts_per_page' => -1,
        );

        $campaigns = get_posts($args);
        $overdue = array();

        foreach ($campaigns as $campaign) {
            $scheduled_time = get_post_meta($campaign->ID, '_qrnss_scheduled_time', true);
            if ($scheduled_time && strtotime($scheduled_time) < current_time('timestamp')) {
                $overdue[] = $campaign;
            }
        }

        if (!empty($overdue)) {
            foreach ($overdue as $campaign) {
                // Automatically send the overdue campaign
                $this->send_scheduled_campaign($campaign->ID);
                
                // Check if it was sent successfully
                $status = get_post_meta($campaign->ID, '_qrnss_status', true);
                
                if ($status === 'sent') {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p><strong>Overdue Campaign Sent:</strong> "' . esc_html($campaign->post_title) . '" was automatically sent.</p>';
                    echo '</div>';
                } else {
                    // If it failed, show error with retry button
                    $error = get_post_meta($campaign->ID, '_qrnss_send_error', true);
                    $retry_url = add_query_arg(array(
                        'action' => 'qrnss_retry_send',
                        'campaign_id' => $campaign->ID,
                        'nonce' => wp_create_nonce('qrnss_retry_send_' . $campaign->ID)
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
            'post_type' => 'qrnss_campaign',
            'post_status' => 'publish',
            'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary for filtering failed campaigns
                array(
                    'key' => '_qrnss_status',
                    'value' => 'failed',
                ),
            ),
            'posts_per_page' => -1,
        );

        $failed_campaigns = get_posts($failed_args);

        if (!empty($failed_campaigns)) {
            foreach ($failed_campaigns as $campaign) {
                $error = get_post_meta($campaign->ID, '_qrnss_send_error', true);
                $retry_url = add_query_arg(array(
                    'action' => 'qrnss_retry_send',
                    'campaign_id' => $campaign->ID,
                    'nonce' => wp_create_nonce('qrnss_retry_send_' . $campaign->ID)
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
        
        if ($action !== 'qrnss_manual_send' && $action !== 'qrnss_retry_send') {
            return;
        }

        if (!isset($_GET['campaign_id']) || !isset($_GET['nonce'])) {
            return;
        }

        $campaign_id = intval($_GET['campaign_id']);
        
        $nonce_action = $action === 'qrnss_retry_send' ? 'qrnss_retry_send_' . $campaign_id : 'qrnss_manual_send_' . $campaign_id;
        
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), $nonce_action)) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // Clear previous error if retrying
        if ($action === 'qrnss_retry_send') {
            delete_post_meta($campaign_id, '_qrnss_send_error');
        }

        // Trigger the send
        $this->send_scheduled_campaign($campaign_id);

        // Redirect back
        wp_safe_redirect(admin_url('edit.php?post_type=qrnss_campaign&sent=1'));
        exit;
    }


    /**
     * Load text domain.
     */
    public function load_textdomain()
    {
        // load_plugin_textdomain('quillrush-newsletter-studio-for-sendy', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
            strpos($screen->base, 'quillrush_newsletter') !== false || 
            strpos($screen->base, 'qrnss_newsletter_builder') !== false ||
            'qrnss_campaign' === $screen->post_type
        )) {
            $is_plugin_page = true;
        }

        if (!$is_plugin_page) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('qrnss-admin-style', QRNSS_PLUGIN_URL . 'admin/css/style.css', array(), QRNSS_VERSION);
        wp_enqueue_script('qrnss-admin-script', QRNSS_PLUGIN_URL . 'admin/js/script.js', array('jquery', 'jquery-ui-datepicker'), QRNSS_VERSION, true);

        
        $options = get_option('qrnss_settings');

        // Auto-fetch lists from Sendy (cached). Allows force refresh via ?qrnss_refresh_lists=1.
        // Read-only presence check on the GET flag — no state change happens from the flag alone,
        // it only bypasses the 10-minute cache for the subsequent get_lists() call. The enclosing
        // admin page already requires the manage_options capability, so a nonce is not needed here.
        $known_lists = array();
        if (class_exists('QRNSS_Sendy_API')) {
            $sendy_api  = new QRNSS_Sendy_API();
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only cache-bust flag on an admin-only page; capability check applies upstream.
            $force      = isset($_GET['qrnss_refresh_lists']);
            $known_lists = $sendy_api->get_lists($force);
        }
        
        wp_localize_script('qrnss-admin-script', 'qrnss_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qrnss_newsletter_nonce'),
            'site_url' => home_url(),
            'known_lists'      => $known_lists,
            'remembered_lists' => array_values((array) get_option('qrnss_remembered_lists', array())),
            'settings' => array(
                'footer_logo' => get_option('qrnss_footer_logo_url'),
                'footer_copyright' => get_option('qrnss_footer_copyright', '© {year} ' . get_bloginfo('name')),
                'more_articles_link' => get_option('qrnss_more_articles_link'),
                'social_instagram' => get_option('qrnss_social_instagram'),
                'social_linkedin' => get_option('qrnss_social_linkedin'),
                'social_twitter' => get_option('qrnss_social_twitter'),
                'social_youtube' => get_option('qrnss_social_youtube'),
                'footer_custom_text' => wp_kses_post(nl2br(get_option('qrnss_footer_custom_text'))),
                'show_article_excerpt' => isset($options['show_article_excerpt']) ? $options['show_article_excerpt'] : '',
                // Editorial format texts (HTML allowed; newlines converted to <br>)
                'editorial_greeting'       => wp_kses_post(nl2br(get_option('qrnss_editorial_greeting', ''))),
                'editorial_intro'          => wp_kses_post(nl2br(get_option('qrnss_editorial_intro', ''))),
                'editorial_hero_label'     => wp_kses_post(get_option('qrnss_editorial_hero_label', '')),
                'editorial_grid_heading'   => wp_kses_post(get_option('qrnss_editorial_grid_heading', '')),
                'editorial_why_heading'    => wp_kses_post(get_option('qrnss_editorial_why_heading', '')),
                'editorial_why_body'       => wp_kses_post(nl2br(get_option('qrnss_editorial_why_body', ''))),
                'editorial_collab_heading' => wp_kses_post(get_option('qrnss_editorial_collab_heading', '')),
                'editorial_collab_body'    => wp_kses_post(nl2br(get_option('qrnss_editorial_collab_body', ''))),
                'editorial_about_heading'  => wp_kses_post(get_option('qrnss_editorial_about_heading', '')),
                'editorial_about_body'     => wp_kses_post(nl2br(get_option('qrnss_editorial_about_body', ''))),
            )
        ));
    }
}

// Initialize the plugin.
function qrnss_init()
{
    return QRNSS_Core::get_instance();
}
add_action('plugins_loaded', 'qrnss_init');

// Add Settings + Support on Ko-fi links to plugin action links (next to Deactivate).
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'qrnss_plugin_action_links');
function qrnss_plugin_action_links($links)
{
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=quillrush_newsletter')) . '">' . esc_html__('Settings', 'quillrush-newsletter-studio-for-sendy') . '</a>';
    $kofi_link     = '<a href="https://ko-fi.com/gunjanjaswal" target="_blank" style="color:#0073aa; font-weight:bold;">' . esc_html__('Support on Ko-fi', 'quillrush-newsletter-studio-for-sendy') . '</a>';
    array_unshift($links, $settings_link, $kofi_link);
    return $links;
}

// Plugin row meta: Plugin Support (WP.org forum) + Contact Developer.
add_filter('plugin_row_meta', 'qrnss_plugin_row_meta', 10, 2);
function qrnss_plugin_row_meta($links, $file)
{
    if (plugin_basename(__FILE__) !== $file) {
        return $links;
    }

    $plugin_slug = 'quillrush-newsletter-studio-for-sendy';

    // Strip the auto-injected "Visit plugin site" link (points at the Plugin URI header).
    // WordPress.org-hosted plugins already get a "View details" link auto-injected by core,
    // so we don't add our own to avoid duplicates.
    $plugin_uri = 'https://wordpress.org/plugins/' . $plugin_slug . '/';
    foreach ($links as $i => $link) {
        if (false !== strpos($link, $plugin_uri)) {
            unset($links[ $i ]);
        }
    }
    $links = array_values($links);

    $links[] = '<a href="https://wordpress.org/support/plugin/' . $plugin_slug . '/" target="_blank">' . esc_html__('Plugin Support', 'quillrush-newsletter-studio-for-sendy') . '</a>';
    $links[] = '<a href="mailto:hello@gunjanjaswal.me">' . esc_html__('Contact Developer', 'quillrush-newsletter-studio-for-sendy') . '</a>';

    return $links;
}
