/**
 * Öffentliches JavaScript für das Event Attendance Plugin
 *
 * @package   Event_Attendance
 * @author    Claude
 * @license   GPL-2.0+
 */
/* global event_attendance_ajax */

(function($) {
    'use strict';

    /**
     * Alle DOM-bezogenen Funktionen sollten innerhalb dieses Blocks ausgeführt werden,
     * um sicherzustellen, dass das Dokument bereit ist.
     */
    $(document).ready(function() {
        
        // Prüfen ob Benutzer eingeloggt ist
        if (event_attendance_ajax.is_logged_in !== 'yes') {
            // Wenn nicht eingeloggt, alle Buttons deaktivieren
            $('.attendance-button').prop('disabled', true);
            return;
        }
        
        // Teilnahmestatus aktualisieren
        $('.attendance-button').on('click', function() {
            var button = $(this);
            var buttonContainer = button.parent();
            var eventId = buttonContainer.data('event-id');
            var participantId = buttonContainer.data('participant-id');
            var status = button.data('status');
            var comment = buttonContainer.find('textarea').val();
            var feedback = buttonContainer.find('.attendance-feedback');
            
            // Setze alle Buttons zurück
            buttonContainer.find('.attendance-button').removeClass('active');
            
            // Markiere ausgewählten Button
            button.addClass('active');
            
            // AJAX-Request
            $.ajax({
                url: event_attendance_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_attendance',
                    nonce: event_attendance_ajax.nonce,
                    event_id: eventId,
                    participant_id: participantId,
                    status: status,
                    comment: comment
                },
                beforeSend: function() {
                    feedback.html('<span class="loading">Updating...</span>');
                },
                success: function(response) {
                    try {
                        // Stellen Sie sicher, dass die Antwort ein Objekt ist
                        if (typeof response !== 'object' && response !== null) {
                            response = JSON.parse(response);
                        }
                        
                        if (response.success) {
                            feedback.html('<span class="success">' + response.data.message + '</span>');
                            
                            // Seite nach kurzem Delay neu laden, um Teilnehmerliste zu aktualisieren
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            feedback.html('<span class="error">' + response.data.message + '</span>');
                        }
                    } catch (e) {
                        console.error('JSON parsing error:', e);
                        feedback.html('<span class="error">Error processing response</span>');
                    }
                },
                error: function() {
                    feedback.html('<span class="error">Error updating attendance</span>');
                }
            });
        });
        
    });

})(jQuery);