<?php
if (!defined('WPINC')) {
	die('Permission Denied');
}
?>
<div class="<?php echo LetsRide::NAME ?>">
	<div class="app__container square">
		<div class="app__viewWrapper">
			<div class="app__listContainer">
				<div class="switchView showMap">
					<a href="#" title="Show Map">Map</a>
				</div>
				<div class="listWrapper">
					<ul class="listItems">
						<!-- this is grabbed by the js, duplicated and filled with data -->
						<li class="listItem template">
							<a class="link" target="_blank" href="#" title="Template Title"><span class="title">Template Title</span></a>
							<div class="date">Sample date</div>
						</li>
					</ul>
				</div>
			</div>
			<div class="app__mapContainer">
				<div class="switchView showList">
					<a class="show-list" href="#" title="Show List">List</a>
				</div>
				<div class="mapWrapper">
					<div id="map"></div>
				</div>
			</div>
		</div>
	</div>
</div>