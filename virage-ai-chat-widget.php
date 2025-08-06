<?php
/**
 * Plugin Name: Virage AI Chat Widget
 * Description: Easily integrate the Virage AI chat widget on your WordPress site with advanced display rules. Once activated, go to **Settings > Virage AI Chat** to configure the widget.
 * Version: 1.1.1
 * Author: Virage AI
 * Author URI: https://virage.ai/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: virage-ai-chat-widget
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Load plugin textdomain for localization.
 * This function loads the .mo file for the current language.
 */
function virage_ai_load_textdomain()
{
    load_plugin_textdomain('virage-ai-chat-widget', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

add_action('plugins_loaded', 'virage_ai_load_textdomain');


// Setup for automatic updates from GitHub.
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/Virage-AI/virage-ai-chat-widget',
    __FILE__,
    'virage-ai-chat-widget'
);
$myUpdateChecker->setBranch('main');

// =============================================================================
// Settings Page Setup (Admin Area)
// =============================================================================

/**
 * Add the settings page to the admin menu.
 */
function virage_ai_add_admin_menu()
{
    add_options_page(
        __('Virage AI Chat Widget Settings', 'virage-ai-chat-widget'), // Page Title
        __('Virage AI Chat', 'virage-ai-chat-widget'), // Menu Title
        'manage_options',
        'virage_ai_chat_widget',
        'virage_ai_options_page_html'
    );
}

add_action('admin_menu', 'virage_ai_add_admin_menu');

/**
 * Register settings, sections, and fields.
 */
function virage_ai_settings_init()
{
    register_setting('virage_ai_options_group', 'virage_ai_options', 'virage_ai_sanitize_options');

    // Section 1: Widget Configuration
    add_settings_section(
        'virage_ai_config_section',
        __('Widget Configuration', 'virage-ai-chat-widget'),
        null,
        'virage_ai_chat_widget'
    );

    // All configuration fields with translatable labels and some default values.
    $config_fields = [
        'organization_uuid' => ['label' => __('Organization UUID', 'virage-ai-chat-widget'), 'type' => 'text', 'required' => true],
        'project_uuid' => ['label' => __('Project UUID', 'virage-ai-chat-widget'), 'type' => 'text', 'required' => true],
        'channel_uuid' => ['label' => __('Channel UUID', 'virage-ai-chat-widget'), 'type' => 'text', 'required' => true],
        'whatsapp_redirect_url' => ['label' => __('WhatsApp Redirect URL', 'virage-ai-chat-widget'), 'type' => 'url'],

        'button_icon_url' => ['label' => __('Button Icon URL', 'virage-ai-chat-widget'), 'type' => 'url', 'default' => 'https://storage.googleapis.com/virage-public/chat-widget/whatsapp.svg'],
        'button_text' => ['label' => __('Button Text', 'virage-ai-chat-widget'), 'type' => 'text'],
        'button_text_color' => ['label' => __('Button Text Color', 'virage-ai-chat-widget'), 'type' => 'color', 'default' => '#FFFFFF'],
        'button_bg_color' => ['label' => __('Button Background Color', 'virage-ai-chat-widget'), 'type' => 'color', 'default' => '#4edd82'],
        'button_size' => ['label' => __('Button Size', 'virage-ai-chat-widget'), 'type' => 'text', 'default' => '64px'],
        'button_bottom_offset' => ['label' => __('Button Bottom Offset', 'virage-ai-chat-widget'), 'type' => 'text', 'default' => '20px'],
        'button_right_offset' => ['label' => __('Button Right Offset', 'virage-ai-chat-widget'), 'type' => 'text', 'default' => '20px'],

        'popup_avatar_url' => ['label' => __('Popup Avatar URL', 'virage-ai-chat-widget'), 'type' => 'url', 'default' => 'https://storage.googleapis.com/virage-public/chat-widget/squared_white.jpg'],
        'popup_avatar_name' => ['label' => __('Popup Avatar Name', 'virage-ai-chat-widget'), 'type' => 'text', 'default' => __('Virage AI', 'virage-ai-chat-widget')],
        'popup_whats_app_text' => ['label' => __('Popup WhatsApp Text', 'virage-ai-chat-widget'), 'type' => 'textarea', 'default' => __('Scan this QR code to start<br/>the conversation on WhatsApp:', 'virage-ai-chat-widget')],
        'popup_whats_app_cta_text' => ['label' => __('Popup WhatsApp CTA Text', 'virage-ai-chat-widget'), 'type' => 'text', 'default' => __('Continue on desktop', 'virage-ai-chat-widget')],
        'popup-web-welcome-text' => ['label' => __('Popup Web Welcome Text', 'virage-ai-chat-widget'), 'type' => 'textarea', 'default' => __('Hello!<br />How can I help you?:', 'virage-ai-chat-widget')],

        'popup_width' => ['label' => __('Popup Width', 'virage-ai-chat-widget'), 'type' => 'text', 'default' => '350px'],
        'popup_height' => ['label' => __('Popup Height', 'virage-ai-chat-widget'), 'type' => 'text', 'default' => '490px'],
        'popup_bottom_offset' => ['label' => __('Popup Bottom Offset', 'virage-ai-chat-widget'), 'type' => 'text', 'default' => '90px'],
        'popup_right_offset' => ['label' => __('Popup Right Offset', 'virage-ai-chat-widget'), 'type' => 'text', 'default' => '20px'],

        'popup_tabs' => ['label' => __('Popup Tabs', 'virage-ai-chat-widget'), 'type' => 'text', 'default' => '1,2'],
        'popup_content_url' => ['label' => __('Popup Content URL', 'virage-ai-chat-widget'), 'type' => 'url', 'default' => 'https://chat-widget.virage.ai'],
    ];

    foreach ($config_fields as $id => $field) {
        add_settings_field('virage_ai_' . $id, $field['label'] . (isset($field['required']) ? ' <span style="color:red;">*</span>' : ''), 'virage_ai_field_callback', 'virage_ai_chat_widget', 'virage_ai_config_section', ['id' => $id, 'type' => $field['type'], 'default' => $field['default'] ?? '']);
    }

    // Section 2: Display Rules
    add_settings_section(
        'virage_ai_display_section',
        __('Display Rules', 'virage-ai-chat-widget'),
        function () {
            echo '<p>' . esc_html__('Use these settings to control exactly where the chat widget appears on your site.', 'virage-ai-chat-widget') . '</p>';
        },
        'virage_ai_chat_widget'
    );

    add_settings_field('virage_ai_enabled', __('Enable Chat Widget', 'virage-ai-chat-widget'), 'virage_ai_field_callback', 'virage_ai_chat_widget', 'virage_ai_display_section', ['id' => 'enabled', 'type' => 'checkbox', 'description' => __('This is the main switch. If this is off, the widget will not appear anywhere.', 'virage-ai-chat-widget')]);
    add_settings_field('virage_ai_display_locations', __('Show on Specific Page Types', 'virage-ai-chat-widget'), 'virage_ai_display_locations_callback', 'virage_ai_chat_widget', 'virage_ai_display_section');
}

add_action('admin_init', 'virage_ai_settings_init');

/**
 * Generic callback to render standard HTML for the input fields.
 */
function virage_ai_field_callback($args)
{
    $options = get_option('virage_ai_options');
    $id = esc_attr($args['id']);
    $value = isset($options[$id]) ? $options[$id] : ($args['default'] ?? '');
    $name = "virage_ai_options[{$id}]";

    switch ($args['type']) {
        case 'checkbox':
            printf('<input type="checkbox" id="%s" name="%s" value="1" %s />', $id, $name, checked(1, $value, false));
            if (!empty($args['description'])) {
                printf('<p class="description">%s</p>', esc_html($args['description']));
            }
            break;
        case 'textarea':
            printf('<textarea id="%s" name="%s" rows="4" class="large-text">%s</textarea>', $id, $name, esc_textarea($value));
            break;
        case 'color':
            printf('<input type="text" id="%s" name="%s" value="%s" class="virage-ai-color-picker" />', $id, $name, esc_attr($value));
            break;
        case 'url':
            printf('<input type="url" id="%s" name="%s" value="%s" class="regular-text" />', $id, $name, esc_url($value));
            break;
        case 'text':
        default:
            printf('<input type="text" id="%s" name="%s" value="%s" class="regular-text" />', $id, $name, esc_attr($value));
            break;
    }
}

/**
 * Special callback to render the group of checkboxes for display locations.
 */
function virage_ai_display_locations_callback()
{
    $options = get_option('virage_ai_options');
    $locations = $options['display_locations'] ?? [];

    // Translatable page types
    $page_types = [
        'homepage' => __('Homepage', 'virage-ai-chat-widget'),
        'posts' => __('All Posts (single post view)', 'virage-ai-chat-widget'),
        'pages' => __('All Pages', 'virage-ai-chat-widget'),
        'archives' => __('Archive Pages', 'virage-ai-chat-widget'),
        'categories' => __('Category Pages', 'virage-ai-chat-widget'),
        'e404_page' => __('404 Not Found Page', 'virage-ai-chat-widget'),
    ];

    echo '<h4>' . esc_html__('Standard Pages', 'virage-ai-chat-widget') . '</h4>';
    foreach ($page_types as $key => $label) {
        $checked = isset($locations[$key]) && $locations[$key] == 1;
        printf(
            '<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="virage_ai_options[display_locations][%s]" value="1" %s /> %s</label>',
            esc_attr($key),
            checked($checked, true, false),
            esc_html($label)
        );
    }

    $custom_post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
    if (!empty($custom_post_types)) {
        echo '<h4>' . esc_html__('Custom Post Types', 'virage-ai-chat-widget') . '</h4>';
        foreach ($custom_post_types as $cpt) {
            $checked = isset($locations['cpt'][$cpt->name]) && $locations['cpt'][$cpt->name] == 1;
            printf(
                '<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="virage_ai_options[display_locations][cpt][%s]" value="1" %s /> %s</label>',
                esc_attr($cpt->name),
                checked($checked, true, false),
                esc_html($cpt->labels->name) // CPT labels are translatable by default in WordPress.
            );
        }
    }
}

/**
 * Sanitize the options before saving to the database.
 */
function virage_ai_sanitize_options($input)
{
    $sanitized_input = [];

    // Sanitize display locations
    if (isset($input['display_locations']) && is_array($input['display_locations'])) {
        $display_locations = [];
        foreach ($input['display_locations'] as $key => $value) {
            if ($key === 'cpt' && is_array($value)) {
                foreach ($value as $cpt_key => $cpt_value) {
                    $display_locations['cpt'][sanitize_key($cpt_key)] = $cpt_value ? 1 : 0;
                }
            } else {
                $display_locations[sanitize_key($key)] = $value ? 1 : 0;
            }
        }
        $sanitized_input['display_locations'] = $display_locations;
    } else {
        $sanitized_input['display_locations'] = [];
    }

    // Sanitize all other fields
    $other_fields = $input;
    unset($other_fields['display_locations']);

    foreach ($other_fields as $key => $value) {
        $s_key = sanitize_key($key);
        if (str_ends_with($s_key, '_color')) {
            $sanitized_input[$s_key] = sanitize_hex_color($value);
        } elseif (str_ends_with($s_key, '_url')) {
            $sanitized_input[$s_key] = esc_url_raw(trim($value));
        } elseif ($s_key === 'popup_whats_app_text') {
            $sanitized_input[$s_key] = wp_kses(trim($value), ['br' => []]);
        } else {
            $sanitized_input[$s_key] = sanitize_text_field(trim($value));
        }
    }

    // Define which fields are translatable
    $translatable_fields = [
        'button_text' => __('Button Text', 'virage-ai-chat-widget'),
        'popup_avatar_name' => __('Popup Avatar Name', 'virage-ai-chat-widget'),
        'popup_whats_app_text' => __('Popup WhatsApp Text', 'virage-ai-chat-widget'),
        'popup_whats_app_cta_text' => __('Popup WhatsApp CTA Text', 'virage-ai-chat-widget'),
        'popup-web-welcome-text' => __('Popup Web Welcome Text', 'virage-ai-chat-widget'),
    ];

    foreach ($translatable_fields as $key => $label) {
        if (!empty($input[$key])) {
            virage_ai_register_string_for_translation($input[$key], $label);
        }
    }

    return $sanitized_input;
}

/**
 * Enqueue the WordPress color picker on our settings page.
 */
function virage_ai_enqueue_admin_scripts($hook_suffix)
{
    if ($hook_suffix !== 'settings_page_virage_ai_chat_widget') return;
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('virage-ai-admin-script', false, ['wp-color-picker'], false, true);
    wp_add_inline_script('virage-ai-admin-script', 'jQuery(document).ready(function($){$(".virage-ai-color-picker").wpColorPicker();});');
}

add_action('admin_enqueue_scripts', 'virage_ai_enqueue_admin_scripts');

/**
 * HTML for the options page wrapper.
 */
function virage_ai_options_page_html()
{
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('virage_ai_options_group');
            do_settings_sections('virage_ai_chat_widget');
            submit_button(__('Save Settings', 'virage-ai-chat-widget'));
            ?>
        </form>
    </div>
    <?php
}

// =============================================================================
// Front-End Script Injection
// =============================================================================

/**
 * Add the script to the website footer based on display rules.
 */
function virage_ai_add_widget_script()
{
    $options = get_option('virage_ai_options');

    // 1. Check for required UUIDs and the main 'enabled' switch
    if (empty($options['enabled']) || empty($options['organization_uuid']) || empty($options['project_uuid']) || empty($options['channel_uuid'])) {
        return;
    }

    // 2. Check display rules
    $locations = $options['display_locations'] ?? [];
    if (empty($locations)) {
        return; // Don't show if no locations are chosen
    }
    $show_widget = false;

    if (is_front_page() && !empty($locations['homepage'])) $show_widget = true;
    if (is_singular('post') && !empty($locations['posts'])) $show_widget = true;
    if (is_page() && !is_front_page() && !empty($locations['pages'])) $show_widget = true;
    if (is_archive() && !empty($locations['archives'])) $show_widget = true;
    if (is_category() && !empty($locations['categories'])) $show_widget = true;
    if (is_404() && !empty($locations['e404_page'])) $show_widget = true;

    // Check for custom post types
    if (!$show_widget && is_singular() && !empty($locations['cpt'])) {
        $cpt = get_post_type();
        if (isset($locations['cpt'][$cpt]) && $locations['cpt'][$cpt]) {
            $show_widget = true;
        }
    }

    if (!$show_widget) {
        return;
    }

    // 3. Get translated versions of the options before outputting them
    $options['button_text'] = virage_ai_get_translated_string($options['button_text'] ?? '', __('Button Text', 'virage-ai-chat-widget'));
    $options['popup_avatar_name'] = virage_ai_get_translated_string($options['popup_avatar_name'] ?? '', __('Popup Avatar Name', 'virage-ai-chat-widget'));
    $options['popup_whats_app_text'] = virage_ai_get_translated_string($options['popup_whats_app_text'] ?? '', __('Popup WhatsApp Text', 'virage-ai-chat-widget'));
    $options['popup_whats_app_cta_text'] = virage_ai_get_translated_string($options['popup_whats_app_cta_text'] ?? '', __('Popup WhatsApp CTA Text', 'virage-ai-chat-widget'));
    $options['popup-web-welcome-text'] = virage_ai_get_translated_string($options['popup-web-welcome-text'] ?? '', __('Popup Web Welcome Text', 'virage-ai-chat-widget'));

    // 4. Build and output the script tag
    $data_attrs = '';
    $exclude_from_data = ['enabled', 'display_locations'];

    foreach ($options as $key => $value) {
        if (!empty($value) && !in_array($key, $exclude_from_data)) {
            $attr_name = 'data-' . str_replace('_', '-', $key);
            $data_attrs .= sprintf('%s="%s" ', esc_attr($attr_name), esc_attr($value));
        }
    }

    printf(
        '<script src="https://chat-widget.virage.ai/cdn/chat-widget-sdk-v1.min.js" %s async defer></script>',
        $data_attrs
    );
}

add_action('wp_footer', 'virage_ai_add_widget_script');

/**
 * Add a settings link to the plugins page for easy access.
 */
function virage_ai_add_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=virage_ai_chat_widget">' . __('Settings', 'virage-ai-chat-widget') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'virage_ai_add_settings_link');

/**
 * Registers a dynamic string for translation with WPML or Polylang.
 *
 * @param string $string_value The string to register.
 * @param string $string_name  A unique name for the string (e.g., 'Popup WhatsApp Text').
 * @param string $context      The group/context for the string (your plugin name is a good choice).
 */
function virage_ai_register_string_for_translation($string_value, $string_name, $context = 'Virage AI Chat Widget') {
    // For WPML
    if (function_exists('do_action')) {
        do_action('wpml_register_single_string', $context, $string_name, $string_value);
    }

    // For Polylang
    if (function_exists('pll_register_string')) {
        pll_register_string($string_name, $string_value, $context);
    }
}

/**
 * A helper function to get the translated version of a string.
 *
 * @param string $string_value The default string value.
 * @param string $string_name  The unique name of the string.
 * @param string $context      The group/context for the string.
 * @return string The translated string if available, otherwise the original.
 */
function virage_ai_get_translated_string($string_value, $string_name, $context = 'Virage AI Chat Widget') {
    // For WPML
    if (function_exists('apply_filters')) {
        $string_value = apply_filters('wpml_translate_single_string', $string_value, $context, $string_name);
    }

    // For Polylang
    if (function_exists('pll__')) {
        $string_value = pll__($string_value);
    }

    return $string_value;
}