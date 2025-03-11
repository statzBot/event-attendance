<?php
/**
 * Die Admin-spezifischen Funktionen des Plugins
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Event_Attendance
 * @subpackage Event_Attendance/admin
 */

/**
 * Die Admin-spezifischen Funktionen des Plugins
 *
 * @package    Event_Attendance
 * @subpackage Event_Attendance/admin
 * @author     Claude
 */
class Event_Attendance_Admin {

    /**
     * Admin-Menü und Unterseiten hinzufügen
     */
    public function add_admin_menu() {
        // Hauptmenüpunkt
        add_menu_page(
            __('Event Attendance', 'event-attendance'),
            __('Event Attendance', 'event-attendance'),
            'manage_event_attendance',
            'event-attendance',
            [$this, 'display_events_page'],
            'dashicons-calendar-alt',
            30
        );

        // Unterseite für Termine
        add_submenu_page(
            'event-attendance',
            __('Events', 'event-attendance'),
            __('Events', 'event-attendance'),
            'manage_event_attendance',
            'event-attendance',
            [$this, 'display_events_page']
        );

        // Unterseite für Teilnehmer
        add_submenu_page(
            'event-attendance',
            __('Participants', 'event-attendance'),
            __('Participants', 'event-attendance'),
            'manage_event_attendance',
            'event-attendance-participants',
            [$this, 'display_participants_page']
        );

        // Unterseite für wiederkehrende Termine
        add_submenu_page(
            'event-attendance',
            __('Recurring Events', 'event-attendance'),
            __('Recurring Events', 'event-attendance'),
            'manage_event_attendance',
            'event-attendance-recurring',
            [$this, 'display_recurring_events_page']
        );

        // Unterseite für Einstellungen
        add_submenu_page(
            'event-attendance',
            __('Settings', 'event-attendance'),
            __('Settings', 'event-attendance'),
            'manage_event_attendance',
            'event-attendance-settings',
            [$this, 'display_settings_page']
        );
    }

    /**
     * Admin-Styles laden
     */
    public function enqueue_styles($hook) {
        // Nur auf Plugin-Seiten laden
        if (strpos($hook, 'event-attendance') !== false) {
            wp_enqueue_style(
                'event-attendance-admin',
                EVENT_ATTENDANCE_PLUGIN_URL . 'admin/css/event-attendance-admin.css',
                [],
                EVENT_ATTENDANCE_VERSION,
                'all'
            );

            // Für Datepicker
            wp_enqueue_style('jquery-ui-datepicker');
        }
    }

