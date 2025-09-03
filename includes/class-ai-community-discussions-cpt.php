<?php

if (!defined('ABSPATH')) {
    exit;
}

class AICommunityDiscussions_CPT
{
    /**
     * Hook into WordPress to register CPT.
     *
     * @return void
     */
    public function register()
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
        register_post_type(postType, $args);
    }
}


