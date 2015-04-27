var WPLocationSearch = (function( window, document, $, undefined ) {
	'use strict';
	
	var app = {};
	
	app.init = function() {
		alert( 'test' );
	};
	
	$( document ).ready( app.init );
	
	return app;
	
})( window, document, jQuery );