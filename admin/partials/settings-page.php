<?php
/**
 * Admin-Ansicht für Plugin-Einstellungen
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Event_Attendance
 * @subpackage Event_Attendance/admin/partials
 */

// Direkter Zugriff verhindern
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="event-attendance-admin">
        <div class="settings-container">
            <form method="post" action="options.php">
                <?php 
                // Einstellungen registrieren, falls noch nicht geschehen
                if (!get_option('event_attendance_settings')) {
                    add_option('event_attendance_settings', [
                        'default_status' => 'attending',
                        'enable_email_notifications' => 'no',
                        'notification_email' => get_option('admin_email'),
                    ]);
                }
                
                // Einstellungen anzeigen
                settings_fields('event_attendance_settings_group');
                do_settings_sections('event_attendance_settings');
                
                // Einstellungen speichern
                submit_button(__('Save Settings', 'event-attendance'));
                ?>
            </form>
        </div>
        
        <div class="about-container">
            <h2><?php _e('About Event Attendance', 'event-attendance'); ?></h2>
            <p>
                <?php _e('The Event Attendance plugin allows users to confirm or decline attendance to events. It provides a convenient way to manage event participants and track attendance.', 'event-attendance'); ?>
            </p>
            
            <h3><?php _e('Usage', 'event-attendance'); ?></h3>
            <p>
                <?php _e('You can display the event attendance functionality on any page using the shortcode:', 'event-attendance'); ?>
                <code>[event_attendance]</code>
            </p>
            
            <p>
                <?php _e('Optional parameters:', 'event-attendance'); ?>
            </p>
            <ul>
                <li>
                    <code>event_id="123"</code> - <?php _e('Display details for a specific event', 'event-attendance'); ?>
                </li>
                <li>
                    <code>limit="10"</code> - <?php _e('Number of events to display (default: 5)', 'event-attendance'); ?>
                </li>
                <li>
                    <code>show_past="yes"</code> - <?php _e('Show past events (default: no)', 'event-attendance'); ?>
                </li>
            </ul>
            
            <p>
                <?php _e('Examples:', 'event-attendance'); ?>
            </p>
            <ul>
                <li>
                    <code>[event_attendance event_id="123"]</code> - <?php _e('Display details for event with ID 123', 'event-attendance'); ?>
                </li>
                <li>
                    <code>[event_attendance limit="10" show_past="yes"]</code> - <?php _e('Display 10 events including past events', 'event-attendance'); ?>
                </li>
            </ul>
            
            <h3><?php _e('Widget', 'event-attendance'); ?></h3>
            <p>
                <?php _e('The plugin also provides a widget that can be added to your sidebar for quick access to upcoming events and attendance management.', 'event-attendance'); ?>
            </p>
            <p>
                <?php _e('To add the widget, go to Appearance > Widgets and drag the "Event Attendance" widget to your desired sidebar.', 'event-attendance'); ?>
            </p>
        </div>
    </div>
</div>

<style type="text/css">
    .event-attendance-admin {
        margin-top: 20px;
        display: flex;
        flex-wrap: wrap;
    }
    
    .settings-container {
        flex: 1 1 60%;
        min-width: 300px;
        background: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        margin-right: 20px;
        margin-bottom: 20px;
    }
    
    .about-container {
        flex: 1 1 30%;
        min-width: 250px;
        background: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        margin-bottom: 20px;
    }
    
    @media (max-width: 782px) {
        .settings-container {
            margin-right: 0;
        }
    }
    
    .about-container code {
        background: #f0f0f1;
        padding: 2px 6px;
        border-radius: 3px;
    }
</style>

<?php
// Einstellungen registrieren
function event_attendance_register_settings() {
    register_setting(
        'event_attendance_settings_group',
        'event_attendance_settings',
        'event_attendance_sanitize_settings'
    );
    
    add_settings_section(
        'event_attendance_general_section',
        __('General Settings', 'event-attendance'),
        'event_attendance_general_section_callback',
        'event_attendance_settings'
    );
    
    add_settings_field(
        'default_status',
        __('Default Status', 'event-attendance'),
        'event_attendance_default_status_callback',
        'event_attendance_settings',
        'event_attendance_general_section'
    );
    
    add_settings_field(
        'enable_email_notifications',
        __('Email Notifications', 'event-attendance'),
        'event_attendance_email_notifications_callback',
        'event_attendance_settings',
        'event_attendance_general_section'
    );
    
    add_settings_field(
        'notification_email',
        __('Notification Email', 'event-attendance'),
        'event_attendance_notification_email_callback',
        'event_attendance_settings',
        'event_attendance_general_section'
    );
}

add_action('admin_init', 'event_attendance_register_settings');

// Beschreibung der allgemeinen Einstellungen
function event_attendance_general_section_callback() {
    echo '<p>' . __('Configure the general settings for the Event Attendance plugin.', 'event-attendance') . '</p>';
}

// Callback für Standard-Status
function event_attendance_default_status_callback() {
    $options = get_option('event_attendance_settings');
    $default_status = isset($options['default_status']) ? $options['default_status'] : 'attending';
    ?>
    <select name="event_attendance_settings[default_status]">
        <option value="attending" <?php selected($default_status, 'attending'); ?>>
            <?php _e('Attending', 'event-attendance'); ?>
        </option>
        <option value="none" <?php selected($default_status, 'none'); ?>>
            <?php _e('None (require explicit confirmation)', 'event-attendance'); ?>
        </option>
    </select>
    <p class="description">
        <?php _e('The default status for users when a new event is created.', 'event-attendance'); ?>
    </p>
    <?php
}

// Callback für E-Mail-Benachrichtigungen
function event_attendance_email_notifications_callback() {
    $options = get_option('event_attendance_settings');
    $enable_email_notifications = isset($options['enable_email_notifications']) ? $options['enable_email_notifications'] : 'no';
    ?>
    <label>
        <input type="checkbox" name="event_attendance_settings[enable_email_notifications]" value="yes" <?php checked($enable_email_notifications, 'yes'); ?>>
        <?php _e('Send email notifications when status changes', 'event-attendance'); ?>
    </label>
    <p class="description">
        <?php _e('If enabled, emails will be sent to the administrator when a participant changes their attendance status.', 'event-attendance'); ?>
    </p>
    <?php
}

// Callback für Benachrichtigungs-E-Mail
function event_attendance_notification_email_callback() {
    $options = get_option('event_attendance_settings');
    $notification_email = isset($options['notification_email']) ? $options['notification_email'] : get_option('admin_email');
    ?>
    <input type="email" name="event_attendance_settings[notification_email]" value="<?php echo esc_attr($notification_email); ?>" class="regular-text">
    <p class="description">
        <?php _e('The email address to receive notifications. Default is the admin email.', 'event-attendance'); ?>
    </p>
    <?php
}

// Einstellungen validieren
function event_attendance_sanitize_settings($input) {
    $output = [];
    
    // Standard-Status
    $output['default_status'] = isset($input['default_status']) && in_array($input['default_status'], ['attending', 'none']) 
        ? $input['default_status'] 
        : 'attending';
    
    // E-Mail-Benachrichtigungen
    $output['enable_email_notifications'] = isset($input['enable_email_notifications']) && $input['enable_email_notifications'] === 'yes'
        ? 'yes'
        : 'no';
    
    // Benachrichtigungs-E-Mail
    $output['notification_email'] = isset($input['notification_email']) && is_email($input['notification_email'])
        ? sanitize_email($input['notification_email'])
        : get_option('admin_email');
    
    return $output;
}
?>