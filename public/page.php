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
				list goes here
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