<?php

/**
 * Plugin Name: AI Community Discussions
 * Description: Demonstrates AI integration workflow for a custom post type. Adds an "AI Summary" meta box that simulates generating a summary, stores it as post meta, shows it on the front‑end, and provides a settings page for summary length.
 * Version: 1.0.0
 * Author: Marwan Shokry
 * License: GPLv2 or later
 * Text Domain: aicd
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


class AICommunityDiscussions
{

    const postType = 'ai_comm_discussions';
    const metaKeySummary = '_ai_community_discussions_summary';
    const optionSettingsKey = 'ai_community_discussions_settings';
    const nonceAction = 'ai_community_discussions_generate_summary_nonce';
    const nonceName = 'ai_community_discussions_nonce';

    public function __construct()
    {
        add_action('init', [$this, 'registerPostType']);
        // Meta box and saving
        add_action('add_meta_boxes', [$this, 'addSummaryMetaBox']);
        add_action('save_post', [$this, 'saveSummaryMeta'], 10, 2);
        add_action('the_content', [$this, 'addSummaryToContent']);
        // Settings page
        add_action('admin_menu', [$this, 'AiCommunityDiscussionsAddSettingsPage']);
        add_action('admin_init', [$this, 'aiCommunityDiscussionsSettingsInit']);
        add_action('admin_enqueue_scripts', [$this, 'adminAssets']);
        // Frontend
        add_action('wp_ajax_ai_community_discussions_generate_summary', [$this, 'generateSummary']);


        register_activation_hook(__FILE__, [$this, 'activate']);

    }

    /**
     * Plugin activation hook. Sets default settings if not present.
     *
     * @return void
     */
    public static function activate()
    {
        $defaultSettings = [
            'summary_length' => 40,
        ];
        if (!get_option(self::optionSettingsKey)) {
            update_option(self::optionSettingsKey, $defaultSettings);
        }
    }

    /**
     * Registers the custom post type for community discussions.
     *
     * @return void
     */
    public function registerPostType()
    {
        $labels = [
            'name' => __('Community Discussions', 'aicd'),
            'singular_name' => __('Community Discussion', 'aicd'),
            'menu_name' => __('Community Discussions', 'aicd'),
            'add_new' => __('Add New', 'aicd'),
            'add_new_item' => __('Add New Discussion', 'aicd'),
            'edit_item' => __('Edit Discussion', 'aicd'),
            'new_item' => __('New Discussion', 'aicd'),
            'view_item' => __('View Discussion', 'aicd'),
        ];
        $args = [
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'capability_type' => 'post',
            'supports' => ['title', 'editor', 'author', 'excerpt', 'thumbnail', 'comments', 'revisions'],
            'rewrite' => ['slug' => 'ai-community-discussions'],
            'menu_icon' => 'dashicons-format-chat',
            'menu_position' => 5,
            'show_in_rest' => true,
            'rest_base' => 'ai-community-discussions-api'
        ];
        register_post_type(self::postType, $args);
    }

    /**
     * Adds the AI Summary meta box to the custom post type edit screen.
     *
     * @return void
     */
    public function addSummaryMetaBox()
    {
        add_meta_box(
            'aicd_summary_box',
            __('AI Summary', 'aicd'),
            [$this, 'renderSummaryMetaBox'],
            self::postType,
            'side',
            'default'
        );
    }

    /**
     * Renders the content of the AI Summary meta box.
     *
     * @return void
     */
    public function renderSummaryMetaBox()
    {
        $summary = get_post_meta(get_the_ID(), self::metaKeySummary, true);
        $summary = is_string($summary) ? $summary : ''; // Ensure $summary is a string to avoid type errors
        $nonce = wp_create_nonce(self::nonceAction);
        echo '<div id="cdai-summary-box">';
        echo '<p><button type="button" class="button" id="aicd-generate-summary">' . esc_html__('Generate Summary', 'aicd') . '</button></p>';
        echo '<p><textarea id="aicd-summary-field" name="aicd-summary-field" rows="3" class="widefat">' . esc_textarea($summary) . '</textarea></p>';
        echo '<input type="hidden" id="aicd-post-id" name="aicd-post-id" value="' . esc_attr(get_the_ID()) . '">';
        echo '<input type="hidden" id="aicd-nonce" name="aicd-nonce" value="' . esc_attr($nonce) . '">';
        echo '<p class="description">' . esc_html__('You can generate a summary for this post using the button above.', 'aicd') . '</p>';
        echo '</div>';
        ?>
        <script>
            var AICD = AICD || {};
            AICD.ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        </script>
        <?php

    }

    /**
     * Saves the AI summary meta field when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     * @return void
     */
    public function saveSummaryMeta($post_id)
    {
        $post_type = get_post_type($post_id);
        if ($post_type !== self::postType) {
            return;
        }
        if (!isset($_POST['aicd-nonce']) || !wp_verify_nonce($_POST['aicd-nonce'], self::nonceAction)) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $summary = isset($_POST['aicd-summary-field']) ? $_POST['aicd-summary-field'] : '';
        update_post_meta($post_id, self::metaKeySummary, $summary);
    }


    /**
     * Enqueues admin scripts for the custom post type edit screen.
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function adminAssets($hook)
    {
        $screen = get_current_screen();
        if (!$screen || ($screen->post_type !== self::postType)) {
            return;
        }
        $handle = 'aicd-admin-summary';
        $src = plugin_dir_url(__FILE__) . 'assets/admin-summary.js';
        $ver = '1.0.0';
        wp_enqueue_script($handle, $src, ['jquery'], $ver, true);
        wp_localize_script($handle, 'AICD', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonceAction' => self::nonceAction,
        ]);
    }

    /**
     * Handles the AJAX request to generate an AI summary for a post.
     *
     * @return void Outputs JSON response and exits.
     */
    public function generateSummary()
    {

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Unauthorized', 'aicd')], 403);
        }

        $post_id = isset($_POST['postId']) ? absint($_POST['postId']) : 0;
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        $content_from_client = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';

        if (!wp_verify_nonce($_POST['nonce'], self::nonceAction)) {
            wp_send_json_error(['message' => __('Invalid nonce', 'aicd')], 403);
        }
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Invalid post ID', 'aicd')], 403);
        }

        $settings = get_option(self::optionSettingsKey);
        $length = isset($settings['summary_length']) ? (int) $settings['summary_length'] : 40;
        $length = $length > 0 ? $length : 40;

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found', 'cdai')], 404);
        }


        $content_source = $content_from_client ? $content_from_client : $post->post_content;

        $geminiAPIKey = $settings['gemini_api_key'] ?? '';
        $geminiAPIUrl = $settings['gemini_api_url'] ?? '';
        if (!empty($geminiAPIKey) && !empty($geminiAPIUrl)) {


            // Call the Gemini API to generate a summary
            $dataToSend = [
                "contents" => [
                    [
                        "parts" => [
                            [
                                "text" => "Give me a very short summary in " . $length . " words: " . $content_source
                            ]
                        ]
                    ]
                ]
            ];

            $response = wp_remote_post(
                $geminiAPIUrl . ':generateContent', [
                'body' => json_encode($dataToSend),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => esc_attr($geminiAPIKey)
                ]
            ]);

            if (is_wp_error($response)) {
                wp_send_json_error(['message' => __('Error communicating with Gemini API: ' . $response->get_error_message(), 'aicd')], 500);
            }
            $dataReceived = json_decode($response['body'], true);
            $dataReceived = $dataReceived['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $content_source = $dataReceived;
        }

        $content_source = wp_strip_all_tags($content_source);

        $summary = wp_trim_words($content_source, $length, '…');
        $summary = sanitize_text_field($summary);

        update_post_meta($post_id, self::metaKeySummary, $summary);

        wp_send_json_success(['summary' => $summary]);
    }

    /**
     * Appends the AI summary to the post content on the front end.
     *
     * @param string $content The original post content.
     * @return string Modified post content with summary appended if available.
     */
    public function addSummaryToContent($content)
    {
        if (!is_singular(self::postType) || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        $summary = get_post_meta(get_the_ID(), self::metaKeySummary, true);
        if (!$summary) {
            return $content;
        }
        $summaryHtml = '<div class="cdai-summary" style="border-top:1px solid #eee;margin-top:24px;padding-top:16px;">';
        $summaryHtml .= '<h3>' . esc_html__('Summary', 'cdai') . '</h3>';
        $summaryHtml .= '<p>' . esc_html($summary) . '</p>';
        $summaryHtml .= '</div>';
        return $content . $summaryHtml;
    }


    /**
     * Adds the settings page for the plugin to the WordPress admin menu.
     *
     * @return void
     */
    public function AiCommunityDiscussionsAddSettingsPage()
    {
        add_options_page(
            __('AI Discussion Summary', 'aicd'),
            __('AI Discussion Summary', 'aicd'),
            'manage_options',
            'aicd-settings',
            [$this, 'renderSettingsPage']
        );
    }

    /**
     * Initializes the plugin settings and settings fields.
     *
     * @return void
     */
    public function aiCommunityDiscussionsSettingsInit()
    {
        register_setting('aicd_settings_group', self::optionSettingsKey, [$this, 'sanitizeSettings']);

        add_settings_section(
            'aicdMainSection',
            __('Summary Settings', 'aicd'),
            function () {
                echo '<p>' . esc_html__('Configure how summaries are generated.', 'aicd') . '</p>';
            },
            'aicd-settings'
        );

        add_settings_field(
            'summary_length',
            __('Summary length (words)', 'aicd'),
            [$this, 'renderLengthField'],
            'aicd-settings',
            'aicdMainSection'
        );

        add_settings_section(
            'aicdGeminiAPI',
            __('Gemini API Settings', 'aicd'),
            function () {
                echo '<p>' . esc_html__('Configure the Gemini API settings.', 'aicd') . '</p>';
            },
            'aicd-settings'
        );

        add_settings_field(
            'gemini_api_key',
            __('Gemini API Data', 'aicd'),
            [$this, 'renderGeminiAPISettings'],
            'aicd-settings',
            'aicdGeminiAPI'
        );
    }


    public function renderGeminiAPISettings()
    {
        $options = get_option(self::optionSettingsKey, []);
        $apiKey = isset($options['gemini_api_key']) ? (string) $options['gemini_api_key'] : '';
        $apiUrl = isset($options['gemini_api_url']) ? (string) $options['gemini_api_url'] : '';
        

        echo '<input type="text" name="' . esc_attr(self::optionSettingsKey) . '[gemini_api_key]"
            value="' . $apiKey . '" />';
        echo '<p>Enter your Gemini API key.</p>';
        echo '<input style="margin-top: 20px;" type="text" name="' . esc_attr(self::optionSettingsKey) . '[gemini_api_url]"
            value="' . $apiUrl . '" />';
        echo '<p>Enter your Gemini API URL.</p>';
    }

    /**
     * Sanitizes the plugin settings input.
     *
     * @param array $input The input settings array.
     * @return array Sanitized settings array.
     */
    public function sanitizeSettings($input)
    {
        $sanitized = [];
        $sanitized['summary_length'] = isset($input['summary_length']) ? max(10, min(300, (int) $input['summary_length'])) : 40;
        $sanitized['gemini_api_key'] = isset($input['gemini_api_key']) ? (string) $input['gemini_api_key'] : '';
        $sanitized['gemini_api_url'] = isset($input['gemini_api_url']) ? (string) $input['gemini_api_url'] : '';
        return $sanitized;
    }

    /**
     * Renders the input field for summary length in the settings page.
     *
     * @return void
     */
    public function renderLengthField()
    {
        $options = get_option(self::optionSettingsKey, []);
        $value = isset($options['summary_length']) ? (int) $options['summary_length'] : 40;
        ?>
        <?php
        echo '<input type="number" name="' . esc_attr(self::optionSettingsKey) . '[summary_length]" value="' . esc_attr($value) . '" min="10" max="300" />';
        echo '<p>' . esc_html__('Specify the maximum length of the summary in words.', 'aicd') . '</p>';
    }



    /**
     * Renders the plugin settings page in the WordPress admin.
     *
     * @return void
     */
    function renderSettingsPage()
    {

        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Discussion Summaries', 'aicd') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('aicd_settings_group');
        do_settings_sections('aicd-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}

new AICommunityDiscussions();
