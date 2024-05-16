<?php
/*
Plugin Name: Image Randomizer
Description: Display a random image from the WordPress media library.
Version: 1.0
Author: Haley Stelly
Author URI: https://stly.dev/
License: GPL-3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

// Add settings menu item
function image_randomizer_menu() {
    add_options_page('Image Randomizer Settings', 'Image Randomizer', 'manage_options', 'image-randomizer-settings', 'image_randomizer_settings_page');
}
add_action('admin_menu', 'image_randomizer_menu');

// Render settings page
function image_randomizer_settings_page() {
    ?>
    <div class="wrap">
        <h2>Image Randomizer Settings</h2>
        <p>Select images from the WordPress media library.</p>
    </div>
    <?php
}

// Add meta box to post edit screen
function image_randomizer_meta_box() {
    add_meta_box('image_randomizer_meta_box', 'Select Images', 'image_randomizer_meta_box_callback', 'post');
}
add_action('add_meta_boxes', 'image_randomizer_meta_box');

// Meta box callback function
function image_randomizer_meta_box_callback($post) {
    wp_nonce_field(basename(__FILE__), 'image_randomizer_nonce');
    $selected_images = get_post_meta($post->ID, '_selected_images', true);
    ?>
    <p>Select the images you want to include in the randomizer:</p>
    <ul>
        <?php
        $args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        );

        $attachments = get_posts($args);
        foreach ($attachments as $attachment) {
            $checked = in_array($attachment->ID, $selected_images) ? 'checked' : '';
            echo '<li><label><input type="checkbox" name="selected_images[]" value="' . esc_attr($attachment->ID) . '" ' . $checked . '> ' . esc_html(get_the_title($attachment->ID)) . '</label></li>';
        }
        ?>
    </ul>
    <?php
}

// Save selected images meta data
function image_randomizer_save_meta_box_data($post_id) {
    if (!isset($_POST['image_randomizer_nonce']) || !wp_verify_nonce($_POST['image_randomizer_nonce'], basename(__FILE__))) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['selected_images'])) {
        update_post_meta($post_id, '_selected_images', $_POST['selected_images']);
    } else {
        delete_post_meta($post_id, '_selected_images');
    }
}
add_action('save_post', 'image_randomizer_save_meta_box_data');

// Shortcode for displaying selected images
function image_randomizer_shortcode($atts) {
    $atts = shortcode_atts(array(
        'ids' => '', // Default value is an empty string
    ), $atts);

    // Check if 'ids' attribute is empty or not provided
    if (empty($atts['ids'])) {
        return 'No image IDs provided.';
    }

    // Convert comma-separated list of IDs to an array
    $ids = explode(',', $atts['ids']);

    // Remove empty elements and sanitize IDs
    $ids = array_map('absint', array_filter($ids));

    // If no valid IDs provided, return error message
    if (empty($ids)) {
        return 'No valid image IDs provided.';
    }

    // Retrieve image URLs based on provided IDs
    $images = array();
    foreach ($ids as $id) {
        $image_url = wp_get_attachment_url($id);
        if ($image_url) {
            $images[] = $image_url;
        }
    }

    // If no valid images found, return error message
    if (empty($images)) {
        return 'No valid images found.';
    }

    // Select a random image URL
    $random_image = $images[array_rand($images)];

    // Output the random image HTML
    return '<div class="random-image"><img src="' . esc_url($random_image) . '" alt="Random Image"></div>';
}
add_shortcode('image_randomizer', 'image_randomizer_shortcode');
