var source;

function call_remote_exec( a ) {
	var ja = $(a);
	var remote = ja.data('remote');

	var target_td = ja.closest('td');
	target_td.html('<span class="exec_status"></span><div class="progress"><div class="progress-bar" style="width:0%"></div></div>');

	source = new EventSource(remote);

	source.onerror = function() {
		source.close();
		update_status( 'Error', target_td );
	};
	source.addEventListener('message', function(e) {
		var result = JSON.parse( e.data );
		if(e.lastEventId == 'CLOSE') {
            source.close();
			load_steps_table();
		} else {
			update_progress(result, target_td);
		}
	});
}

function update_status( txt, target ) {
	target.find('span.exec_status').html(txt);
}

function update_progress( data, target ) {
	var txt = data.status;
	if( undefined !== data.current ) {
		var pval = Math.floor( ( data.current / data.max ) * 100 );
		var progress = target.find('div.progress-bar');
		progress.css( 'width', pval+'%' );
		txt = txt + ' ('+data.current+'/'+data.max+')';
	}
	update_status( txt, target );
}

function load_steps_table() {
	var div = $('div#steps_table');
	div.load( div.data('remote') );
}

$(document).ready(function() {

	if( ('div#steps_table').length ) {
		load_steps_table();
	}

	$(document).on('click', '.trigger_exec',
		function(){
			call_remote_exec( this );
		});
});