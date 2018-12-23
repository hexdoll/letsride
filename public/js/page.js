var map;
function initMap() {
	map = new google.maps.Map(document.getElementById('map'), {
		center: {lat: 54.6, lng: -3.5},
		zoom: 5
	});
}

jQuery(document).ready(function($){
	$('.switchView').click(function (e) {
		e.preventDefault();
		$('.app__viewWrapper').toggleClass('showMap');
	});
});
