<?php
if (!defined('WPINC')) {
	die('Permission Denied');
}
$feeds = LetsRide::active_feed_urls();
?>

<h1>Let's Ride Settings</h1>

<p>Current data feeds:</p>

<?php if ($feeds): ?>
	<ul>
	<?php foreach (LetsRide::active_feed_urls() as $feed): ?>
		<li><?php echo esc_url($feed) ?></li>
	<?php endforeach; ?>
	</ul>
<?php else: ?>
	<p>No feeds found.</p>
<?php endif; ?>
