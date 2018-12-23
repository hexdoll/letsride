<?php
if (!defined('WPINC')) {
	die('Permission Denied');
}
$feeds = LetsRide::active_feed_urls();
$action = LetsRide::NAME;
$nonce = LetsRide::PREFIX.'nonce';
$feeds_option = get_option(LetsRide::PREFIX.'feeds');
?>

<h1>Let's Ride Settings</h1>

<p>Current data feeds:</p>

<?php if ($feeds): ?>
	<ul>
	<?php foreach (LetsRide::active_feed_urls() as $feed): ?>
		<li>
			<?php
			$feed_data = $feeds_option[$feed];
			$updated = (isset($feed_data['updated']) ? date_i18n('Y-m-d H:i:s', $feed_data['updated']) : 'never');
			?>
			<?php echo esc_url($feed) ?>
			(last updated: <?php echo $updated; ?>)
		</li>
	<?php endforeach; ?>
	</ul>
<?php else: ?>
	<p>No feeds found.</p>
<?php endif; ?>

<?php
$redirect = urlencode( remove_query_arg( 'msg', $_SERVER['REQUEST_URI'] ) );
$redirect = urlencode( $_SERVER['REQUEST_URI'] );
?>
<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
	<?php wp_nonce_field( $action, $nonce, false ); ?>
	<input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">
	<input type="hidden" name="action" value="<?php echo $action; ?>" />

	<?php submit_button('Update Feeds', 'primary', 'update-feeds', false); ?>
	<?php submit_button('Clear Cache', 'primary', 'clear-cache', false); ?>
</form>