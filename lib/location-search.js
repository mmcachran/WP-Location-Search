var WPLocationSearch = (function( window, document, $, undefined ) {
	'use strict';
	
	var app = {
		map : null,
		markers : [],
		info_window : null,
		location_select : null,
	};

	var l10n = window.wpls_config;

	app.init = function() {
		$.extend( app, l10n );

		google.maps.event.addDomListener(window, 'load', app.load);
		app.search_locations();

		$(document).on('change', '#radiusSelect', function(e) {
			e.preventDefault();
			app.search_locations();
		});
		
		$(document).on('change', '#personSelect', function(e) {
			e.preventDefault();
			app.search_locations();
		});
	};

	app.load = function () {
		app.map = new google.maps.Map(document.getElementById("wpls-map"), {
			center: new google.maps.LatLng(27, -82),
			zoom: 6,
			mapTypeId: 'roadmap',
			mapTypeControlOptions: {
				style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
			}
		});
			
		app.info_window = new google.maps.InfoWindow();

		app.location_select = document.getElementById('location_select');
		app.location_select.onchange = function() {
			var markerNum = app.location_select.options[app.location_select.selectedIndex].value;
				
			if (markerNum != "none") {
				google.maps.event.trigger(app.markers[markerNum], 'click');
			}
		};
	};

	app.search_locations = function () {
		//var address = document.getElementById("addressInput").value;
		var address = '4121 N. 50th Street, Tampa, Florida 33610';
		var geocoder = new google.maps.Geocoder();
		geocoder.geocode({address: address}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				app.search_locations_near(results[0].geometry.location);
			} else {
				alert(address + ' not found');
			}
		});
	};

	app.clear_locations = function () {
		if ( ! app.info_window ) {
			app.info_window = new google.maps.InfoWindow();
		}

		app.info_window.close();

		if ( ! app.location_select ) {
			app.location_select = document.getElementById('location_select');
		}
		
		for (var i = 0; i < app.markers.length; i++) {
			app.markers[i].setMap(null);
		}

		app.markers.length = 0;

		app.location_select.innerHTML = '';
		var option = document.createElement('option');
		option.value = 'none';
		option.innerHTML = 'See all results:';
		app.location_select.appendChild(option);
	};

	app.search_locations_near = function (center) {
		app.clear_locations();
		var radius = document.getElementById('radiusSelect').value || 10;
		
		var data = {
			'lat' : center.lat(),
			'lng' : center.lng(),
			'radius' : radius,
			'action' : 'wpls_fetch_locations',
			'nonce' : app.nonce,
		};

		app.fetch_locations( data );
	};

	app.create_marker = function (latlng, location) {
		var html = "<b>" + location.title + "</b> <br />";
		html += location.address + "<br />";
		html += location.city + ', ' + location.state+ "<br />";
		html += "<a href='" + location.permalink + "' target='_blank'>View Details</a><br />";
		
		var marker = new google.maps.Marker({
			map: app.map,
			position: latlng
		});

		google.maps.event.addListener(marker, 'click', function() {
			app.info_window.setContent(html);
			app.info_window.open(app.map, marker);
		});

		app.markers.push(marker);
	};

	app.create_option = function (name, num) {
		var option = document.createElement("option");
		option.value = num;
		option.innerHTML = name;
		app.location_select.appendChild(option);
	};

	app.fetch_locations = function( data ) {
		return $.ajax({
			url : app.ajaxurl,
			dataType : 'json',
			data : data,
		}).done(function( data ) {
			console.log(data);
			if ( data.success ) {
				var bounds = new google.maps.LatLngBounds();
						
				// if no locations were found, keep from being centered in the ocean
				if( data.data.length < 1 ) {
					alert( 'No locations were found' );
					return false;
				}
					
				$.each( data.data, function( i, location) {
					var latlng = new google.maps.LatLng(
						parseFloat( location.lat ),
						parseFloat( location.lng )
					);

					app.create_option( location.title, i );
					app.create_marker(latlng, location );
					bounds.extend( latlng );
				});
						
				app.map.fitBounds(bounds);
				app.location_select.style.visibility = "visible";
				app.location_select.onchange = function() {
					var marker_num = app.location_select.options[app.location_select.selectedIndex].value;
					google.maps.event.trigger(app.markers[marker_num], 'click');
				};
			}
		});
	};

	app.do_nothing = function() {};
	
	$( document ).ready( app.init );
	
	return app;
	
})( window, document, jQuery );