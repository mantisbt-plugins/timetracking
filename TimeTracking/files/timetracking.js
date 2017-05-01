$(document).ready(function() {
	if( $('.disable-collapsed-inputs').length ) {
		$('.collapse.disable-collapsed-inputs')
			.on('hidden.bs.collapse', function (e) {
				$(e.currentTarget).find(':input').each( function(){
					var input = $(this);
					input.data('previous_disabled', input.prop('disabled') ).prop('disabled', true);
				});
			})
			.on('show.bs.collapse', function (e) {
				$(e.currentTarget).find(':input').each( function(){
					var input = $(this);
					input.prop('disabled', input.data('previous_disabled')).removeData('previous_disabled');
				});
			});
	}

	if( $('a.stopwatch_open').length ) {
		$('a.stopwatch_open').click(function(e){
			//var sw_div = $('#stopwatch');
			e.preventDefault();
			var sw_div = $('a.stopwatch_open').closest('div.stopwatch_control');
			var sw_ui = sw_div.find('.stopwatch_ui');
			sw_div.find('.stopwatch_open').hide();
			stopwatch_init( sw_ui );
			sw_ui.show();
		});
	}

});