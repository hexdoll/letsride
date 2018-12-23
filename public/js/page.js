function initMap() {
	map = new google.maps.Map(document.getElementById('map'), {
		center: {lat: -34.397, lng: 150.644},
		zoom: 8
	});
}

jQuery(document).ready(function($){
	$('.switchView').click(function (e) {
		e.preventDefault();
		$('.app__viewWrapper').toggleClass('showMap');
	});
});
