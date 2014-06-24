<?php
/**
 * Represents the settings for the administration dashboard.
 *
 * @package   vubpay
 * @author    Michal Zuber <info@nevilleweb.sk>
 * @license   GPL-2.0+
 * @link      http://nevilleweb.sk
 * @copyright 2014 Michal Zuber
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<form action="<?php echo admin_url( "edit.php?post_type={$this->plugin_slug}&page=settings" ); ?>" method="post">
		<?php settings_fields( $this->plugin_slug ); ?>
		<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="client_id"><?php _e( 'Client ID', $this->plugin_slug ); ?></label></th>
				<td>
					<input type="text" name="<?php echo $this->plugin_slug; ?>_client_id" id="client_id" class="regular-text" maxlength="8" placeholder="12345678" value="<?php echo get_option( $this->plugin_slug . '_client_id' ); ?>"/>
					<p class="description"><?php _e( 'Emailed from the banks personnel', $this->plugin_slug ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="store_key"><?php _e( 'Store key', $this->plugin_slug ); ?></label></th>
				<td>
					<input type="text" name="<?php echo $this->plugin_slug; ?>_store_key" id="store_key" class="regular-text" maxlength="20" value="<?php echo get_option($this->plugin_slug . '_store_key'); ?>"/>
					<p class="description"><?php _e( 'Generated at <a href="https://testsecurepay.intesasanpaolocard.com/vub/report/admin.3D.securityKey?opr=key" target="_blank">https://testsecurepay.intesasanpaolocard.com/vub/report/admin.3D.securityKey?opr=key</a> or for production at <a href="https://vub.eway2pay.com/vub/report/user.login" target="_blank">https://vub.eway2pay.com/vub/report/user.login', $this->plugin_slug ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="gateway_url"><?php _e( 'Gateway URL', $this->plugin_slug ); ?></label></th>
				<td>
					<input type="text" name="<?php echo $this->plugin_slug; ?>_gateway_url" id="gateway_url" class="regular-text" maxlength="255" placeholder="https://vub.eway2pay.com/vub/report/user.login" value="<?php echo get_option( $this->plugin_slug . '_gateway_url' ); ?>"/>
					<p class="description"><?php _e( 'Action URL where to submit POST data, should be <a href="https://vub.eway2pay.com/fim/est3dgate" target="_blank">https://vub.eway2pay.com/fim/est3dgate</a>', $this->plugin_slug ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ok_url"><?php _e( 'URL for success payment', $this->plugin_slug ); ?></label></th>
				<td>
					<code><?php echo site_url(); ?></code>
					<input type="text" name="<?php echo $this->plugin_slug; ?>_ok_url" id="ok_url" class="regular-text" maxlength="255" value="<?php echo get_option( $this->plugin_slug . '_ok_url' ); ?>"/>
					<p class="description"><?php _e( 'Visitor is redirected to this URL on successful payment', $this->plugin_slug ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="fail_url"><?php _e( 'URL for failed payment', $this->plugin_slug ); ?></label></th>
				<td>
					<code><?php echo site_url(); ?></code>
					<input type="text" name="<?php echo $this->plugin_slug; ?>_fail_url" id="fail_url" class="regular-text" maxlength="255" value="<?php echo get_option( $this->plugin_slug . '_fail_url' ); ?>"/>
					<p class="description"><?php _e( 'Visitor is redirected to this URL on failed payment', $this->plugin_slug ); ?></p>
				</td>
			</tr>
		</tbody>
		</table>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save settings ', $this->plugin_slug ); ?>"></p>
	</form>
</div>

