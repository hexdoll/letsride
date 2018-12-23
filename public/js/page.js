//letsride_data variable is passed in from Wordpress for data from PHP
//console.log(letsride_data);

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

	settings = {
		method: 'POST',
		data: {
			action: letsride_data.action,
		},
	};
	$.ajax(letsride_data.ajax_url, settings).success(function(data) {
		itemList = $('.listItems');
		itemTemplate = $('.listItem.template');
		data.forEach(function(item, index) {
			itemElem = itemTemplate.clone();
			itemElem.removeClass('template');
			$('.title', itemElem).text(item.title);
			$('.date', itemElem).text(item.date);
			itemList.append(itemElem);
			// TODO: add a marker to the map
		});
	});
});
