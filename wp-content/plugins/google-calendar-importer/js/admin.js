jQuery(document).ready(function($) {

    $( '#googleCal_page_table' ).on( 'click', 'span.add_event_action', function(event){
		var tr = $(this).parents('tr.type-event');

		var data = {
			action:					'wppolska_save_event',
			event_id:				tr.find('.event-id').text(),
			event_summary:			tr.find('.event-summary').text(),
			event_description:		tr.find('.event-description').text(),
			event_creator_name:		tr.find('.event-creator-name').text(),
			event_creator_email:	tr.find('.event-creator-email').text(),
			event_start_date:		tr.find('.event-start-date').text(),
			event_start_time:		tr.find('.event-start-time').text(),
			event_end_date:			tr.find('.event-end-date').text(),
			event_end_time:			tr.find('.event-end-time').text(),
			nonce:					ajax_wppolska_var.nonce_save_event
		};

		tr.find('.add_event').hide();
		tr.find('.loading').show();

		$.post( ajax_wppolska_var.ajaxurl, data, function(response) {
			console.log(response);
			tr.find('.loading').hide();
			if (response.status === 200) {
				tr.find('.success').show();
				tr.addClass('active');
			}
			if (response.status === 500) {
				// TODO: some error messages handling
				tr.find('.add_event').show();
				tr.find('.dismiss').show();
				console.log(response.msg);
			}
		});
    });

});