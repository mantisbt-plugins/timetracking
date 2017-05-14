$(document).ready(function() {
	/**
	 * Manage collapsed form inputs that are hidden from collapsed divs
	 * These inputs are disables when collapsed, so they are not sent when the form is submittted
	 */
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
		/* disable inputs that are collapsed at startup */
		$('.collapse.disable-collapsed-inputs:not(.in)').find(':input').each( function(){
			var input = $(this);
			input.data('previous_disabled', input.prop('disabled') ).prop('disabled', true);
		});
	}

	/**
	 * Manage grouping controls in time tracking reports
	 */
	if( $('.ttreport_groupby_remove a').length ) {
		$('.ttreport_groupby_remove a').click( function(e){
			e.preventDefault();
			var span = $(e.currentTarget).closest('span.ttreport_groupby');
			span.remove();
		});
	}

	/**
	 * Manage visiblity on hover trigger objects
	 */
	if( $('.hover_visibility_toggle').length ) {
		$('.hover_visibility_toggle').hover(
			function(e){ // handlerIn
				$(e.currentTarget).find('.hover_visibility_target').removeClass('invisible');
			},
			function(e){ // handlerOut
				$(e.currentTarget).find('.hover_visibility_target').addClass('invisible');
			}
		);
	}
});