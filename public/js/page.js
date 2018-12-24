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
		popupTemplate = $('.mapPopup.template');
		data.forEach(function(item, index) {
			itemElem = itemTemplate.clone();
			itemElem.removeClass('template');
			$('.title', itemElem).text(item.title);
			$('.description', itemElem).text(item.description);
			$('.location', itemElem).text(item.place);
			$('.date', itemElem).text(item.date);
			$('.link', itemElem).attr('href', item.url);
			$('.link', itemElem).attr('title', item.title);
			$('.thumbnail', itemElem).attr('src', item.thumbnail);
			itemList.append(itemElem);

			popupElem = popupTemplate.clone();
			popupElem.removeClass('template');
			$('.title', popupElem).text(item.title);
			$('.location', popupElem).text(item.place);
			$('.date', popupElem).text(item.date);
			$('a.link', popupElem).attr('href', item.url);
			$('span.link', popupElem).text(item.url);
			$('.location', popupElem).text(item.place);
			$('.thumbnail', popupElem).attr('src', item.thumbnail);

			var infowindow = new google.maps.InfoWindow({
				content: popupElem.html()
			});
			var marker = new google.maps.Marker({
				position: item.location,
				map: map,
				title: item.title
			});
			marker.addListener('click', function() {
				infowindow.open(map, marker);
			});
		});
	});
});
