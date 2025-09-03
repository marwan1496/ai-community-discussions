<?php

if (!defined('ABSPATH')) {
    exit;
}

class AICommunityDiscussions_Activator
{
    /**
     * Sets default settings on plugin activation.
     *
     * @return void
     */
    public static function activate()
    {
        $defaultSettings = [
            'summary_length' => 40,
        ];
        if (!get_option(optionSettingsKey)) {
            update_option(optionSettingsKey, $defaultSettings);
        }
    }
}


