<?php
/**
 * Die öffentlich zugänglichen Funktionen des Plugins
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Event_Attendance
 * @subpackage Event_Attendance/public
 */

/**
 * Die öffentlich zugänglichen Funktionen des Plugins
 *
 * @package    Event_Attendance
 * @subpackage Event_Attendance/public
 * @author     Claude
 */
class Event_Attendance_Public {

    /**
     * Public Styles laden
     */
    public function enqueue_styles() {
        // Styles immer laden, aber Widget-Inhalte nur für eingeloggte Benutzer anzeigen
        wp_enqueue_style(
            'event-attendance-public',
            EVENT_ATTENDANCE_PLUGIN_URL . 'public/css/event-attendance-public.css',
            [],
            EVENT_ATTENDANCE_VERSION,
            'all'
        );
    }

    /**
     * Public Scripts laden
     */
    public function enqueue_scripts() {
        // Scripts immer laden, aber Widget-Funktionalität nur für eingeloggte Benutzer aktivieren
        wp_enqueue_script(
            'event-attendance-public',
            EVENT_ATTENDANCE_PLUGIN_URL . 'public/js/event-attendance-public.js',
            ['jquery'],
            EVENT_ATTENDANCE_VERSION,
            true
        );

        // Localize Script für AJAX
        wp_localize_script(
            'event-attendance-public',
            'event_attendance_ajax',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('event_attendance_nonce'),
                'is_logged_in' => is_user_logged_in() ? 'yes' : 'no'
            ]
        );
    }

    /**
     * AJAX-Handler: Teilnahmestatus aktualisieren
     */
    public function update_attendance() {
        // Sicherheitsprüfung
        check_ajax_referer('event_attendance_nonce', 'nonce');
        
        // Nur für eingeloggte Benutzer
        if (!is_user_logged_in()) {
            $response = [
                'success' => false,
                'data' => ['message' => __('Du musst angemeldet sein, um diese Aktion durchzuführen', 'event-attendance')]
            ];
            
            wp_send_json($response);
            exit;
        }
        
        // Daten validieren
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $participant_id = isset($_POST['participant_id']) ? intval($_POST['participant_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';
        
        // Wenn kein participant_id angegeben ist, versuchen, den des aktuellen Benutzers zu bekommen
        if (empty($participant_id)) {
            $current_user_id = get_current_user_id();
            $participant_id = $this->get_participant_id_by_user_id($current_user_id);
            
            // Wenn der Benutzer noch kein Teilnehmer ist, einen neuen Teilnehmer erstellen
            if (!$participant_id) {
                $current_user = wp_get_current_user();
                $participant_id = $this->create_participant_from_user($current_user);
                
                if (!$participant_id) {
                    $response = [
                        'success' => false,
                        'data' => ['message' => __('Teilnehmerprofil konnte nicht erstellt werden', 'event-attendance')]
                    ];
                    wp_send_json($response);
                    exit;
                }
            }
        }
        
        if (empty($event_id) || empty($participant_id) || empty($status)) {
            $response = [
                'success' => false,
                'data' => ['message' => __('Pflichtfelder fehlen', 'event-attendance')]
            ];
            wp_send_json($response);
            exit;
        }
        
        // Gültige Status-Werte überprüfen
        $valid_statuses = ['attending', 'declined_sick', 'declined_vacation', 'declined_business'];
        if (!in_array($status, $valid_statuses)) {
            $response = [
                'success' => false,
                'data' => ['message' => __('Ungültiger Status-Wert', 'event-attendance')]
            ];
            wp_send_json($response);
            exit;
        }
        
        // Prüfen, ob Event und Participant existieren
        if (!$this->event_exists($event_id) || !$this->participant_exists($participant_id)) {
            $response = [
                'success' => false,
                'data' => ['message' => __('Termin oder Teilnehmer existiert nicht', 'event-attendance')]
            ];
            wp_send_json($response);
            exit;
        }
        
        // Status in der Datenbank aktualisieren oder hinzufügen
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_status';
        
        // Prüfen, ob bereits ein Eintrag existiert
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE event_id = %d AND participant_id = %d",
            $event_id,
            $participant_id
        ));
        
        if ($existing) {
            // Aktualisieren des bestehenden Eintrags
            $result = $wpdb->update(
                $table_name,
                [
                    'status' => $status,
                    'comment' => $comment,
                ],
                [
                    'event_id' => $event_id,
                    'participant_id' => $participant_id,
                ],
                ['%s', '%s'],
                ['%d', '%d']
            );
        } else {
            // Neuen Eintrag erstellen
            $result = $wpdb->insert(
                $table_name,
                [
                    'event_id' => $event_id,
                    'participant_id' => $participant_id,
                    'status' => $status,
                    'comment' => $comment,
                ],
                ['%d', '%d', '%s', '%s']
            );
        }
        
        if ($result === false) {
            $response = [
                'success' => false,
                'data' => ['message' => __('Fehler beim Aktualisieren des Teilnahmestatus', 'event-attendance')]
            ];
            wp_send_json($response);
            exit;
        }
        
        // Formatierte Nachricht je nach Status
        $message = '';
        switch ($status) {
            case 'attending':
                $message = __('Zusage bestätigt', 'event-attendance');
                break;
            case 'declined_sick':
                $message = __('Abwesenheit wegen Krankheit vermerkt', 'event-attendance');
                break;
            case 'declined_vacation':
                $message = __('Abwesenheit wegen Urlaub vermerkt', 'event-attendance');
                break;
            case 'declined_business':
                $message = __('Abwesenheit wegen Dienstreise vermerkt', 'event-attendance');
                break;
        }
        
        $response = [
            'success' => true,
            'data' => [
                'message' => $message,
                'status' => $status
            ]
        ];
        
        wp_send_json($response);
        exit;
    }

    /**
     * Shortcode-Funktion: [event_attendance]
     */
    public function event_attendance_shortcode($atts) {
        // Nur für eingeloggte Benutzer
        if (!is_user_logged_in()) {
            return '<p>' . __('Bitte melde dich an, um Terminteilnahmen anzuzeigen und zu verwalten.', 'event-attendance') . '</p>';
        }
        
        // Attribute verarbeiten
        $atts = shortcode_atts(
            [
                'event_id' => 0,
                'limit' => 5,
                'show_past' => 'no',
            ],
            $atts,
            'event_attendance'
        );
        
        $event_id = intval($atts['event_id']);
        $limit = intval($atts['limit']);
        $show_past = ($atts['show_past'] === 'yes');
        
        // Teilnehmer-ID des aktuellen Benutzers abrufen
        $current_user_id = get_current_user_id();
        $participant_id = $this->get_participant_id_by_user_id($current_user_id);
        
        // Wenn event_id angegeben ist, zeige Details zu diesem Event
        if ($event_id > 0) {
            return $this->render_event_detail($event_id, $participant_id);
        }
        
        // Andernfalls zeige Liste der kommenden Events
        return $this->render_event_list($limit, $show_past, $participant_id);
    }

    /**
     * Event-Detailansicht rendern
     */
    private function render_event_detail($event_id, $participant_id) {
        global $wpdb;
        $table_events = $wpdb->prefix . 'event_attendance_events';
        
        // Event-Details abrufen
        $event = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_events WHERE id = %d",
            $event_id
        ));
        
        if (!$event) {
            return '<p>' . __('Termin nicht gefunden.', 'event-attendance') . '</p>';
        }
        
        // Teilnahmestatus des aktuellen Benutzers abrufen
        $status = $this->get_attendance_status($event_id, $participant_id);
        
        // Teilnehmerliste abrufen
        $attendees = $this->get_event_attendees($event_id);
        
        // View für Event-Details laden
        ob_start();
        include EVENT_ATTENDANCE_PLUGIN_DIR . 'public/partials/event-detail.php';
        return ob_get_clean();
    }
    
    /**
     * Event-Liste rendern
     */
    private function render_event_list($limit, $show_past, $participant_id) {
        global $wpdb;
        $table_events = $wpdb->prefix . 'event_attendance_events';
        
        // SQL für kommende oder alle Events
        $where_clause = $show_past ? "" : "WHERE date >= CURDATE()";
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_events $where_clause ORDER BY date ASC LIMIT %d",
            $limit
        ));
        
        if (empty($events)) {
            return '<p>' . __('Keine bevorstehenden Termine gefunden.', 'event-attendance') . '</p>';
        }
        
        // Für jedes Event den Teilnahmestatus des aktuellen Benutzers abrufen
        foreach ($events as $event) {
            $event->status = $this->get_attendance_status($event->id, $participant_id);
        }
        
        // View für Event-Liste laden
        ob_start();
        include EVENT_ATTENDANCE_PLUGIN_DIR . 'public/partials/event-list.php';
        return ob_get_clean();
    }
    
    /**
     * Hilfsfunktion: Teilnehmer-ID nach Benutzer-ID abrufen
     */
    private function get_participant_id_by_user_id($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_participants';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Hilfsfunktion: Benutzer als Teilnehmer registrieren
     */
    private function create_participant_from_user($user) {
        if (!$user || !$user->ID) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_participants';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
            ],
            ['%d', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Hilfsfunktion: Prüfen, ob Event existiert
     */
    private function event_exists($event_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_events';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE id = %d",
            $event_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Hilfsfunktion: Prüfen, ob Teilnehmer existiert
     */
    private function participant_exists($participant_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_participants';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE id = %d",
            $participant_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Hilfsfunktion: Teilnahmestatus eines Teilnehmers für ein Event abrufen
     */
    private function get_attendance_status($event_id, $participant_id) {
        if (!$event_id || !$participant_id) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_status';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE event_id = %d AND participant_id = %d",
            $event_id,
            $participant_id
        ));
    }
    
    /**
     * Hilfsfunktion: Teilnehmerliste für ein Event abrufen
     */
    private function get_event_attendees($event_id) {
        global $wpdb;
        $table_attendance = $wpdb->prefix . 'event_attendance_status';
        $table_participants = $wpdb->prefix . 'event_attendance_participants';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.name, p.email, a.status, a.comment 
             FROM $table_attendance a 
             JOIN $table_participants p ON a.participant_id = p.id 
             WHERE a.event_id = %d 
             ORDER BY p.name ASC",
            $event_id
        ));
    }
}