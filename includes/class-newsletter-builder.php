<?php
/**
 * Newsletter Builder Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class QRNSS_Newsletter_Builder
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_submenu'));
        add_action('wp_ajax_qrnss_search_posts', array($this, 'ajax_search_posts'));
        add_action('wp_ajax_qrnss_create_campaign', array($this, 'ajax_create_campaign'));

    }

    public function add_submenu()
    {
        add_submenu_page(
            'quillrush_newsletter',
            __('Create Newsletter', 'quillrush-newsletter-studio-for-sendy'),
            __('Create Newsletter', 'quillrush-newsletter-studio-for-sendy'),
            'manage_options',
            'qrnss_newsletter_builder',
            array($this, 'render_page')
        );
    }

    public function render_page()
    {
        $options = get_option('qrnss_settings');
        $default_from_name = isset($options['from_name']) ? $options['from_name'] : '';
        $default_from_email = isset($options['from_email']) ? $options['from_email'] : '';
        $default_list_id = isset($options['list_id']) ? $options['list_id'] : '';
        ?>
        <div class="wrap qrnss-container">
            <h1>
                <?php esc_html_e('Create Newsletter', 'quillrush-newsletter-studio-for-sendy'); ?>
            </h1>

            <div class="qrnss-flex">
                <!-- Left Column: Controls -->
                <div class="qrnss-col-left">
                    <div class="qrnss-card">
                        <h2><?php esc_html_e('Design Settings', 'quillrush-newsletter-studio-for-sendy'); ?></h2>
                        <p>
                            <label><strong><?php esc_html_e('Newsletter Format', 'quillrush-newsletter-studio-for-sendy'); ?></strong></label><br>
                            <select id="qrnss-format" name="qrnss_format" class="widefat">
                                <option value="custom"><?php esc_html_e('🗞️ The Roundup — hero story + grid layout for your readers', 'quillrush-newsletter-studio-for-sendy'); ?></option>
                                <option value="editorial"><?php esc_html_e('✉️ The Insider Brief — personal pitch with commentary for media & partners', 'quillrush-newsletter-studio-for-sendy'); ?></option>
                            </select>
                            <span class="description" style="display:block; margin-top:5px;">
                                <?php
                                printf(
                                    /* translators: %s: settings page link */
                                    esc_html__('"The Insider Brief" texts (greeting, intro, "Why this matters" etc.) are pulled from %s.', 'quillrush-newsletter-studio-for-sendy'),
                                    '<a href="' . esc_url(admin_url('admin.php?page=quillrush_newsletter')) . '">' . esc_html__('Settings → The Insider Brief Texts', 'quillrush-newsletter-studio-for-sendy') . '</a>'
                                );
                                ?>
                            </span>
                        </p>
                        <p>
                            <label><strong><?php esc_html_e('Banner Image', 'quillrush-newsletter-studio-for-sendy'); ?></strong></label><br>
                            <button class="button" id="qrnss-upload-banner"><?php esc_html_e('Select Banner', 'quillrush-newsletter-studio-for-sendy'); ?></button>
                            <button class="button hidden" id="qrnss-remove-banner" style="display:none; color: #a00; border-color: #a00;"><?php esc_html_e('Remove', 'quillrush-newsletter-studio-for-sendy'); ?></button>
                        <p class="description" style="margin-top: 5px; color: #666; font-style: italic;">
                            <?php esc_html_e('Recommended Size: 600px wide. Keep height under 200px for best results on mobile and desktop.', 'quillrush-newsletter-studio-for-sendy'); ?>
                        </p>
                            <input type="hidden" id="qrnss-banner-url">
                            <div id="qrnss-banner-preview" style="margin-top:10px; max-width:100%;"></div>
                        </p>
                    </div>

                    <div class="qrnss-card">
                        <h2>
                            <?php esc_html_e('Campaign Settings', 'quillrush-newsletter-studio-for-sendy'); ?>
                        </h2>
                        <p>
                            <label>
                                <?php esc_html_e('Subject Line', 'quillrush-newsletter-studio-for-sendy'); ?>
                            </label><br>
                            <input type="text" id="qrnss-subject" class="widefat" placeholder="Newsletter Subject">
                        </p>
                        <p>
                            <label>
                                <?php esc_html_e('From Name', 'quillrush-newsletter-studio-for-sendy'); ?>
                            </label><br>
                            <input type="text" id="qrnss-from-name" class="widefat"
                                value="<?php echo esc_attr($default_from_name); ?>">
                        </p>
                        <p>
                            <label>
                                <?php esc_html_e('From Email', 'quillrush-newsletter-studio-for-sendy'); ?>
                            </label><br>
                            <input type="email" id="qrnss-from-email" class="widefat"
                                value="<?php echo esc_attr($default_from_email); ?>">
                        </p>
                        <p>
                            <label>
                                <?php esc_html_e('Choose your lists & segments', 'quillrush-newsletter-studio-for-sendy'); ?>
                            </label><br>
                            <input type="hidden" id="qrnss-list-id" value="<?php echo esc_attr($default_list_id); ?>">
                            <span id="qrnss-list-empty-notice" class="description" style="display:none;">
                                <?php
                                printf(
                                    /* translators: 1: opening anchor tag pointing at the refresh URL, 2: closing anchor tag */
                                    esc_html__('No lists found. Check your Sendy URL, API Key and Brand ID in Settings, then %1$srefresh%2$s.', 'quillrush-newsletter-studio-for-sendy'),
                                    '<a href="' . esc_url(add_query_arg('qrnss_refresh_lists', '1')) . '">',
                                    '</a>'
                                );
                                ?>
                            </span>
                            <span class="description" style="display:block; margin-top:5px;">
                                <a href="<?php echo esc_url(add_query_arg('qrnss_refresh_lists', '1')); ?>"><?php esc_html_e('Refresh lists from Sendy', 'quillrush-newsletter-studio-for-sendy'); ?></a>
                            </span>
                        </p>
                    </div>

                    <div class="qrnss-card">
                        <h2>
                            <?php esc_html_e('Add Posts', 'quillrush-newsletter-studio-for-sendy'); ?>
                        </h2>
                        <input type="text" id="qrnss-search" class="widefat" placeholder="Search posts...">
                        <div id="qrnss-post-results" class="qrnss-post-list">
                            <!-- Search results -->
                        </div>
                    </div>

                    <div class="qrnss-card">
                        <h2>
                            <?php esc_html_e('Selected Posts', 'quillrush-newsletter-studio-for-sendy'); ?>
                        </h2>
                        <div id="qrnss-selected-list" class="qrnss-selected-posts"></div>
                    </div>

                    <div class="qrnss-card">
                        <h2>
                            <?php esc_html_e('Actions', 'quillrush-newsletter-studio-for-sendy'); ?>
                        </h2>
                        <label><input type="radio" name="qrnss_send_type" value="draft" checked>
                            <?php esc_html_e('Save as Draft in Sendy', 'quillrush-newsletter-studio-for-sendy'); ?>
                        </label><br>
                        <label><input type="radio" name="qrnss_send_type" value="send">
                            <?php esc_html_e('Send Immediately', 'quillrush-newsletter-studio-for-sendy'); ?>
                        </label><br>
                        <label><input type="radio" name="qrnss_send_type" value="schedule">
                            <?php esc_html_e('Schedule', 'quillrush-newsletter-studio-for-sendy'); ?>
                        </label>
                        
                        <div id="qrnss-schedule-options" style="display:none; margin-top: 10px; padding-left: 20px;">
                            <label><?php esc_html_e('Send Date/Time', 'quillrush-newsletter-studio-for-sendy'); ?></label><br>
                            <?php
                            // Set default to current time + 1 hour
                            $default_time = current_time('timestamp') + 3600; // +1 hour
                            $min_time = current_time('timestamp');
                            $default_datetime = wp_date('Y-m-d\TH:i', $default_time);
                            $min_datetime = wp_date('Y-m-d\TH:i', $min_time);
                            ?>
                            <input type="datetime-local" 
                                   id="qrnss-schedule-datetime" 
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
                                echo esc_html(sprintf(__('Current Server Time: %s', 'quillrush-newsletter-studio-for-sendy'), current_time('mysql')));
                                ?>
                                <br>
                                <?php
                                /* translators: %s: Timezone */
                                echo esc_html(sprintf(__('Timezone: %s', 'quillrush-newsletter-studio-for-sendy'), $timezone));
                                ?>
                            </p>
                        </div>
                        
                        <br><br>
                        <button id="qrnss-create-campaign" class="button button-primary large">
                            <?php esc_html_e('Create Campaign', 'quillrush-newsletter-studio-for-sendy'); ?>
                        </button>
                    </div>

                    <div class="qrnss-card">
                        <h3><?php esc_html_e('Support & Contact', 'quillrush-newsletter-studio-for-sendy'); ?></h3>
                        <p style="margin-top:15px;">
                            <strong><?php esc_html_e('Email:', 'quillrush-newsletter-studio-for-sendy'); ?></strong> <a
                                href="mailto:hello@gunjanjaswal.me" style="color: #2271b1 !important; text-decoration: underline;">hello@gunjanjaswal.me</a><br>
                            <strong><?php esc_html_e('Website:', 'quillrush-newsletter-studio-for-sendy'); ?></strong> <a
                                href="https://gunjanjaswal.me" target="_blank" style="color: #2271b1 !important; text-decoration: underline;">gunjanjaswal.me</a>
                        </p>
                    </div>
                </div>

                <!-- Right Column: Preview -->
                <div class="qrnss-col-right">


                    <div class="qrnss-card">
                        <h2>
                            <?php esc_html_e('Email Preview', 'quillrush-newsletter-studio-for-sendy'); ?>
                        </h2>
                        <div id="qrnss-preview-content">
                            <p style="text-align:center; color:#999;">
                                <?php esc_html_e('Add posts to see preview', 'quillrush-newsletter-studio-for-sendy'); ?>
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
        check_ajax_referer('qrnss_newsletter_nonce', 'nonce');

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
        check_ajax_referer('qrnss_newsletter_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        if (!isset($_POST['campaign']) || !is_array($_POST['campaign'])) {
            wp_send_json_error('No campaign data received');
        }

        // Unslash the raw POST array, then sanitize every field individually below.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Per-field sanitization performed below.
        $raw = wp_unslash($_POST['campaign']);
        if (!is_array($raw)) {
            wp_send_json_error('Invalid campaign payload');
        }

        // Build a fully-sanitized campaign record. Nothing from $raw is used downstream.
        $campaign = array(
            'subject'       => isset($raw['subject'])       ? sanitize_text_field((string) $raw['subject'])       : '',
            'from_name'     => isset($raw['from_name'])     ? sanitize_text_field((string) $raw['from_name'])     : '',
            'from_email'    => isset($raw['from_email'])    ? sanitize_email((string) $raw['from_email'])         : '',
            'plain_text'    => isset($raw['plain_text'])    ? sanitize_textarea_field((string) $raw['plain_text']) : '',
            'list_id'       => isset($raw['list_id'])       ? sanitize_text_field((string) $raw['list_id'])       : '',
            'send_type'     => isset($raw['send_type'])     ? sanitize_key((string) $raw['send_type'])            : 'draft',
            'schedule_date' => isset($raw['schedule_date']) ? sanitize_text_field((string) $raw['schedule_date']) : '',
            // html_text is the rendered newsletter body. Run it through an email-safe
            // wp_kses allowlist so any unexpected <script>/<iframe>/<form>/etc. is stripped
            // while preserving the markup an HTML email actually needs (tables, inline styles,
            // images, anchors, headings, lists, etc.).
            'html_text'     => isset($raw['html_text'])     ? self::kses_email_html((string) $raw['html_text'])    : '',
        );

        // Validate required fields.
        if ('' === $campaign['subject']) {
            wp_send_json_error(array('message' => 'Subject line is required.'));
        }
        if ('' === $campaign['from_email']) {
            wp_send_json_error(array('message' => 'A valid From Email is required.'));
        }
        if ('' === $campaign['html_text']) {
            wp_send_json_error(array('message' => 'Newsletter content is empty.'));
        }
        if (!in_array($campaign['send_type'], array('draft', 'send', 'schedule'), true)) {
            $campaign['send_type'] = 'draft';
        }

        // Create Campaign Post
        $post_args = array(
            'post_type'    => 'qrnss_campaign',
            'post_title'   => $campaign['subject'],
            'post_content' => $campaign['html_text'], // Email-kses-sanitized HTML.
            'post_status'  => 'draft',
        );

        $post_id = wp_insert_post($post_args);

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => 'Could not save campaign locally: ' . $post_id->get_error_message()));
        }

        // Save Meta
        update_post_meta($post_id, '_qrnss_from_name',  $campaign['from_name']);
        update_post_meta($post_id, '_qrnss_from_email', $campaign['from_email']);
        update_post_meta($post_id, '_qrnss_plain_text', $campaign['plain_text']);
        update_post_meta($post_id, '_qrnss_list_id',    $campaign['list_id']);

        // Remember which list IDs the user actually selected for this campaign,
        // so they can be pre-checked next time the builder is opened.
        $selected_ids = array_values(array_filter(array_map('trim', explode(',', $campaign['list_id']))));
        if (!empty($selected_ids)) {
            update_option('qrnss_remembered_lists', array_map('sanitize_text_field', $selected_ids));
        }

        $send_type = $campaign['send_type'];

        if ($send_type === 'schedule') {
             $schedule_date = $campaign['schedule_date'];
             $timestamp = strtotime($schedule_date);

             if (!$timestamp || $timestamp <= current_time('timestamp')) {
                 wp_delete_post($post_id, true);
                 wp_send_json_error(array('message' => 'Invalid or past date for scheduling.'));
             }

             wp_schedule_single_event($timestamp, 'qrnss_send_scheduled_campaign', array($post_id));

             update_post_meta($post_id, '_qrnss_status', 'scheduled');
             update_post_meta($post_id, '_qrnss_scheduled_time', $schedule_date);
             wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));

             wp_send_json_success(array('message' => 'Campaign scheduled successfully for ' . $schedule_date));

        } elseif ($send_type === 'send') {

            update_post_meta($post_id, '_qrnss_status', 'sending');

            $api_args = array(
                'from_name'     => $campaign['from_name'],
                'from_email'    => $campaign['from_email'],
                'reply_to'      => $campaign['from_email'],
                'subject'       => $campaign['subject'],
                'html_text'     => $campaign['html_text'],
                'plain_text'    => $campaign['plain_text'],
                'list_ids'      => $campaign['list_id'],
                'send_campaign' => 1,
            );

            $sendy_api = new QRNSS_Sendy_API();
            $result = $sendy_api->create_campaign($api_args);

            if (is_wp_error($result)) {
                wp_delete_post($post_id, true);
                wp_send_json_error(array('message' => $result->get_error_message()));
            } else {
                update_post_meta($post_id, '_qrnss_status', 'sent');
                update_post_meta($post_id, '_qrnss_sent_time', current_time('mysql'));
                wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
                wp_send_json_success(array('message' => 'Campaign created and sent successfully!'));
            }

        } else {
            // Draft
            update_post_meta($post_id, '_qrnss_status', 'draft');

             $api_args = array(
                'from_name'     => $campaign['from_name'],
                'from_email'    => $campaign['from_email'],
                'reply_to'      => $campaign['from_email'],
                'subject'       => $campaign['subject'],
                'html_text'     => $campaign['html_text'],
                'plain_text'    => $campaign['plain_text'],
                'list_ids'      => $campaign['list_id'],
                'send_campaign' => 0,
            );

            $sendy_api = new QRNSS_Sendy_API();
            $result = $sendy_api->create_campaign($api_args);

            if (is_wp_error($result)) {
                 wp_delete_post($post_id, true);
                 wp_send_json_error(array('message' => $result->get_error_message()));
            } else {
                 wp_send_json_success(array('message' => 'Campaign saved as draft in Sendy!'));
            }
        }
    }

    /**
     * Email-safe wp_kses allowlist.
     *
     * Strips <script>, <iframe>, <form>, on* event attributes, javascript: URLs,
     * etc. — while keeping the markup an HTML newsletter actually needs:
     * tables, inline styles, images, anchors, headings, lists, and presentational
     * attributes that classic email clients (Outlook, Apple Mail, Gmail) rely on.
     *
     * Applied to every `html_text` value before it is stored to `post_content`
     * or sent to the Sendy API.
     *
     * @param string $html Raw email HTML.
     * @return string Sanitized email HTML.
     */
    public static function kses_email_html($html)
    {
        $common      = array('style' => true, 'class' => true, 'id' => true, 'align' => true);
        $cell_attrs  = array_merge($common, array(
            'width'    => true,
            'height'   => true,
            'valign'   => true,
            'bgcolor' => true,
            'colspan' => true,
            'rowspan' => true,
        ));
        $allowed = array(
            'html'       => array('lang' => true, 'xmlns' => true),
            'head'       => array(),
            'meta'       => array('charset' => true, 'http-equiv' => true, 'content' => true, 'name' => true),
            'title'      => array(),
            'style'      => array('type' => true),
            'body'       => array_merge($common, array('bgcolor' => true)),
            'table'      => array_merge($common, array(
                'width' => true, 'cellpadding' => true, 'cellspacing' => true,
                'border' => true, 'bgcolor' => true, 'role' => true,
            )),
            'thead'      => $common,
            'tbody'      => $common,
            'tfoot'      => $common,
            'tr'         => array_merge($common, array('bgcolor' => true)),
            'th'         => $cell_attrs,
            'td'         => $cell_attrs,
            'div'        => $common,
            'span'       => $common,
            'p'          => $common,
            'a'          => array_merge($common, array('href' => true, 'target' => true, 'rel' => true, 'title' => true)),
            'img'        => array_merge($common, array(
                'src' => true, 'alt' => true, 'width' => true, 'height' => true,
                'border' => true, 'title' => true, 'srcset' => true, 'sizes' => true,
            )),
            'h1'         => $common,
            'h2'         => $common,
            'h3'         => $common,
            'h4'         => $common,
            'h5'         => $common,
            'h6'         => $common,
            'strong'     => array(),
            'b'          => array(),
            'em'         => array(),
            'i'          => array(),
            'u'          => array(),
            'small'      => array('style' => true),
            'br'         => array(),
            'hr'         => array('style' => true),
            'ul'         => $common,
            'ol'         => $common,
            'li'         => $common,
            'blockquote' => $common,
            'center'     => array(),
            'font'       => array('color' => true, 'face' => true, 'size' => true, 'style' => true),
            'mark'       => array('style' => true),
            'sup'        => array(),
            'sub'        => array(),
        );

        /**
         * Filter the wp_kses allowlist used to sanitize newsletter HTML before
         * storage / transmission to Sendy.
         *
         * @param array $allowed Tag => attribute-allowlist map.
         */
        $allowed = apply_filters('qrnss_email_kses_allowed_html', $allowed);

        return wp_kses($html, $allowed);
    }
}
