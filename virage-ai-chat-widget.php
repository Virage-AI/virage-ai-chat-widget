<?php
/**
 * Plugin Name: Virage AI Chat Widget
 * Description: Intégrez facilement le widget de chat Virage AI sur votre site WordPress avec des règles d'affichage avancées. Une fois activé, rendez-vous dans **Réglages > Virage AI Chat** pour configurer le widget
 * Version: 0.0.6
 * Author: Virage AI
 * Author URI: https://virage.ai/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: virage-ai-chat-widget
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

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
function virage_ai_add_admin_menu() {
    add_options_page(
        'Virage AI Chat Widget Settings',
        'Virage AI Chat',
        'manage_options',
        'virage_ai_chat_widget',
        'virage_ai_options_page_html'
    );
}
add_action('admin_menu', 'virage_ai_add_admin_menu');

/**
 * Register settings, sections, and fields.
 */
function virage_ai_settings_init() {
    register_setting('virage_ai_options_group', 'virage_ai_options', 'virage_ai_sanitize_options');

    // Section 1: Widget Configuration
    add_settings_section(
        'virage_ai_config_section',
        'Widget Configuration',
        null,
        'virage_ai_chat_widget'
    );

    $config_fields = [
        'organization_uuid' => ['label' => 'Organization UUID', 'type' => 'text', 'required' => true],
        'project_uuid' => ['label' => 'Project UUID', 'type' => 'text', 'required' => true],
        'whatsapp_redirect_url' => ['label' => 'WhatsApp Redirect URL', 'type' => 'url'],

        'button_icon_url' => ['label' => 'Button Icon URL', 'type' => 'url', 'default' => 'https://storage.googleapis.com/virage-public/chat-widget/whatsapp.svg'],
        'button_text' => ['label' => 'Button Text', 'type' => 'text'],
        'button_text_color' => ['label' => 'Button Text Color', 'type' => 'color', 'default' => '#FFFFFF'],
        'button_bg_color' => ['label' => 'Button Background Color', 'type' => 'color', 'default' => '#4edd82'],
        'button_size' => ['label' => 'Button Size', 'type' => 'text', 'default' => '64px'],
        'button_bottom_offset' => ['label' => 'Button Bottom Offset', 'type' => 'text', 'default' => '20px'],
        'button_right_offset' => ['label' => 'Button Right Offset', 'type' => 'text', 'default' => '20px'],

        'popup_avatar_url' => ['label' => 'Popup Avatar URL', 'type' => 'url', 'default' => 'https://storage.googleapis.com/virage-public/chat-widget/squared_white.jpg'],
        'popup_avatar_name' => ['label' => 'Popup Avatar Name', 'type' => 'text', 'default' => 'Virage AI'],
        'popup_whats_app_text' => ['label' => 'Popup WhatsApp Text', 'type' => 'text', 'default' => 'Scan this QR code to start<br/>the conversation on WhatsApp:'],
        'popup_whats_app_cta_text' => ['label' => 'Popup WhatsApp CTA Text', 'type' => 'text', 'default' => 'Continue on desktop'],

        'popup_width' => ['label' => 'Popup Width', 'type' => 'text', 'default' => '350px'],
        'popup_height' => ['label' => 'Popup Height', 'type' => 'text', 'default' => '490px'],
        'popup_bottom_offset' => ['label' => 'Popup Bottom Offset', 'type' => 'text', 'default' => '90px'],
        'popup_right_offset' => ['label' => 'Popup Right Offset', 'type' => 'text', 'default' => '20px'],

        'popup_tabs' => ['label' => 'Popup Tabs', 'type' => 'text', 'default' => '1,2'],
        'popup_content_url' => ['label' => 'Popup Content URL', 'type' => 'url', 'default' => 'https://chat-widget.virage.ai'],
    ];

    foreach ($config_fields as $id => $field) {
        add_settings_field('virage_ai_' . $id, $field['label'] . (isset($field['required']) ? ' <span style="color:red;">*</span>' : ''), 'virage_ai_field_callback', 'virage_ai_chat_widget', 'virage_ai_config_section', ['id' => $id, 'type' => $field['type'], 'default' => $field['default'] ?? '']);
    }

    // Section 2: Display Rules
    add_settings_section(
        'virage_ai_display_section',
        'Display Rules',
        function () { echo '<p>Use these settings to control exactly where the chat widget appears on your site.</p>'; },
        'virage_ai_chat_widget'
    );

    add_settings_field('virage_ai_enabled', 'Enable Chat Widget', 'virage_ai_field_callback', 'virage_ai_chat_widget', 'virage_ai_display_section', ['id' => 'enabled', 'type' => 'checkbox', 'description' => 'This is the main switch. If this is off, the widget will not appear anywhere.']);

    add_settings_field('virage_ai_display_locations', 'Show on Specific Page Types', 'virage_ai_display_locations_callback', 'virage_ai_chat_widget', 'virage_ai_display_section');
}
add_action('admin_init', 'virage_ai_settings_init');

/**
 * Generic callback to render standard HTML for the input fields.
 */
