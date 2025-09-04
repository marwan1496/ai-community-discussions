<?php

if (!defined('ABSPATH')) {
    exit;
}

class AICommunityDiscussions_Admin
{
    private $plugin_dir;
    private $plugin_url;

    public function __construct($plugin_dir, $plugin_url)
    {
        $this->plugin_dir = $plugin_dir;
        $this->plugin_url = $plugin_url;
    }

    public function hooks()
    {
        add_action('add_meta_boxes', [$this, 'addSummaryMetaBox']);
        add_action('save_post', [$this, 'saveSummaryMeta'], 10, 2);
        add_action('admin_menu', [$this, 'AiCommunityDiscussionsAddSettingsPage']);
        add_action('admin_init', [$this, 'aiCommunityDiscussionsSettingsInit']);
        add_action('admin_enqueue_scripts', [$this, 'adminAssets']);
        add_action('wp_ajax_ai_community_discussions_generate_summary', [$this, 'generateSummary']);
    }

    public function addSummaryMetaBox()
    {
        add_meta_box(
            'aicd_summary_box',
            __('AI Summary', 'aicd'),
            [$this, 'renderSummaryMetaBox'],
            postType,
            'side',
            'default'
        );
    }

    public function renderSummaryMetaBox()
    {
        $summary = get_post_meta(get_the_ID(), metaKeySummary, true);
        $summary = is_string($summary) ? $summary : '';
        $nonce = wp_create_nonce(nonceAction);
        echo '<div id="aicd-summary-box">';
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

    public function saveSummaryMeta($post_id)
    {
        $post_type = get_post_type($post_id);
        if ($post_type !== postType) {
            return;
        }
        if (!isset($_POST['aicd-nonce']) || !wp_verify_nonce($_POST['aicd-nonce'], nonceAction)) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $summary = isset($_POST['aicd-summary-field']) ? $_POST['aicd-summary-field'] : '';
        update_post_meta($post_id, metaKeySummary, $summary);
    }

    public function adminAssets($hook)
    {
        $screen = get_current_screen();
        if (!$screen || ($screen->post_type !== postType)) {
            return;
        }
        $handle = 'aicd-admin-summary';
        $src = $this->plugin_url . 'assets/admin-summary.js';
        $ver = '1.0.0';
        wp_enqueue_script($handle, $src, ['jquery'], $ver, true);
        wp_localize_script($handle, 'AICD', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonceAction' => nonceAction,
        ]);
    }

    public function generateSummary()
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Unauthorized', 'aicd')], 403);
        }

        $post_id = isset($_POST['postId']) ? absint($_POST['postId']) : 0;
        $content_from_client = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';

        if (!wp_verify_nonce($_POST['nonce'], nonceAction)) {
            wp_send_json_error(['message' => __('Invalid nonce', 'aicd')], 403);
        }
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Invalid post ID', 'aicd')], 403);
        }

        $settings = get_option(optionSettingsKey);
        $length = isset($settings['summary_length']) ? (int) $settings['summary_length'] : 40;
        $length = $length > 0 ? $length : 40;

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found', 'aicd')], 404);
        }

        $content_source = $content_from_client ? $content_from_client : $post->post_content;

        $geminiAPIKey = $settings['gemini_api_key'] ?? '';
        $geminiAPIUrl = $settings['gemini_api_url'] ?? '';
        if (!empty($geminiAPIKey) && !empty($geminiAPIUrl)) {
            $dataToSend = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => 'Give me a very short summary in ' . $length . ' words: ' . $content_source
                            ]
                        ]
                    ]
                ]
            ];

            $response = wp_remote_post(
                $geminiAPIUrl . ':generateContent', [
                'body' => wp_json_encode($dataToSend),
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

        update_post_meta($post_id, metaKeySummary, $summary);

        wp_send_json_success(['summary' => $summary]);
    }

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

    public function aiCommunityDiscussionsSettingsInit()
    {
        register_setting('aicd_settings_group', optionSettingsKey, [$this, 'sanitizeSettings']);

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
        $options = get_option(optionSettingsKey, []);
        $apiKey = isset($options['gemini_api_key']) ? (string) $options['gemini_api_key'] : '';
        $apiUrl = isset($options['gemini_api_url']) ? (string) $options['gemini_api_url'] : '';

        echo '<input type="text" name="' . esc_attr(optionSettingsKey) . '[gemini_api_key]" value="' . esc_attr($apiKey) . '" />';
        echo '<p>Enter your Gemini API key.</p>';
        echo '<input style="margin-top: 20px;" type="text" name="' . esc_attr(optionSettingsKey) . '[gemini_api_url]" value="' . esc_attr($apiUrl) . '" />';
        echo '<p>Enter your Gemini API URL.</p>';
    }

    public function sanitizeSettings($input)
    {
        $sanitized = [];
        $sanitized['summary_length'] = isset($input['summary_length']) ? max(10, min(300, (int) $input['summary_length'])) : 40;
        $sanitized['gemini_api_key'] = isset($input['gemini_api_key']) ? (string) $input['gemini_api_key'] : '';
        $sanitized['gemini_api_url'] = isset($input['gemini_api_url']) ? (string) $input['gemini_api_url'] : '';
        return $sanitized;
    }

    public function renderLengthField()
    {
        $options = get_option(optionSettingsKey, []);
        $value = isset($options['summary_length']) ? (int) $options['summary_length'] : 40;
        echo '<input type="number" name="' . esc_attr(optionSettingsKey) . '[summary_length]" value="' . esc_attr($value) . '" min="10" max="300" />';
        echo '<p>' . esc_html__('Specify the maximum length of the summary in words.', 'aicd') . '</p>';
    }

    public function renderSettingsPage()
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


