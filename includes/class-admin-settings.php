<?php
/**
 * Admin Settings Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class QRNSS_Admin_Settings
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu()
    {
        add_menu_page(
            __('Quillrush Newsletter Studio for Sendy', 'quillrush-newsletter-studio-for-sendy'),
            __('Quillrush Newsletter', 'quillrush-newsletter-studio-for-sendy'),
            'manage_options',
            'quillrush_newsletter',
            array($this, 'settings_page_html'),
            'dashicons-email',
            60
        );

        add_submenu_page(
            'quillrush_newsletter',
            __('Settings', 'quillrush-newsletter-studio-for-sendy'),
            __('Settings', 'quillrush-newsletter-studio-for-sendy'),
            'manage_options',
            'quillrush_newsletter'
        );
    }

    public function register_settings()
    {
        register_setting('qrnss_settings_group', 'qrnss_settings', array($this, 'sanitize_settings'));

        add_settings_section(
            'qrnss_main_section',
            __('Sendy Connection Settings', 'quillrush-newsletter-studio-for-sendy'),
            null,
            'quillrush_newsletter'
        );

        add_settings_field(
            'installation_url',
            __('Sendy Installation URL', 'quillrush-newsletter-studio-for-sendy'),
            array($this, 'render_text_field'),
            'quillrush_newsletter',
            'qrnss_main_section',
            array('field' => 'installation_url', 'desc' => 'E.g. https://sendy.yourdomain.com/')
        );

        add_settings_field(
            'api_key',
            __('API Key', 'quillrush-newsletter-studio-for-sendy'),
            array($this, 'render_text_field'),
            'quillrush_newsletter',
            'qrnss_main_section',
            array('field' => 'api_key', 'type' => 'password')
        );

        add_settings_field(
            'brand_id',
            __('Brand ID (Optional)', 'quillrush-newsletter-studio-for-sendy'),
            array($this, 'render_text_field'),
            'quillrush_newsletter',
            'qrnss_main_section',
            array(
                'field' => 'brand_id',
                'desc' => __('Found in Sendy Settings > Your Brand > ID (Required for some Sendy versions)', 'quillrush-newsletter-studio-for-sendy')
            )
        );

        add_settings_field(
            'from_name',
            __('Default From Name', 'quillrush-newsletter-studio-for-sendy'),
            array($this, 'render_text_field'),
            'quillrush_newsletter',
            'qrnss_main_section',
            array('field' => 'from_name')
        );

        add_settings_field(
            'from_email',
            __('Default From Email', 'quillrush-newsletter-studio-for-sendy'),
            array($this, 'render_text_field'),
            'quillrush_newsletter',
            'qrnss_main_section',
            array('field' => 'from_email')
        );

        add_settings_field(
            'trigger_cron',
            __('Auto-Trigger Cron', 'quillrush-newsletter-studio-for-sendy'),
            array($this, 'render_checkbox_field'),
            'quillrush_newsletter',
            'qrnss_main_section',
            array(
                'field' => 'trigger_cron',
                'desc' => __('Automatically trigger Sendy\'s <code>scheduled.php</code> after sending a campaign? Useful if you don\'t have a cron job set up on your server.<br><em>(Will append ?i=BRAND_ID if Brand ID is set)</em>', 'quillrush-newsletter-studio-for-sendy')
            )
        );

        add_settings_field(
            'show_article_excerpt',
            __('Show Article Excerpt', 'quillrush-newsletter-studio-for-sendy'),
            array($this, 'render_checkbox_field'),
            'quillrush_newsletter',
            'qrnss_main_section',
            array(
                'field' => 'show_article_excerpt',
                'desc' => __('Show a short excerpt of the article between the title and the "Read More" button in the newsletter.', 'quillrush-newsletter-studio-for-sendy')
            )
        );

        // --- Footer & Social Settings ---
        
        add_settings_section(
            'qrnss_footer_section',
            __('Footer & Social Settings', 'quillrush-newsletter-studio-for-sendy'),
            null,
            'quillrush_newsletter'
        );

        // Register individual options for footer settings
        register_setting('qrnss_settings_group', 'qrnss_footer_logo_url', 'esc_url_raw');
        register_setting('qrnss_settings_group', 'qrnss_footer_copyright', 'sanitize_text_field');
        register_setting('qrnss_settings_group', 'qrnss_more_articles_link', 'esc_url_raw');
        register_setting('qrnss_settings_group', 'qrnss_social_instagram', 'esc_url_raw');
        register_setting('qrnss_settings_group', 'qrnss_social_linkedin', 'esc_url_raw');
        register_setting('qrnss_settings_group', 'qrnss_social_twitter', 'esc_url_raw');
        register_setting('qrnss_settings_group', 'qrnss_social_youtube', 'esc_url_raw');
        register_setting('qrnss_settings_group', 'qrnss_footer_custom_text', 'wp_kses_post'); // Allow HTML

        add_settings_field('qrnss_footer_logo_url', __('Footer Logo URL', 'quillrush-newsletter-studio-for-sendy'), array($this, 'render_footer_field'), 'quillrush_newsletter', 'qrnss_footer_section', array('field' => 'qrnss_footer_logo_url'));
        add_settings_field('qrnss_footer_copyright', __('Copyright Text', 'quillrush-newsletter-studio-for-sendy'), array($this, 'render_footer_field'), 'quillrush_newsletter', 'qrnss_footer_section', array('field' => 'qrnss_footer_copyright'));
        add_settings_field('qrnss_footer_custom_text', __('Custom Footer Text', 'quillrush-newsletter-studio-for-sendy'), array($this, 'render_textarea_footer_field'), 'quillrush_newsletter', 'qrnss_footer_section', array('field' => 'qrnss_footer_custom_text', 'desc' => __('Add custom text before the subscription message. HTML allowed (e.g. &lt;br&gt;, &lt;a href="..."&gt;Link&lt;/a&gt;). Newlines are automatically converted to line breaks.', 'quillrush-newsletter-studio-for-sendy')));
        add_settings_field('qrnss_more_articles_link', __('"Read More Articles" Link', 'quillrush-newsletter-studio-for-sendy'), array($this, 'render_footer_field'), 'quillrush_newsletter', 'qrnss_footer_section', array('field' => 'qrnss_more_articles_link'));
        
        add_settings_field('qrnss_social_instagram', __('Instagram URL', 'quillrush-newsletter-studio-for-sendy'), array($this, 'render_footer_field'), 'quillrush_newsletter', 'qrnss_footer_section', array('field' => 'qrnss_social_instagram'));
        add_settings_field('qrnss_social_linkedin', __('LinkedIn URL', 'quillrush-newsletter-studio-for-sendy'), array($this, 'render_footer_field'), 'quillrush_newsletter', 'qrnss_footer_section', array('field' => 'qrnss_social_linkedin'));
        add_settings_field('qrnss_social_twitter', __('X (Twitter) URL', 'quillrush-newsletter-studio-for-sendy'), array($this, 'render_footer_field'), 'quillrush_newsletter', 'qrnss_footer_section', array('field' => 'qrnss_social_twitter'));
        add_settings_field('qrnss_social_youtube', __('YouTube URL', 'quillrush-newsletter-studio-for-sendy'), array($this, 'render_footer_field'), 'quillrush_newsletter', 'qrnss_footer_section', array('field' => 'qrnss_social_youtube'));

        // --- Editorial Format Texts ---

        add_settings_section(
            'qrnss_editorial_section',
            __('✉️ The Insider Brief — Template Texts', 'quillrush-newsletter-studio-for-sendy'),
            function () {
                echo '<p>' . esc_html__('These texts power "The Insider Brief" newsletter format — a personal pitch with commentary for media & partners. Choose it on the Create Newsletter page. HTML is allowed.', 'quillrush-newsletter-studio-for-sendy') . '</p>';
            },
            'quillrush_newsletter'
        );

        $editorial_fields = array(
            'qrnss_editorial_greeting'        => array('label' => __('Greeting', 'quillrush-newsletter-studio-for-sendy'),               'type' => 'text',     'desc' => __('e.g. "Hi [First Name],"', 'quillrush-newsletter-studio-for-sendy')),
            'qrnss_editorial_intro'           => array('label' => __('Intro Paragraph', 'quillrush-newsletter-studio-for-sendy'),         'type' => 'textarea', 'desc' => __('Lead paragraph shown above the hero story.', 'quillrush-newsletter-studio-for-sendy')),
            'qrnss_editorial_hero_label'      => array('label' => __('Hero Section Label', 'quillrush-newsletter-studio-for-sendy'),       'type' => 'text',     'desc' => __('Small label above the hero post (e.g. "Hero Story").', 'quillrush-newsletter-studio-for-sendy')),
            'qrnss_editorial_grid_heading'    => array('label' => __('Grid Section Heading', 'quillrush-newsletter-studio-for-sendy'),     'type' => 'text',     'desc' => __('e.g. "🔍 What Else We\'re Seeing"', 'quillrush-newsletter-studio-for-sendy')),
            'qrnss_editorial_why_heading'     => array('label' => __('"Why This Matters" Heading', 'quillrush-newsletter-studio-for-sendy'),'type' => 'text',     'desc' => ''),
            'qrnss_editorial_why_body'        => array('label' => __('"Why This Matters" Body', 'quillrush-newsletter-studio-for-sendy'),  'type' => 'textarea', 'desc' => ''),
            'qrnss_editorial_collab_heading'  => array('label' => __('Collaboration Heading', 'quillrush-newsletter-studio-for-sendy'),     'type' => 'text',     'desc' => __('e.g. "📩 For Media & Collaborations"', 'quillrush-newsletter-studio-for-sendy')),
            'qrnss_editorial_collab_body'     => array('label' => __('Collaboration Body', 'quillrush-newsletter-studio-for-sendy'),       'type' => 'textarea', 'desc' => __('HTML allowed. Use this for CTA bullets and contact info.', 'quillrush-newsletter-studio-for-sendy')),
            'qrnss_editorial_about_heading'   => array('label' => __('About Us Heading', 'quillrush-newsletter-studio-for-sendy'),         'type' => 'text',     'desc' => ''),
            'qrnss_editorial_about_body'      => array('label' => __('About Us Body', 'quillrush-newsletter-studio-for-sendy'),            'type' => 'textarea', 'desc' => ''),
        );

        foreach ($editorial_fields as $key => $cfg) {
            register_setting('qrnss_settings_group', $key, 'wp_kses_post');
            add_settings_field(
                $key,
                $cfg['label'],
                array($this, 'render_editorial_field'),
                'quillrush_newsletter',
                'qrnss_editorial_section',
                array('field' => $key, 'type' => $cfg['type'], 'desc' => $cfg['desc'])
            );
        }
    }

    public function render_editorial_field($args)
    {
        $field = $args['field'];
        $type  = isset($args['type']) ? $args['type'] : 'text';
        $desc  = isset($args['desc']) ? $args['desc'] : '';
        $value = get_option($field, '');

        if ($type === 'textarea') {
            echo '<textarea name="' . esc_attr($field) . '" rows="4" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
        } else {
            echo '<input type="text" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '" class="regular-text">';
        }
        if ($desc) {
            echo '<p class="description">' . wp_kses_post($desc) . '</p>';
        }
    }

    public function render_footer_field($args) {
        $option = get_option($args['field']);
        echo '<input type="text" name="' . esc_attr($args['field']) . '" value="' . esc_attr($option) . '" class="regular-text">';
    }

    public function render_textarea_footer_field($args) {
        $option = get_option($args['field']);
        $desc = isset($args['desc']) ? $args['desc'] : '';
        echo '<textarea name="' . esc_attr($args['field']) . '" rows="4" cols="50" class="large-text">' . esc_textarea($option) . '</textarea>';
        if ($desc) {
            echo '<p class="description">' . wp_kses_post($desc) . '</p>';
        }
    }

    public function sanitize_settings($input)
    {
        $new_input = array();
        if (isset($input['installation_url'])) {
            $new_input['installation_url'] = esc_url_raw($input['installation_url']);
        }
        if (isset($input['api_key'])) {
            $new_input['api_key'] = sanitize_text_field($input['api_key']);
        }
        if (isset($input['brand_id'])) {
            $new_input['brand_id'] = sanitize_text_field($input['brand_id']);
        }
        if (isset($input['from_name'])) {
            $new_input['from_name'] = sanitize_text_field($input['from_name']);
        }
        if (isset($input['from_email'])) {
            $new_input['from_email'] = sanitize_email($input['from_email']);
        }
if (isset($input['trigger_cron'])) {
            $new_input['trigger_cron'] = '1';
        } else {
            $new_input['trigger_cron'] = '';
        }
        
        if (isset($input['show_article_excerpt'])) {
            $new_input['show_article_excerpt'] = '1';
        } else {
            $new_input['show_article_excerpt'] = '';
        }
        
        return $new_input;
    }

    public function render_text_field($args)
    {
        $options = get_option('qrnss_settings');
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        $type = isset($args['type']) ? $args['type'] : 'text';
        $desc = isset($args['desc']) ? $args['desc'] : '';
        ?>
        <input type="<?php echo esc_attr($type); ?>" name="qrnss_settings[<?php echo esc_attr($field); ?>]"
            value="<?php echo esc_attr($value); ?>" class="regular-text">
        <?php if ($desc): ?>
            <p class="description">
                <?php echo wp_kses_post($desc); ?>
            </p>
        <?php endif; ?>
    <?php
    }

    public function render_checkbox_field($args)
    {
        $options = get_option('qrnss_settings');
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        $desc = isset($args['desc']) ? $args['desc'] : '';
        ?>
        <label>
            <input type="checkbox" name="qrnss_settings[<?php echo esc_attr($field); ?>]" value="1" <?php checked('1', $value); ?>>
            <?php if ($desc): ?>
                <span class="description">
                    <?php echo wp_kses_post($desc); ?>
                </span>
            <?php endif; ?>
        </label>
        <?php
    }

    public function render_textarea_field($args)
    {
        $options = get_option('qrnss_settings');
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        $desc = isset($args['desc']) ? $args['desc'] : '';
        ?>
        <textarea name="qrnss_settings[<?php echo esc_attr($field); ?>]" rows="5" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <?php if ($desc): ?>
            <p class="description">
                <?php echo wp_kses_post($desc); ?>
            </p>
        <?php endif; ?>
    <?php
    }

    public function settings_page_html()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?>
            </h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('qrnss_settings_group');
                do_settings_sections('quillrush_newsletter');
                submit_button('Save Settings');
                ?>
            </form>
            <?php $this->render_support_box(); ?>
        </div>
        <?php
    }

    /**
     * Render Support / Ko-fi / Plugin Support / Contact Developer box.
     * Shown on the settings page and the Create Newsletter screen.
     */
    public function render_support_box()
    {
        ?>
        <div class="qrnss-support-box" style="margin-top:30px; padding:20px 24px; background:linear-gradient(180deg,#fffafa 0%,#fff5f7 100%); border-left:4px solid #FF5E5B; border-radius:6px; box-shadow:0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin:0 0 8px; display:flex; align-items:center; gap:8px;">
                <span class="dashicons dashicons-heart" style="color:#FF5E5B;"></span>
                <?php esc_html_e('Support the developer', 'quillrush-newsletter-studio-for-sendy'); ?>
            </h2>
            <p style="margin:0 0 14px; color:#555;">
                <?php esc_html_e('Quillrush Newsletter Studio for Sendy saving you time? Back development on Ko-fi.', 'quillrush-newsletter-studio-for-sendy'); ?>
            </p>
            <p style="margin:0; display:flex; flex-wrap:wrap; gap:10px;">
                <a href="https://ko-fi.com/gunjanjaswal" target="_blank" rel="noopener noreferrer" class="button button-primary button-large" style="background:#FF5E5B; border-color:#e04643; box-shadow:0 1px 0 #e04643; text-shadow:none; display:inline-flex; align-items:center; gap:6px;">
                    <span class="dashicons dashicons-coffee" style="line-height:1;"></span>
                    <?php esc_html_e('Support on Ko-fi', 'quillrush-newsletter-studio-for-sendy'); ?>
                </a>
                <a href="https://wordpress.org/support/plugin/quillrush-newsletter-studio-for-sendy/" target="_blank" rel="noopener noreferrer" class="button button-secondary button-large" style="display:inline-flex; align-items:center; gap:6px;">
                    <span class="dashicons dashicons-sos" style="line-height:1;"></span>
                    <?php esc_html_e('Plugin Support Forum', 'quillrush-newsletter-studio-for-sendy'); ?>
                </a>
                <a href="mailto:hello@gunjanjaswal.me" class="button button-secondary button-large" style="display:inline-flex; align-items:center; gap:6px;">
                    <span class="dashicons dashicons-email-alt" style="line-height:1;"></span>
                    <?php esc_html_e('Contact Developer', 'quillrush-newsletter-studio-for-sendy'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
