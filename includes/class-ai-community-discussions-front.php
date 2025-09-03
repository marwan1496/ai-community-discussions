<?php

if (!defined('ABSPATH')) {
    exit;
}

class AICommunityDiscussions_Public
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
        add_filter('the_content', [$this, 'addSummaryToContent']);
    }

    public function addSummaryToContent($content)
    {
        if (!is_singular(postType) || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        $summary = get_post_meta(get_the_ID(), metaKeySummary, true);
        if (!$summary) {
            return $content;
        }
        $summaryHtml = '<div class="aicd-summary" style="border-top:1px solid #eee;margin-top:24px;padding-top:16px;">';
        $summaryHtml .= '<h3>' . esc_html__('Summary', 'aicd') . '</h3>';
        $summaryHtml .= '<p>' . esc_html($summary) . '</p>';
        $summaryHtml .= '</div>';
        return $content . $summaryHtml;
    }
}