    /**
     * Admin-Scripts laden
     */
    public function enqueue_scripts($hook) {
        // Nur auf Plugin-Seiten laden
        if (strpos($hook, 'event-attendance') !== false) {
            wp_enqueue_script(
                'event-attendance-admin',
                EVENT_ATTENDANCE_PLUGIN_URL . 'admin/js/event-attendance-admin.js',
                ['jquery', 'jquery-ui-datepicker'],
                EVENT_ATTENDANCE_VERSION,
                false
            );

            // Localize Script für AJAX
            wp_localize_script(
                'event-attendance-admin',
                'event_attendance_ajax',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('event_attendance_nonce'),
                ]
            );
        }
    }

    /**
     * Seite für Termine anzeigen
     */
    public function display_events_page() {
        // Prüfen, ob Benutzer Berechtigungen hat
        if (!current_user_can('manage_event_attendance')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'event-attendance'));
        }

        // Termine aus der Datenbank laden
        $events = $this->get_events();
        
        // View für Termine laden
        require_once EVENT_ATTENDANCE_PLUGIN_DIR . 'admin/partials/events-page.php';
    }

    /**
     * Seite für Teilnehmer anzeigen
     */
    public function display_participants_page() {
        // Prüfen, ob Benutzer Berechtigungen hat
        if (!current_user_can('manage_event_attendance')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'event-attendance'));
        }

        // Teilnehmer aus der Datenbank laden
        $participants = $this->get_participants();
        
        // View für Teilnehmer laden
        require_once EVENT_ATTENDANCE_PLUGIN_DIR . 'admin/partials/participants-page.php';
    }

    /**
     * Seite für wiederkehrende Termine anzeigen
     */
    public function display_recurring_events_page() {
        // Prüfen, ob Benutzer Berechtigungen hat
        if (!current_user_can('manage_event_attendance')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'event-attendance'));
        }
        
        // View für wiederkehrende Termine laden
        require_once EVENT_ATTENDANCE_PLUGIN_DIR . 'admin/partials/recurring-events-page.php';
    }

    /**
     * Seite für Einstellungen anzeigen
     */
    public function display_settings_page() {
        // Prüfen, ob Benutzer Berechtigungen hat
        if (!current_user_can('manage_event_attendance')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'event-attendance'));
        }
        
        // View für Einstellungen laden
        require_once EVENT_ATTENDANCE_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }

    /**
     * Alle Termine abrufen
     */
    private function get_events() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_events';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC");
    }

    /**
     * Alle Teilnehmer abrufen
     */
    private function get_participants() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_participants';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");
    }
    
    /**
     * Anzahl der Teilnehmer für ein Event abrufen
     */
    public function get_attendees_count($event_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_status';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE event_id = %d AND status = 'attending'",
            $event_id
        ));
    }
    
    /**
     * Anzahl der Events, an denen ein Teilnehmer teilnimmt
     */
    public function get_participant_events_count($participant_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_status';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE participant_id = %d AND status = 'attending'",
            $participant_id
        ));
    }

    /**
     * AJAX-Handler: Termin erstellen
     */
    public function create_event() {
        // Sicherheitsprüfung
        check_ajax_referer('event_attendance_nonce', 'nonce');
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_event_attendance')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'event-attendance')]);
            wp_die();
        }
        
        // Daten validieren
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        
        if (empty($title) || empty($date) || empty($location)) {
            wp_send_json_error(['message' => __('Required fields missing', 'event-attendance')]);
            wp_die();
        }
        
        // In Datenbank speichern
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_events';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'title' => $title,
                'date' => $date,
                'location' => $location,
                'description' => $description,
            ],
            ['%s', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Error creating event', 'event-attendance')]);
            wp_die();
        }
        
        $event_id = $wpdb->insert_id;
        
        wp_send_json_success([
            'message' => __('Event created successfully', 'event-attendance'),
            'event_id' => $event_id,
        ]);
        
        wp_die();
    }

    /**
     * AJAX-Handler: Termin aktualisieren
     */
    public function update_event() {
        // Sicherheitsprüfung
        check_ajax_referer('event_attendance_nonce', 'nonce');
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_event_attendance')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'event-attendance')]);
            wp_die();
        }
        
        // Daten validieren
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        
        if (empty($event_id) || empty($title) || empty($date) || empty($location)) {
            wp_send_json_error(['message' => __('Required fields missing', 'event-attendance')]);
            wp_die();
        }
        
        // In Datenbank aktualisieren
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_events';
        
        $result = $wpdb->update(
            $table_name,
            [
                'title' => $title,
                'date' => $date,
                'location' => $location,
                'description' => $description,
            ],
            ['id' => $event_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Error updating event', 'event-attendance')]);
            wp_die();
        }
        
        wp_send_json_success(['message' => __('Event updated successfully', 'event-attendance')]);
        wp_die();
    }

    /**
     * AJAX-Handler: Termin löschen
     */
    public function delete_event() {
        // Sicherheitsprüfung
        check_ajax_referer('event_attendance_nonce', 'nonce');
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_event_attendance')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'event-attendance')]);
            wp_die();
        }
        
        // Daten validieren
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (empty($event_id)) {
            wp_send_json_error(['message' => __('Event ID required', 'event-attendance')]);
            wp_die();
        }
        
        // Aus Datenbank löschen
        global $wpdb;
        $table_events = $wpdb->prefix . 'event_attendance_events';
        $table_attendance = $wpdb->prefix . 'event_attendance_status';
        
        // Zuerst die zugehörigen Teilnahme-Einträge löschen
        $wpdb->delete(
            $table_attendance,
            ['event_id' => $event_id],
            ['%d']
        );
        
        // Dann den Termin löschen
        $result = $wpdb->delete(
            $table_events,
            ['id' => $event_id],
            ['%d']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Error deleting event', 'event-attendance')]);
            wp_die();
        }
        
        wp_send_json_success(['message' => __('Event deleted successfully', 'event-attendance')]);
        wp_die();
    }

    /**
     * AJAX-Handler: Teilnehmer erstellen
     */
    public function create_participant() {
        // Sicherheitsprüfung
        check_ajax_referer('event_attendance_nonce', 'nonce');
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_event_attendance')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'event-attendance')]);
            wp_die();
        }
        
        // Daten validieren
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (empty($name) || empty($email)) {
            wp_send_json_error(['message' => __('Required fields missing', 'event-attendance')]);
            wp_die();
        }
        
        // In Datenbank speichern
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_participants';
        
        // Prüfen, ob Teilnehmer bereits existiert
        if ($user_id > 0) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                $user_id
            ));
            
            if ($exists > 0) {
                wp_send_json_error(['message' => __('Participant already exists for this user', 'event-attendance')]);
                wp_die();
            }
        }
        
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'name' => $name,
                'email' => $email,
            ],
            ['%d', '%s', '%s']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Error creating participant', 'event-attendance')]);
            wp_die();
        }
        
        $participant_id = $wpdb->insert_id;
        
        wp_send_json_success([
            'message' => __('Participant created successfully', 'event-attendance'),
            'participant_id' => $participant_id,
        ]);
        
        wp_die();
    }

    /**
     * AJAX-Handler: Teilnehmer aktualisieren
     */
    public function update_participant() {
        // Sicherheitsprüfung
        check_ajax_referer('event_attendance_nonce', 'nonce');
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_event_attendance')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'event-attendance')]);
            wp_die();
        }
        
        // Daten validieren
        $participant_id = isset($_POST['participant_id']) ? intval($_POST['participant_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (empty($participant_id) || empty($name) || empty($email)) {
            wp_send_json_error(['message' => __('Required fields missing', 'event-attendance')]);
            wp_die();
        }
        
        // In Datenbank aktualisieren
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_participants';
        
        $result = $wpdb->update(
            $table_name,
            [
                'name' => $name,
                'email' => $email,
            ],
            ['id' => $participant_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Error updating participant', 'event-attendance')]);
            wp_die();
        }
        
        wp_send_json_success(['message' => __('Participant updated successfully', 'event-attendance')]);
        wp_die();
    }

    /**
     * AJAX-Handler: Teilnehmer löschen
     */
    public function delete_participant() {
        // Sicherheitsprüfung
        check_ajax_referer('event_attendance_nonce', 'nonce');
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_event_attendance')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'event-attendance')]);
            wp_die();
        }
        
        // Daten validieren
        $participant_id = isset($_POST['participant_id']) ? intval($_POST['participant_id']) : 0;
        
        if (empty($participant_id)) {
            wp_send_json_error(['message' => __('Participant ID required', 'event-attendance')]);
            wp_die();
        }
        
        // Aus Datenbank löschen
        global $wpdb;
        $table_participants = $wpdb->prefix . 'event_attendance_participants';
        $table_attendance = $wpdb->prefix . 'event_attendance_status';
        
        // Zuerst die zugehörigen Teilnahme-Einträge löschen
        $wpdb->delete(
            $table_attendance,
            ['participant_id' => $participant_id],
            ['%d']
        );
        
        // Dann den Teilnehmer löschen
        $result = $wpdb->delete(
            $table_participants,
            ['id' => $participant_id],
            ['%d']
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Error deleting participant', 'event-attendance')]);
            wp_die();
        }
        
        wp_send_json_success(['message' => __('Participant deleted successfully', 'event-attendance')]);
        wp_die();
    }

    /**
     * AJAX-Handler: Teilnehmerliste für ein Event abrufen
     */
    public function get_event_attendees() {
        // Sicherheitsprüfung
        check_ajax_referer('event_attendance_nonce', 'nonce');
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_event_attendance')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'event-attendance')]);
            wp_die();
        }
        
        // Daten validieren
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (empty($event_id)) {
            wp_send_json_error(['message' => __('Event ID required', 'event-attendance')]);
            wp_die();
        }
        
        // Teilnehmer abrufen
        global $wpdb;
        $table_attendance = $wpdb->prefix . 'event_attendance_status';
        $table_participants = $wpdb->prefix . 'event_attendance_participants';
        
        $attendees = $wpdb->get_results($wpdb->prepare(
            "SELECT p.name, p.email, a.status, a.comment 
             FROM $table_attendance a 
             JOIN $table_participants p ON a.participant_id = p.id 
             WHERE a.event_id = %d 
             ORDER BY p.name ASC",
            $event_id
        ));
        
        wp_send_json_success(['attendees' => $attendees]);
        wp_die();
    }
    
    /**
     * AJAX-Handler: Wiederkehrende Termine erstellen
     */
    public function create_recurring_events() {
        // Sicherheitsprüfung
        check_ajax_referer('event_attendance_nonce', 'nonce');
        
        // Berechtigungsprüfung
        if (!current_user_can('manage_event_attendance')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'event-attendance')]);
            wp_die();
        }
        
        // Daten validieren
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $interval = isset($_POST['interval']) ? intval($_POST['interval']) : 1;
        $day_of_week = isset($_POST['day_of_week']) ? intval($_POST['day_of_week']) : -1;
        
        if (empty($title) || empty($start_date) || empty($end_date) || empty($location)) {
            wp_send_json_error(['message' => __('Required fields missing', 'event-attendance')]);
            wp_die();
        }
        
        // Startdatum und Enddatum in DateTime-Objekte umwandeln
        $start_datetime = new DateTime($start_date);
        $end_datetime = new DateTime($end_date);
        
        // Prüfen, ob Enddatum nach Startdatum liegt
        if ($end_datetime <= $start_datetime) {
            wp_send_json_error(['message' => __('End date must be after start date', 'event-attendance')]);
            wp_die();
        }
        
        // Termine generieren
        $events = [];
        $current_date = clone $start_datetime;
        
        // Ist ein bestimmter Wochentag angegeben?
        if ($day_of_week >= 0 && $day_of_week <= 6) {
            // Auf den ersten passenden Wochentag setzen
            while ((int)$current_date->format('w') !== $day_of_week) {
                $current_date->modify('+1 day');
            }
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_events';
        $created_count = 0;
        
        // Wiederkehrende Termine erstellen
        while ($current_date <= $end_datetime) {
            $event_date = $current_date->format('Y-m-d H:i:s');
            
            // In Datenbank speichern
            $result = $wpdb->insert(
                $table_name,
                [
                    'title' => $title,
                    'date' => $event_date,
                    'location' => $location,
                    'description' => $description,
                ],
                ['%s', '%s', '%s', '%s']
            );
            
            if ($result !== false) {
                $created_count++;
                $events[] = [
                    'id' => $wpdb->insert_id,
                    'date' => $event_date,
                ];
            }
            
            // Nächstes Datum berechnen
            if ($interval == 1) {
                // Wenn der Intervall 1 ist, einfach eine Woche addieren
                $current_date->modify('+1 week');
            } elseif ($interval == 2) {
                // Für zweiwöchige Intervalle
                $current_date->modify('+2 weeks');
            } else {
                // Für andere Intervalle
                $interval_days = $interval * 7;
                $current_date->modify("+$interval_days days");
            }
        }
        
        wp_send_json_success([
            'message' => sprintf(__('%d recurring events created successfully', 'event-attendance'), $created_count),
            'events' => $events,
        ]);
        
        wp_die();
    }
}