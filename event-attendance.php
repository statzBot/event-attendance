<?php
/**
 * Plugin Name: Event Attendance
 * Plugin URI: https://example.com/event-attendance
 * Description: Ein Plugin zur Verwaltung von Terminen und Teilnehmern mit Widget zur Zu- und Absage
 * Version: 1.0.0
 * Author: Claude
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: event-attendance
 * Domain Path: /languages
 * Requires PHP: 8.0
 */

// Wenn direkt aufgerufen wird, abbrechen
if (!defined('WPINC')) {
    die;
}

// Definiere Konstanten
define('EVENT_ATTENDANCE_VERSION', '1.0.0');
define('EVENT_ATTENDANCE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EVENT_ATTENDANCE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Aktivierungs- und Deaktivierungshooks
register_activation_hook(__FILE__, 'event_attendance_activate');
register_deactivation_hook(__FILE__, 'event_attendance_deactivate');

/**
 * Plugin-Aktivierungsfunktion
 */
function event_attendance_activate() {
    // Tabellen erstellen
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Tabelle für Termine
    $table_events = $wpdb->prefix . 'event_attendance_events';
    $sql_events = "CREATE TABLE $table_events (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        date datetime NOT NULL,
        location varchar(255) NOT NULL,
        description text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Tabelle für Teilnehmer
    $table_participants = $wpdb->prefix . 'event_attendance_participants';
    $sql_participants = "CREATE TABLE $table_participants (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";

    // Tabelle für Teilnahme-Status
    $table_attendance = $wpdb->prefix . 'event_attendance_status';
    $sql_attendance = "CREATE TABLE $table_attendance (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        participant_id mediumint(9) NOT NULL,
        status varchar(20) NOT NULL, /* attending, declined_sick, declined_vacation, declined_business */
        comment text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY event_participant (event_id, participant_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_events);
    dbDelta($sql_participants);
    dbDelta($sql_attendance);

    // Füge Capabilities für das Plugin hinzu
    add_role('event_manager', 'Event Manager', [
        'read' => true,
        'manage_event_attendance' => true,
    ]);

    // Administrator die Rechte geben
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('manage_event_attendance');
    }
}

/**
 * Plugin-Deaktivierungsfunktion
 */
function event_attendance_deactivate() {
    // Bereinigen bei Deaktivierung, falls notwendig
    // Tabellen werden nicht gelöscht, um Datenverlust zu vermeiden
}

/**
 * Hauptklasse für das Plugin
 */
class Event_Attendance_Plugin {
    /**
     * Instance der Klasse
     */
    private static $instance = null;

    /**
     * Konstruktor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->register_widget();
    }

    /**
     * Singleton-Pattern: Eine Instance erhalten
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Abhängigkeiten laden
     */
    private function load_dependencies() {
        // Admin Funktionen
        require_once EVENT_ATTENDANCE_PLUGIN_DIR . 'admin/class-event-attendance-admin.php';
        
        // Public Funktionen
        require_once EVENT_ATTENDANCE_PLUGIN_DIR . 'public/class-event-attendance-public.php';
        
        // Widgets
        require_once EVENT_ATTENDANCE_PLUGIN_DIR . 'widgets/class-event-attendance-widget.php';
    }

    /**
     * Sprachdateien laden
     */
    private function set_locale() {
        add_action('plugins_loaded', function() {
            load_plugin_textdomain(
                'event-attendance',
                false,
                EVENT_ATTENDANCE_PLUGIN_DIR . 'languages/'
            );
        });
    }

    /**
     * Admin-Hooks definieren
     */
    private function define_admin_hooks() {
        $admin = new Event_Attendance_Admin();
        
        // Admin-Menü und Einstellungsseiten
        add_action('admin_menu', [$admin, 'add_admin_menu']);
        
        // Admin-Assets (CSS, JS)
        add_action('admin_enqueue_scripts', [$admin, 'enqueue_styles']);
        add_action('admin_enqueue_scripts', [$admin, 'enqueue_scripts']);

        // AJAX-Handler für Admin-Aktionen
        add_action('wp_ajax_create_event', [$admin, 'create_event']);
        add_action('wp_ajax_update_event', [$admin, 'update_event']);
        add_action('wp_ajax_delete_event', [$admin, 'delete_event']);
        add_action('wp_ajax_create_participant', [$admin, 'create_participant']);
        add_action('wp_ajax_update_participant', [$admin, 'update_participant']);
        add_action('wp_ajax_delete_participant', [$admin, 'delete_participant']);
        add_action('wp_ajax_create_recurring_events', [$admin, 'create_recurring_events']);
        add_action('wp_ajax_get_event_attendees', [$admin, 'get_event_attendees']);
    }

    /**
     * Public-Hooks definieren
     */
    private function define_public_hooks() {
        $public = new Event_Attendance_Public();
        
        // Öffentliche Assets (CSS, JS)
        add_action('wp_enqueue_scripts', [$public, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$public, 'enqueue_scripts']);

        // AJAX-Handler für öffentliche Aktionen
        add_action('wp_ajax_update_attendance', [$public, 'update_attendance']);
        
        // Shortcodes
        add_shortcode('event_attendance', [$public, 'event_attendance_shortcode']);
    }

    /**
     * Widget registrieren
     */
    private function register_widget() {
        add_action('widgets_init', function() {
            register_widget('Event_Attendance_Widget');
        });
    }
}

// Die Widget-Klasse laden
require_once EVENT_ATTENDANCE_PLUGIN_DIR . 'widgets/class-event-attendance-widget.php';

// Widget registrieren (unabhängig vom Login-Status)
function event_attendance_register_widget() {
    register_widget('Event_Attendance_Widget');
}
add_action('widgets_init', 'event_attendance_register_widget');

// Plugin initialisieren
function event_attendance_init() {
    // Hauptplugin-Funktionalität nur für eingeloggte Benutzer anzeigen
    if (is_user_logged_in() || is_admin()) {
        Event_Attendance_Plugin::get_instance();
    }
}
add_action('init', 'event_attendance_init');