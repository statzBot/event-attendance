<?php
/**
 * Das Widget für Terminteilnahme
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Event_Attendance
 * @subpackage Event_Attendance/widgets
 */

/**
 * Das Widget für Terminteilnahme
 *
 * @package    Event_Attendance
 * @subpackage Event_Attendance/widgets
 * @author     Claude
 */
class Event_Attendance_Widget extends WP_Widget {

    /**
     * Registriert das Widget bei WordPress
     */
    public function __construct() {
        parent::__construct(
            'event_attendance_widget', // Basis-ID
            __('Terminzusagen', 'event-attendance'), // Name
            [
                'description' => __('Zeigt bevorstehende Termine an und ermöglicht Benutzern, ihre Teilnahme zu bestätigen oder abzusagen', 'event-attendance'),
            ]
        );
    }

    /**
     * Frontend des Widgets
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : __('Terminzusagen', 'event-attendance');
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        // Nur Inhalte anzeigen, wenn Benutzer eingeloggt ist
        if (!is_user_logged_in()) {
            echo '<p>' . __('Bitte melde dich an, um bevorstehende Termine zu sehen und deine Teilnahme zu verwalten.', 'event-attendance') . '</p>';
            echo $args['after_widget'];
            return;
        }

        $num_events = !empty($instance['num_events']) ? absint($instance['num_events']) : 3;
        
        // Kommende Termine anzeigen
        $this->display_upcoming_events($num_events);
        
        echo $args['after_widget'];
    }

    /**
     * Backend-Formular des Widgets
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Terminzusagen', 'event-attendance');
        $num_events = !empty($instance['num_events']) ? absint($instance['num_events']) : 3;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Titel:', 'event-attendance'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('num_events')); ?>"><?php esc_html_e('Anzahl der anzuzeigenden Termine:', 'event-attendance'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('num_events')); ?>" name="<?php echo esc_attr($this->get_field_name('num_events')); ?>" type="number" step="1" min="1" value="<?php echo esc_attr($num_events); ?>" size="3">
        </p>
        <?php
    }

    /**
     * Speichert die Widget-Einstellungen
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['num_events'] = (!empty($new_instance['num_events'])) ? absint($new_instance['num_events']) : 3;

        return $instance;
    }

    /**
     * Zeigt kommende Termine im Widget an
     */
    private function display_upcoming_events($num_events) {
        // Sicherstellen, dass die Datenbanktabellen existieren
        global $wpdb;
        $table_events = $wpdb->prefix . 'event_attendance_events';
        
        // Prüfen, ob die Tabelle existiert
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_events'") === $table_events;
        
        if (!$table_exists) {
            echo '<p>' . __('Die Plugin-Tabellen sind nicht korrekt eingerichtet. Bitte deaktiviere und reaktiviere das Plugin.', 'event-attendance') . '</p>';
            return;
        }
        
        // Anstehende Termine abrufen
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_events 
             WHERE date >= CURDATE() 
             ORDER BY date ASC 
             LIMIT %d",
            $num_events
        ));
        
        if (empty($events)) {
            echo '<p>' . __('Keine bevorstehenden Termine gefunden.', 'event-attendance') . '</p>';
            return;
        }
        
        // Aktuelle Benutzer-ID abrufen
        $current_user_id = get_current_user_id();
        
        // Teilnehmer-ID des Benutzers abrufen oder erstellen
        $participant_id = $this->get_or_create_participant_id($current_user_id);
        
        if (!$participant_id) {
            echo '<p>' . __('Fehler beim Abrufen des Teilnehmerprofils.', 'event-attendance') . '</p>';
            return;
        }
        
        // Teilnahmestatus für diese Events abrufen
        $statuses = $this->get_attendance_statuses($events, $participant_id);
        
        echo '<div class="event-attendance-widget">';
        
        foreach ($events as $event) {
            $event_date = new DateTime($event->date);
            $status = isset($statuses[$event->id]) ? $statuses[$event->id] : null;
            
            echo '<div class="event-item">';
            echo '<h4>' . esc_html($event->title) . '</h4>';
            echo '<p class="event-date">' . $event_date->format('d.m.Y H:i') . '</p>';
            echo '<p class="event-location">' . esc_html($event->location) . '</p>';
            
            // Buttons zur Zu-/Absage
            echo $this->render_attendance_buttons($event->id, $participant_id, $status);
            
            // Liste der Teilnehmer für diesen Termin anzeigen, nach Status gruppiert
            echo $this->render_grouped_attendees_list($event->id);
            
            echo '</div>';
        }
        
        echo '</div>';
        
        // JavaScript für AJAX-Funktionalität einbinden
        $this->enqueue_scripts();
    }
    
    /**
     * Rendert die Zu-/Absage-Buttons für ein Event
     */
    private function render_attendance_buttons($event_id, $participant_id, $status) {
        $output = '<div class="attendance-buttons" data-event-id="' . esc_attr($event_id) . '" data-participant-id="' . esc_attr($participant_id) . '">';
        
        // Aktueller Status
        $current_status = $status ? $status->status : '';
        
        // Button für Zusage
        $attending_class = ($current_status === 'attending') ? 'active' : '';
        $output .= '<button type="button" class="attendance-button ' . $attending_class . '" data-status="attending">' . __('Zusage', 'event-attendance') . '</button>';
        
        // Button für Absage (krank)
        $sick_class = ($current_status === 'declined_sick') ? 'active' : '';
        $output .= '<button type="button" class="attendance-button ' . $sick_class . '" data-status="declined_sick">' . __('Krank', 'event-attendance') . '</button>';
        
        // Button für Absage (Urlaub)
        $vacation_class = ($current_status === 'declined_vacation') ? 'active' : '';
        $output .= '<button type="button" class="attendance-button ' . $vacation_class . '" data-status="declined_vacation">' . __('Urlaub', 'event-attendance') . '</button>';
        
        // Button für Absage (Dienstreise)
        $business_class = ($current_status === 'declined_business') ? 'active' : '';
        $output .= '<button type="button" class="attendance-button ' . $business_class . '" data-status="declined_business">' . __('Dienstreise', 'event-attendance') . '</button>';
        
        // Kommentarfeld
        $comment = $status ? esc_attr($status->comment) : '';
        $output .= '<div class="attendance-comment">';
        $output .= '<textarea placeholder="' . __('Kommentar (optional)', 'event-attendance') . '">' . $comment . '</textarea>';
        $output .= '</div>';
        
        // Feedback-Nachricht
        $output .= '<div class="attendance-feedback"></div>';
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Rendert eine Liste der Teilnehmer für ein Event, gruppiert nach Status
     */
    private function render_grouped_attendees_list($event_id) {
        global $wpdb;
        $table_attendance = $wpdb->prefix . 'event_attendance_status';
        $table_participants = $wpdb->prefix . 'event_attendance_participants';
        
        // Alle Teilnehmer mit ihrem Status abrufen
        $all_attendees = $wpdb->get_results($wpdb->prepare(
            "SELECT p.name, a.status, a.comment
             FROM $table_attendance a 
             JOIN $table_participants p ON a.participant_id = p.id 
             WHERE a.event_id = %d
             ORDER BY p.name ASC",
            $event_id
        ));
        
        $output = '<div class="attendees-list">';
        
        if (empty($all_attendees)) {
            $output .= '<p class="no-attendees">' . __('Noch keine Teilnehmer bestätigt.', 'event-attendance') . '</p>';
            $output .= '</div>';
            return $output;
        }
        
        // Gruppierung der Teilnehmer nach Status
        $attending = [];
        $declined_sick = [];
        $declined_vacation = [];
        $declined_business = [];
        
        foreach ($all_attendees as $attendee) {
            switch ($attendee->status) {
                case 'attending':
                    $attending[] = $attendee;
                    break;
                case 'declined_sick':
                    $declined_sick[] = $attendee;
                    break;
                case 'declined_vacation':
                    $declined_vacation[] = $attendee;
                    break;
                case 'declined_business':
                    $declined_business[] = $attendee;
                    break;
            }
        }
        
        // Zusagen anzeigen
        if (!empty($attending)) {
            $output .= '<div class="attendees-group attending-group">';
            $output .= '<h5>' . __('Zusagen:', 'event-attendance') . '</h5>';
            $output .= '<ul>';
            foreach ($attending as $attendee) {
                $output .= '<li>' . esc_html($attendee->name);
                if (!empty($attendee->comment)) {
                    $output .= ' <span class="comment">(' . esc_html($attendee->comment) . ')</span>';
                }
                $output .= '</li>';
            }
            $output .= '</ul>';
            $output .= '</div>';
        }
        
        // Absagen gruppieren
        $has_declines = !empty($declined_sick) || !empty($declined_vacation) || !empty($declined_business);
        
        if ($has_declines) {
            $output .= '<div class="declines-group">';
            $output .= '<h5>' . __('Absagen:', 'event-attendance') . '</h5>';
            
            // Krank
            if (!empty($declined_sick)) {
                $output .= '<div class="decline-reason">';
                $output .= '<h6>' . __('Krank:', 'event-attendance') . '</h6>';
                $output .= '<ul>';
                foreach ($declined_sick as $attendee) {
                    $output .= '<li>' . esc_html($attendee->name);
                    if (!empty($attendee->comment)) {
                        $output .= ' <span class="comment">(' . esc_html($attendee->comment) . ')</span>';
                    }
                    $output .= '</li>';
                }
                $output .= '</ul>';
                $output .= '</div>';
            }
            
            // Urlaub
            if (!empty($declined_vacation)) {
                $output .= '<div class="decline-reason">';
                $output .= '<h6>' . __('Urlaub:', 'event-attendance') . '</h6>';
                $output .= '<ul>';
                foreach ($declined_vacation as $attendee) {
                    $output .= '<li>' . esc_html($attendee->name);
                    if (!empty($attendee->comment)) {
                        $output .= ' <span class="comment">(' . esc_html($attendee->comment) . ')</span>';
                    }
                    $output .= '</li>';
                }
                $output .= '</ul>';
                $output .= '</div>';
            }
            
            // Dienstreise
            if (!empty($declined_business)) {
                $output .= '<div class="decline-reason">';
                $output .= '<h6>' . __('Dienstreise:', 'event-attendance') . '</h6>';
                $output .= '<ul>';
                foreach ($declined_business as $attendee) {
                    $output .= '<li>' . esc_html($attendee->name);
                    if (!empty($attendee->comment)) {
                        $output .= ' <span class="comment">(' . esc_html($attendee->comment) . ')</span>';
                    }
                    $output .= '</li>';
                }
                $output .= '</ul>';
                $output .= '</div>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Rendert eine einfache Liste der Teilnehmer für ein Event (Legacy-Funktion)
     */
    private function render_attendees_list($event_id) {
        // Umleitung zur neuen Gruppierten Darstellung
        return $this->render_grouped_attendees_list($event_id);
    }
    
    /**
     * Lädt die Widget-spezifischen Skripte
     */
    private function enqueue_scripts() {
        // Keine inline Skripte oder Styles, alles wird über wp_enqueue_style/script geladen
    }
    
    /**
     * Gibt die Teilnehmer-ID zurück oder erstellt einen neuen Teilnehmer
     */
    private function get_or_create_participant_id($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_participants';
        
        // Existierenden Teilnehmer suchen
        $participant_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        // Wenn gefunden, zurückgeben
        if ($participant_id) {
            return $participant_id;
        }
        
        // Sonst neuen Teilnehmer anlegen
        $current_user = wp_get_current_user();
        
        if (!$current_user || !$current_user->ID) {
            return false;
        }
        
        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $current_user->ID,
                'name' => $current_user->display_name,
                'email' => $current_user->user_email,
            ],
            ['%d', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Teilnahme-Status für mehrere Events abrufen
     */
    private function get_attendance_statuses($events, $participant_id) {
        if (empty($events) || !$participant_id) {
            return [];
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'event_attendance_status';
        
        // Event-IDs extrahieren
        $event_ids = array_map(function($event) {
            return $event->id;
        }, $events);
        
        $event_ids_string = implode(',', array_map('intval', $event_ids));
        
        // Status für alle Events abrufen
        $statuses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                 WHERE event_id IN ($event_ids_string) 
                 AND participant_id = %d",
                $participant_id
            ),
            OBJECT_K
        );
        
        // Nach Event-ID indizieren
        $result = [];
        foreach ($statuses as $status) {
            $result[$status->event_id] = $status;
        }
        
        return $result;
    }
}