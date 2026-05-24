<?php
/**
 * Newsletter Builder Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class PVNSS_Newsletter_Builder
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_submenu'));
        add_action('wp_ajax_pvnss_search_posts', array($this, 'ajax_search_posts'));
        add_action('wp_ajax_pvnss_create_campaign', array($this, 'ajax_create_campaign'));

    }

    public function add_submenu()
    {
        add_submenu_page(
            'postvibe_newsletter',
            __('Create Newsletter', 'postvibe-newsletter-studio-for-sendy'),
            __('Create Newsletter', 'postvibe-newsletter-studio-for-sendy'),
            'manage_options',
            'pvnss_newsletter_builder',
            array($this, 'render_page')
        );
    }

    public function render_page()
    {
        $options = get_option('pvnss_settings');
        $default_from_name = isset($options['from_name']) ? $options['from_name'] : '';
        $default_from_email = isset($options['from_email']) ? $options['from_email'] : '';
        $default_list_id = isset($options['list_id']) ? $options['list_id'] : '';
        ?>
        <div class="wrap pvnss-container">
            <h1>
                <?php esc_html_e('Create Newsletter', 'postvibe-newsletter-studio-for-sendy'); ?>
            </h1>

            <div class="pvnss-flex">
                <!-- Left Column: Controls -->
                <div class="pvnss-col-left">
                    <div class="pvnss-card">
                        <h2><?php esc_html_e('Design Settings', 'postvibe-newsletter-studio-for-sendy'); ?></h2>
                        <p>
                            <label><strong><?php esc_html_e('Newsletter Format', 'postvibe-newsletter-studio-for-sendy'); ?></strong></label><br>
                            <select id="pvnss-format" name="pvnss_format" class="widefat">
                                <option value="custom"><?php esc_html_e('🗞️ The Roundup — hero story + grid layout for your readers', 'postvibe-newsletter-studio-for-sendy'); ?></option>
                                <option value="editorial"><?php esc_html_e('✉️ The Insider Brief — personal pitch with commentary for media & partners', 'postvibe-newsletter-studio-for-sendy'); ?></option>
                            </select>
                            <span class="description" style="display:block; margin-top:5px;">
                                <?php
                                printf(
                                    /* translators: %s: settings page link */
                                    esc_html__('"The Insider Brief" texts (greeting, intro, "Why this matters" etc.) are pulled from %s.', 'postvibe-newsletter-studio-for-sendy'),
                                    '<a href="' . esc_url(admin_url('admin.php?page=postvibe_newsletter')) . '">' . esc_html__('Settings → The Insider Brief Texts', 'postvibe-newsletter-studio-for-sendy') . '</a>'
                                );
                                ?>
                            </span>
                        </p>
                        <p>
                            <label><strong><?php esc_html_e('Banner Image', 'postvibe-newsletter-studio-for-sendy'); ?></strong></label><br>
                            <button class="button" id="pvnss-upload-banner"><?php esc_html_e('Select Banner', 'postvibe-newsletter-studio-for-sendy'); ?></button>
                            <button class="button hidden" id="pvnss-remove-banner" style="display:none; color: #a00; border-color: #a00;"><?php esc_html_e('Remove', 'postvibe-newsletter-studio-for-sendy'); ?></button>
                        <p class="description" style="margin-top: 5px; color: #666; font-style: italic;">
                            <?php esc_html_e('Recommended Size: 600px wide. Keep height under 200px for best results on mobile and desktop.', 'postvibe-newsletter-studio-for-sendy'); ?>
                        </p>
                            <input type="hidden" id="pvnss-banner-url">
                            <div id="pvnss-banner-preview" style="margin-top:10px; max-width:100%;"></div>
                        </p>
                    </div>

                    <div class="pvnss-card">
                        <h2>
                            <?php esc_html_e('Campaign Settings', 'postvibe-newsletter-studio-for-sendy'); ?>
                        </h2>
                        <p>
                            <label>
                                <?php esc_html_e('Subject Line', 'postvibe-newsletter-studio-for-sendy'); ?>
                            </label><br>
                            <input type="text" id="pvnss-subject" class="widefat" placeholder="Newsletter Subject">
                        </p>
                        <p>
                            <label>
                                <?php esc_html_e('From Name', 'postvibe-newsletter-studio-for-sendy'); ?>
                            </label><br>
                            <input type="text" id="pvnss-from-name" class="widefat"
                                value="<?php echo esc_attr($default_from_name); ?>">
                        </p>
                        <p>
                            <label>
                                <?php esc_html_e('From Email', 'postvibe-newsletter-studio-for-sendy'); ?>
                            </label><br>
                            <input type="email" id="pvnss-from-email" class="widefat"
                                value="<?php echo esc_attr($default_from_email); ?>">
                        </p>
                        <p>
                            <label>
                                <?php esc_html_e('Choose your lists & segments', 'postvibe-newsletter-studio-for-sendy'); ?>
                            </label><br>
                            <input type="hidden" id="pvnss-list-id" value="<?php echo esc_attr($default_list_id); ?>">
                            <span id="pvnss-list-empty-notice" class="description" style="display:none;">
                                <?php
                                printf(
                                    /* translators: 1: opening anchor tag pointing at the refresh URL, 2: closing anchor tag */
                                    esc_html__('No lists found. Check your Sendy URL, API Key and Brand ID in Settings, then %1$srefresh%2$s.', 'postvibe-newsletter-studio-for-sendy'),
                                    '<a href="' . esc_url(add_query_arg('pvnss_refresh_lists', '1')) . '">',
                                    '</a>'
                                );
                                ?>
                            </span>
                            <span class="description" style="display:block; margin-top:5px;">
                                <a href="<?php echo esc_url(add_query_arg('pvnss_refresh_lists', '1')); ?>"><?php esc_html_e('Refresh lists from Sendy', 'postvibe-newsletter-studio-for-sendy'); ?></a>
                            </span>
                        </p>
                    </div>

                    <div class="pvnss-card">
                        <h2>
                            <?php esc_html_e('Add Posts', 'postvibe-newsletter-studio-for-sendy'); ?>
                        </h2>
                        <input type="text" id="pvnss-search" class="widefat" placeholder="Search posts...">
                        <div id="pvnss-post-results" class="pvnss-post-list">
                            <!-- Search results -->
                        </div>
                    </div>

                    <div class="pvnss-card">
                        <h2>
                            <?php esc_html_e('Selected Posts', 'postvibe-newsletter-studio-for-sendy'); ?>
                        </h2>
                        <div id="pvnss-selected-list" class="pvnss-selected-posts"></div>
                    </div>

                    <div class="pvnss-card">
                        <h2>
                            <?php esc_html_e('Actions', 'postvibe-newsletter-studio-for-sendy'); ?>
                        </h2>
                        <label><input type="radio" name="pvnss_send_type" value="draft" checked>
                            <?php esc_html_e('Save as Draft in Sendy', 'postvibe-newsletter-studio-for-sendy'); ?>
                        </label><br>
                        <label><input type="radio" name="pvnss_send_type" value="send">
                            <?php esc_html_e('Send Immediately', 'postvibe-newsletter-studio-for-sendy'); ?>
                        </label><br>
                        <label><input type="radio" name="pvnss_send_type" value="schedule">
                            <?php esc_html_e('Schedule', 'postvibe-newsletter-studio-for-sendy'); ?>
                        </label>
                        
                        <div id="pvnss-schedule-options" style="display:none; margin-top: 10px; padding-left: 20px;">
                            <label><?php esc_html_e('Send Date/Time', 'postvibe-newsletter-studio-for-sendy'); ?></label><br>
                            <?php
                            // Set default to current time + 1 hour
                            $default_time = current_time('timestamp') + 3600; // +1 hour
                            $min_time = current_time('timestamp');
                            $default_datetime = wp_date('Y-m-d\TH:i', $default_time);
                            $min_datetime = wp_date('Y-m-d\TH:i', $min_time);
                            ?>
                            <input type="datetime-local" 
                                   id="pvnss-schedule-datetime" 
                                   class="regular-text"
                                   value="<?php echo esc_attr($default_datetime); ?>"
                                   min="<?php echo esc_attr($min_datetime); ?>"
                                   style="position: relative; z-index: 999999;">
                            
                            <?php
                            $timezone = get_option('timezone_string');
                            if (!$timezone) {
                                $timezone = 'UTC ' . get_option('gmt_offset');
                            }
                            ?>
                            <p class="description">
                                <?php
                                /* translators: %s: Current server time */
                                echo esc_html(sprintf(__('Current Server Time: %s', 'postvibe-newsletter-studio-for-sendy'), current_time('mysql')));
                                ?>
                                <br>
                                <?php
                                /* translators: %s: Timezone */
                                echo esc_html(sprintf(__('Timezone: %s', 'postvibe-newsletter-studio-for-sendy'), $timezone));
                                ?>
                            </p>
                        </div>
                        
                        <br><br>
                        <button id="pvnss-create-campaign" class="button button-primary large">
                            <?php esc_html_e('Create Campaign', 'postvibe-newsletter-studio-for-sendy'); ?>
                        </button>
                    </div>

                    <div class="pvnss-card">
                        <h3><?php esc_html_e('Support & Contact', 'postvibe-newsletter-studio-for-sendy'); ?></h3>
                        <p style="margin-top:15px;">
                            <strong><?php esc_html_e('Email:', 'postvibe-newsletter-studio-for-sendy'); ?></strong> <a
                                href="mailto:hello@gunjanjaswal.me" style="color: #2271b1 !important; text-decoration: underline;">hello@gunjanjaswal.me</a><br>
                            <strong><?php esc_html_e('Website:', 'postvibe-newsletter-studio-for-sendy'); ?></strong> <a
                                href="https://gunjanjaswal.me" target="_blank" style="color: #2271b1 !important; text-decoration: underline;">gunjanjaswal.me</a>
                        </p>
                    </div>
                </div>

                <!-- Right Column: Preview -->
                <div class="pvnss-col-right">


                    <div class="pvnss-card">
                        <h2>
                            <?php esc_html_e('Email Preview', 'postvibe-newsletter-studio-for-sendy'); ?>
                        </h2>
                        <div id="pvnss-preview-content">
                            <p style="text-align:center; color:#999;">
                                <?php esc_html_e('Add posts to see preview', 'postvibe-newsletter-studio-for-sendy'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_search_posts()
    {
        check_ajax_referer('pvnss_newsletter_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $query    = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';
        $page     = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;
        $per_page = 10;

        $args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
        );

        if (!empty($query)) {
            $args['s'] = $query;
        }

        $wp_query = new WP_Query($args);
        $data = array();

        foreach ($wp_query->posts as $post) {
            $thumb_id  = get_post_thumbnail_id($post->ID);
            $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'large') : '';

            $data[] = array(
                'id'        => $post->ID,
                'title'     => get_the_title($post->ID),
                'thumbnail' => $thumb_url,
                'excerpt'   => wp_trim_words($post->post_excerpt ? $post->post_excerpt : strip_shortcodes($post->post_content), 20),
                'link'      => get_permalink($post->ID),
            );
        }

        wp_send_json_success(array(
            'posts'    => $data,
            'page'     => $page,
            'has_more' => $page < (int) $wp_query->max_num_pages,
        ));
    }

    public function ajax_create_campaign()
    {
        check_ajax_referer('pvnss_newsletter_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (!isset($_POST['campaign'])) {
            wp_send_json_error('No campaign data received');
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $campaign_data = wp_unslash($_POST['campaign']);

        // Create Campaign Post
        $post_args = array(
            'post_type'    => 'pvnss_campaign',
            'post_title'   => sanitize_text_field($campaign_data['subject']),
            'post_content' => $campaign_data['html_text'], // Save full HTML
            'post_status'  => 'draft',
        );

        $post_id = wp_insert_post($post_args);

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => 'Could not save campaign locally: ' . $post_id->get_error_message()));
        }

        // Save Meta
        update_post_meta($post_id, '_pvnss_from_name', sanitize_text_field($campaign_data['from_name']));
        update_post_meta($post_id, '_pvnss_from_email', sanitize_email($campaign_data['from_email']));
        update_post_meta($post_id, '_pvnss_plain_text', sanitize_textarea_field($campaign_data['plain_text']));
        update_post_meta($post_id, '_pvnss_list_id', sanitize_text_field($campaign_data['list_id']));

        // Remember which list IDs the user actually selected for this campaign,
        // so they can be pre-checked next time the builder is opened.
        $selected_ids = array_values(array_filter(array_map('trim', explode(',', (string) $campaign_data['list_id']))));
        if (!empty($selected_ids)) {
            update_option('pvnss_remembered_lists', array_map('sanitize_text_field', $selected_ids));
        }

        $send_type = $campaign_data['send_type'];

        if ($send_type === 'schedule') {
             $schedule_date = sanitize_text_field($campaign_data['schedule_date']);
             $timestamp = strtotime($schedule_date);

             if (!$timestamp || $timestamp <= current_time('timestamp')) {
                 wp_delete_post($post_id, true);
                 wp_send_json_error(array('message' => 'Invalid or past date for scheduling.'));
             }

             wp_schedule_single_event($timestamp, 'pvnss_send_scheduled_campaign', array($post_id));
             
             update_post_meta($post_id, '_pvnss_status', 'scheduled');
             update_post_meta($post_id, '_pvnss_scheduled_time', $schedule_date);
             wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
             
             wp_send_json_success(array('message' => 'Campaign scheduled successfully for ' . $schedule_date));

        } elseif ($send_type === 'send') {
            
            update_post_meta($post_id, '_pvnss_status', 'sending');

            $api_args = array(
                'from_name' => sanitize_text_field($campaign_data['from_name']),
                'from_email' => sanitize_email($campaign_data['from_email']),
                'reply_to' => sanitize_email($campaign_data['from_email']),
                'subject' => sanitize_text_field($campaign_data['subject']),
                'html_text' => $campaign_data['html_text'], // Full HTML required for email
                'plain_text' => sanitize_textarea_field($campaign_data['plain_text']),
                'list_ids' => sanitize_text_field($campaign_data['list_id']),
                'send_campaign' => 1
            );
    
            $sendy_api = new PVNSS_Sendy_API();
            $result = $sendy_api->create_campaign($api_args);
    
            if (is_wp_error($result)) {
                wp_delete_post($post_id, true);
                wp_send_json_error(array('message' => $result->get_error_message()));
            } else {
                update_post_meta($post_id, '_pvnss_status', 'sent');
                update_post_meta($post_id, '_pvnss_sent_time', current_time('mysql'));
                wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
                wp_send_json_success(array('message' => 'Campaign created and sent successfully!'));
            }

        } else {
            // Draft
            update_post_meta($post_id, '_pvnss_status', 'draft');
            
             $api_args = array(
                'from_name' => sanitize_text_field($campaign_data['from_name']),
                'from_email' => sanitize_email($campaign_data['from_email']),
                'reply_to' => sanitize_email($campaign_data['from_email']),
                'subject' => sanitize_text_field($campaign_data['subject']),
                'html_text' => $campaign_data['html_text'], // Full HTML required for email
                'plain_text' => sanitize_textarea_field($campaign_data['plain_text']),
                'list_ids' => sanitize_text_field($campaign_data['list_id']),
                'send_campaign' => 0
            );

            $sendy_api = new PVNSS_Sendy_API();
            $result = $sendy_api->create_campaign($api_args);

            if (is_wp_error($result)) {
                 wp_delete_post($post_id, true);
                 wp_send_json_error(array('message' => $result->get_error_message()));
            } else {
                 wp_send_json_success(array('message' => 'Campaign saved as draft in Sendy!'));
            }
        }
    }


}