function virage_ai_field_callback($args) {
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
function virage_ai_display_locations_callback() {
    $options = get_option('virage_ai_options');
    $locations = $options['display_locations'] ?? [];

    $page_types = [
        'homepage'   => 'Homepage',
        'posts'      => 'All Posts (single post view)',
        'pages'      => 'All Pages',
        'archives'   => 'Archive Pages',
        'categories' => 'Category Pages',
        'e404_page'  => '404 Not Found Page',
    ];

    echo '<h4>Standard Pages</h4>';
    foreach ($page_types as $key => $label) {
        $checked = isset($locations[$key]) ? $locations[$key] : 0;
        printf(
            '<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="virage_ai_options[display_locations][%s]" value="1" %s /> %s</label>',
            esc_attr($key),
            checked(1, $checked, false),
            esc_html($label)
        );
    }

    $custom_post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
    if (!empty($custom_post_types)) {
        echo '<h4>Custom Post Types</h4>';
        foreach ($custom_post_types as $cpt) {
            $checked = isset($locations['cpt'][$cpt->name]) ? $locations['cpt'][$cpt->name] : 0;
            printf(
                '<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="virage_ai_options[display_locations][cpt][%s]" value="1" %s /> %s</label>',
                esc_attr($cpt->name),
                checked(1, $checked, false),
                esc_html($cpt->labels->name)
            );
        }
    }
}

/**
 * Sanitize the options before saving to the database.
 */
function virage_ai_sanitize_options($input) {
    $sanitized_input = [];
    $options = get_option('virage_ai_options');

    // Sanitize display locations
    if (isset($input['display_locations'])) {
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
        // If no checkboxes are checked, ensure the option is an empty array
        $sanitized_input['display_locations'] = [];
    }

    // Sanitize other fields
    foreach ($input as $key => $value) {
        if ($key === 'display_locations') continue; // Already handled

        if ($key === 'button_bg_color') {
            $sanitized_input[$key] = sanitize_hex_color($value);
        } elseif (strpos($key, '_url') !== false) {
            $sanitized_input[$key] = esc_url_raw(trim($value));
        } elseif ($key === 'popup_text') {
            $sanitized_input[$key] = wp_kses($value, ['br' => []]);
        } else {
            $sanitized_input[$key] = sanitize_text_field(trim($value));
        }
    }

    return $sanitized_input;
}

/**
 * Enqueue the WordPress color picker on our settings page.
 */
function virage_ai_enqueue_admin_scripts($hook_suffix) {
    if ($hook_suffix !== 'settings_page_virage_ai_chat_widget') return;
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('virage-ai-admin-script', false, ['wp-color-picker'], false, true);
    wp_add_inline_script('virage-ai-admin-script', 'jQuery(document).ready(function($){$(".virage-ai-color-picker").wpColorPicker();});');
}
add_action('admin_enqueue_scripts', 'virage_ai_enqueue_admin_scripts');

/**
 * HTML for the options page wrapper.
 */
function virage_ai_options_page_html() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('virage_ai_options_group');
            do_settings_sections('virage_ai_chat_widget');
            submit_button('Save Settings');
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
function virage_ai_add_widget_script() {
    $options = get_option('virage_ai_options');

    // 1. Check for required UUIDs and the main 'enabled' switch
    if (empty($options['organization_uuid']) || empty($options['project_uuid']) || empty($options['enabled'])) {
        return;
    }

    // 2. Check display rules
    $locations = $options['display_locations'] ?? [];
    $show_widget = false;

    if (is_front_page() && !empty($locations['homepage']))   $show_widget = true;
    if (is_singular('post') && !empty($locations['posts']))  $show_widget = true;
    if (is_singular('page') && !empty($locations['pages']))   $show_widget = true;
    if (is_archive() && !empty($locations['archives']))     $show_widget = true;
    if (is_category() && !empty($locations['categories']))   $show_widget = true;
    if (is_404() && !empty($locations['e404_page']))        $show_widget = true;

    // Check for custom post types
    if (!$show_widget && is_singular() && !empty($locations['cpt'])) {
        $cpt = get_post_type();
        if (isset($locations['cpt'][$cpt]) && $locations['cpt'][$cpt]) {
            $show_widget = true;
        }
    }

    // If no rules match, do not show the widget
    if (!$show_widget) {
        return;
    }

    // 3. Build and output the script tag
    $data_attrs = '';
    $exclude_from_data = ['enabled', 'display_locations']; // Don't add these as data attributes

    foreach ($options as $key => $value) {
        if (!empty($value) && !in_array($key, $exclude_from_data)) {
            $attr_name = 'data-' . str_replace('_', '-', $key);
            $data_attrs .= sprintf('%s="%s" ', esc_attr($attr_name), esc_attr($value));
        }
    }

    printf(
        '<script src="https://dev-chat-widget.virage.ai/cdn/chat-widget-sdk-v1.min.js" %s async defer></script>',
        $data_attrs
    );
}
add_action('wp_footer', 'virage_ai_add_widget_script');
