<?php
/**
 * Plugin Name: Virage AI Chat Widget
 * Description: Easily integrate the Virage AI chat widget on your WordPress site with advanced display rules. Once activated, go to **Settings > Virage AI Chat** to configure the widget.
 * Version: 1.4.3
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
        __('Virage AI Chat Widget Settings', 'virage-ai-chat-widget'),
        __('Virage AI Chat', 'virage-ai-chat-widget'),
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

    // Section: Global
    add_settings_section(
        'virage_ai_global_section',
        __('Global Settings', 'virage-ai-chat-widget'),
        null,
        'virage_ai_chat_widget'
    );

    add_settings_field(
        'virage_ai_enabled',
        __('Enable Chat Widget', 'virage-ai-chat-widget'),
        'virage_ai_field_callback',
        'virage_ai_chat_widget',
        'virage_ai_global_section',
        [
            'id' => 'enabled',
            'type' => 'checkbox',
            'description' => __('This is the main switch. If this is off, the widget will not appear anywhere.', 'virage-ai-chat-widget')
        ]
    );

    // Section: Widget Configuration
    add_settings_section(
        'virage_ai_config_section',
        __('Widget Configuration', 'virage-ai-chat-widget'),
        null,
        'virage_ai_chat_widget'
    );

    add_settings_field(
        'virage_ai_channel_uuid',
        __('Channel UUID', 'virage-ai-chat-widget') . ' <span style="color:red;">*</span>',
        'virage_ai_field_callback',
        'virage_ai_chat_widget',
        'virage_ai_config_section',
        [
            'id' => 'channel_uuid',
            'type' => 'text',
            'required' => true
        ]
    );

    // Section 2: Display Rules
    add_settings_section(
        'virage_ai_display_section',
        __('Display Rules', 'virage-ai-chat-widget'),
        function () {
            echo '<p>' . esc_html__('Use these settings to control exactly where the chat widget appears on your site.', 'virage-ai-chat-widget') . '</p>';
        },
        'virage_ai_chat_widget'
    );

    add_settings_field(
        'virage_ai_display_locations',
        __('Show on Specific Page Types', 'virage-ai-chat-widget'),
        'virage_ai_display_locations_callback',
        'virage_ai_chat_widget',
        'virage_ai_display_section'
    );
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
                esc_html($cpt->labels->name)
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

    if (isset($input['enabled'])) {
        $sanitized_input['enabled'] = $input['enabled'] ? 1 : 0;
    } else {
        $sanitized_input['enabled'] = 0;
    }

    if (isset($input['channel_uuid'])) {
        // A tampered form can post an array: trim() throws a TypeError on PHP 8, and WP 5.0's sanitize_text_field() stores "Array".
        $sanitized_input['channel_uuid'] = is_string($input['channel_uuid']) ? sanitize_text_field($input['channel_uuid']) : '';
    }

    return $sanitized_input;
}

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

    // Check for required UUID and the main 'enabled' switch
    if (empty($options['enabled']) || empty($options['channel_uuid'])) {
        return;
    }

    // Check display rules
    $locations = $options['display_locations'] ?? [];
    if (empty($locations)) {
        return;
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

    printf(
        '<script src="https://storage.googleapis.com/virage-public/chat-widget/cdn/chat-widget-sdk-v1.min.js" data-channel-uuid="%s" async defer></script>',
        esc_attr($options['channel_uuid'])
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
 * On activation, check for a pre-configured settings file and save its values.
 * This runs only once when the plugin is activated for the first time.
 */
function virage_ai_activate_plugin() {
    // Check if options already exist. If so, do nothing to avoid overwriting user settings.
    if (get_option('virage_ai_options')) {
        return;
    }

    $config_file_path = __DIR__ . '/defaults.php';

    // Check if the configuration file exists.
    if (file_exists($config_file_path)) {
        // Require the file to get the array of settings.
        $default_options = require $config_file_path;

        // Ensure it's an array and not empty.
        if (is_array($default_options) && !empty($default_options)) {
            // Enable the widget by default for pre-configured installs.
            $default_options['enabled'] = '1';

            // Save the settings to the WordPress database.
            update_option('virage_ai_options', $default_options);
        }
    }
}
register_activation_hook(__FILE__, 'virage_ai_activate_plugin');
