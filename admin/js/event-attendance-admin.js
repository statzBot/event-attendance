/**
 * Admin-JavaScript für das Event Attendance Plugin
 *
 * @package   Event_Attendance
 * @author    Claude
 * @license   GPL-2.0+
 */

(function($) {
    'use strict';

    /**
     * Alle DOM-bezogenen Funktionen sollten innerhalb dieses Blocks ausgeführt werden,
     * um sicherzustellen, dass das Dokument bereit ist.
     */
    $(document).ready(function() {
        
        // Datepicker für Datumsfelder initialisieren
        if ($.fn.datepicker) {
            $('.event-date-field').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        }
        
        // AJAX-Fehlermeldung anzeigen
        function showError(container, message) {
            container.removeClass('notice-success').addClass('notice-error')
                .html('<p>' + message + '</p>')
                .show();
        }
        
        // AJAX-Erfolgsmeldung anzeigen
        function showSuccess(container, message) {
            container.removeClass('notice-error').addClass('notice-success')
                .html('<p>' + message + '</p>')
                .show();
        }
        
        // Event-Formular absenden
        $('#event-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitButton = $('#event-submit');
            
            // Verhindern von doppeltem Absenden
            if (submitButton.prop('disabled')) {
                return false;
            }
            
            // Button deaktivieren
            submitButton.prop('disabled', true);
            
            var eventId = $('#event-id').val();
            var action = eventId == '0' ? 'create_event' : 'update_event';
            var messageContainer = $('#event-message');
            
            $.ajax({
                url: event_attendance_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: event_attendance_ajax.nonce,
                    event_id: eventId,
                    title: $('#event-title').val(),
                    date: $('#event-date').val(),
                    location: $('#event-location').val(),
                    description: $('#event-description').val()
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess(messageContainer, response.data.message);
                        
                        // Formular zurücksetzen und Seite neu laden
                        form[0].reset();
                        $('#event-id').val('0');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showError(messageContainer, response.data.message);
                        // Button wieder aktivieren bei Fehler
                        submitButton.prop('disabled', false);
                    }
                },
                error: function() {
                    showError(messageContainer, 'An error occurred. Please try again.');
                    // Button wieder aktivieren bei Fehler
                    submitButton.prop('disabled', false);
                }
            });
        });
        
        // Teilnehmer-Formular absenden
        $('#participant-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitButton = $('#participant-submit');
            
            // Verhindern von doppeltem Absenden
            if (submitButton.prop('disabled')) {
                return false;
            }
            
            // Button deaktivieren
            submitButton.prop('disabled', true);
            
            var participantId = $('#participant-id').val();
            var action = participantId == '0' ? 'create_participant' : 'update_participant';
            var messageContainer = $('#participant-message');
            
            $.ajax({
                url: event_attendance_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: event_attendance_ajax.nonce,
                    participant_id: participantId,
                    name: $('#participant-name').val(),
                    email: $('#participant-email').val(),
                    user_id: $('#participant-user').val()
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess(messageContainer, response.data.message);
                        
                        // Formular zurücksetzen und Seite neu laden
                        form[0].reset();
                        $('#participant-id').val('0');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showError(messageContainer, response.data.message);
                        // Button wieder aktivieren bei Fehler
                        submitButton.prop('disabled', false);
                    }
                },
                error: function() {
                    showError(messageContainer, 'An error occurred. Please try again.');
                    // Button wieder aktivieren bei Fehler
                    submitButton.prop('disabled', false);
                }
            });
        });
        
        // Wiederkehrende Events erstellen
        $('#recurring-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var messageContainer = $('#recurring-message');
            var submitButton = $('#recurring-submit');
            
            submitButton.prop('disabled', true).text('Creating...');
            messageContainer.hide();
            $('#created-events-container').hide();
            
            // Datum und Zeit kombinieren
            var startDateTime = $('#recurring-start-date').val() + ' ' + $('#recurring-time').val();
            var endDateTime = $('#recurring-end-date').val() + ' 23:59:59';
            
            $.ajax({
                url: event_attendance_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'create_recurring_events',
                    nonce: event_attendance_ajax.nonce,
                    title: $('#recurring-title').val(),
                    start_date: startDateTime,
                    end_date: endDateTime,
                    location: $('#recurring-location').val(),
                    description: $('#recurring-description').val(),
                    interval: $('#recurring-interval').val(),
                    day_of_week: $('#recurring-day-of-week').val()
                },
                success: function(response) {
                    submitButton.prop('disabled', false).text('Create Events');
                    
                    if (response.success) {
                        showSuccess(messageContainer, response.data.message);
                        
                        // Liste der erstellten Termine anzeigen
                        if (response.data.events && response.data.events.length > 0) {
                            var events = response.data.events;
                            var eventsHtml = '';
                            var title = $('#recurring-title').val();
                            
                            for (var i = 0; i < events.length; i++) {
                                var eventDate = new Date(events[i].date);
                                var formattedDate = eventDate.toLocaleDateString() + ' ' + eventDate.toLocaleTimeString();
                                eventsHtml += '<li>' + title + ' - ' + formattedDate + '</li>';
                            }
                            
                            $('#created-events-list').html(eventsHtml);
                            $('#created-events-container').show();
                        }
                    } else {
                        showError(messageContainer, response.data.message);
                    }
                },
                error: function() {
                    submitButton.prop('disabled', false).text('Create Events');
                    showError(messageContainer, 'An error occurred. Please try again.');
                }
            });
        });
        
        // Event löschen
        $('.event-delete').on('click', function() {
            var eventId = $(this).data('id');
            var title = $(this).data('title');
            
            if (confirm('Are you sure you want to delete the event "' + title + '"?')) {
                $.ajax({
                    url: event_attendance_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_event',
                        nonce: event_attendance_ajax.nonce,
                        event_id: eventId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('tr[data-id="' + eventId + '"]').fadeOut(500, function() {
                                $(this).remove();
                                if ($('#event-list tr').length === 0) {
                                    $('.event-list-container table').replaceWith('<p>No events found.</p>');
                                }
                            });
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        });
        
        // Teilnehmer löschen
        $('.participant-delete').on('click', function() {
            var participantId = $(this).data('id');
            var name = $(this).data('name');
            
            if (confirm('Are you sure you want to delete the participant "' + name + '"?')) {
                $.ajax({
                    url: event_attendance_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_participant',
                        nonce: event_attendance_ajax.nonce,
                        participant_id: participantId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('tr[data-id="' + participantId + '"]').fadeOut(500, function() {
                                $(this).remove();
                                if ($('#participant-list tr').length === 0) {
                                    $('.participant-list-container table').replaceWith('<p>No participants found.</p>');
                                }
                            });
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            }
        });
        
        // Event bearbeiten
        $('.event-edit').on('click', function() {
            var eventId = $(this).data('id');
            var title = $(this).data('title');
            var date = $(this).data('date');
            var location = $(this).data('location');
            var description = $(this).data('description');
            
            $('#event-form-title').text('Edit Event');
            $('#event-id').val(eventId);
            $('#event-title').val(title);
            $('#event-date').val(date);
            $('#event-location').val(location);
            $('#event-description').val(description);
            
            $('#event-submit').text('Update Event');
            $('#event-cancel').show();
            
            // Zum Formular scrollen
            $('html, body').animate({
                scrollTop: $('#event-form-title').offset().top - 100
            }, 500);
        });
        
        // Teilnehmer bearbeiten
        $('.participant-edit').on('click', function() {
            var participantId = $(this).data('id');
            var name = $(this).data('name');
            var email = $(this).data('email');
            var userId = $(this).data('user-id');
            
            $('#participant-form-title').text('Edit Participant');
            $('#participant-id').val(participantId);
            $('#participant-name').val(name);
            $('#participant-email').val(email);
            $('#participant-user').val(userId);
            
            $('#participant-submit').text('Update Participant');
            $('#participant-cancel').show();
            
            // Zum Formular scrollen
            $('html, body').animate({
                scrollTop: $('#participant-form-title').offset().top - 100
            }, 500);
        });
        
        // Abbrechen-Button
        $('#event-cancel, #participant-cancel').on('click', function() {
            var formType = $(this).attr('id').replace('-cancel', '');
            
            $('#' + formType + '-form-title').text('Add New ' + formType.charAt(0).toUpperCase() + formType.slice(1));
            $('#' + formType + '-id').val('0');
            $('#' + formType + '-form')[0].reset();
            $('#' + formType + '-submit').text('Save ' + formType.charAt(0).toUpperCase() + formType.slice(1));
            $(this).hide();
            $('#' + formType + '-message').hide();
        });
        
        // Teilnehmerliste anzeigen
        $('.event-view-attendees').on('click', function() {
            var eventId = $(this).data('id');
            var title = $(this).data('title');
            
            $('#attendees-modal-title').text(title);
            $('#attendees-list').html('<p>Loading...</p>');
            $('#attendees-modal').show();
            
            $.ajax({
                url: event_attendance_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_event_attendees',
                    nonce: event_attendance_ajax.nonce,
                    event_id: eventId
                },
                success: function(response) {
                    if (response.success) {
                        var attendees = response.data.attendees;
                        var html = '';
                        
                        if (attendees.length === 0) {
                            html = '<p>No attendees for this event.</p>';
                        } else {
                            html = '<table class="wp-list-table widefat fixed striped">';
                            html += '<thead><tr>';
                            html += '<th>Name</th>';
                            html += '<th>Status</th>';
                            html += '<th>Comment</th>';
                            html += '</tr></thead><tbody>';
                            
                            $.each(attendees, function(i, attendee) {
                                var statusText = '';
                                switch(attendee.status) {
                                    case 'attending':
                                        statusText = 'Attending';
                                        break;
                                    case 'declined_sick':
                                        statusText = 'Declined (Sick)';
                                        break;
                                    case 'declined_vacation':
                                        statusText = 'Declined (Vacation)';
                                        break;
                                    case 'declined_business':
                                        statusText = 'Declined (Business Trip)';
                                        break;
                                    default:
                                        statusText = attendee.status;
                                }
                                
                                html += '<tr>';
                                html += '<td>' + attendee.name + '</td>';
                                html += '<td>' + statusText + '</td>';
                                html += '<td>' + (attendee.comment || '-') + '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</tbody></table>';
                        }
                        
                        $('#attendees-list').html(html);
                    } else {
                        $('#attendees-list').html('<p class="error">' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    $('#attendees-list').html('<p class="error">An error occurred. Please try again.</p>');
                }
            });
        });
        
        // Modal schließen
        $('.event-attendance-modal-close').on('click', function() {
            $('.event-attendance-modal').hide();
        });
        
        // Auch außerhalb des Modals klicken schließt es
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('event-attendance-modal')) {
                $('.event-attendance-modal').hide();
            }
        });
    });

})(jQuery);