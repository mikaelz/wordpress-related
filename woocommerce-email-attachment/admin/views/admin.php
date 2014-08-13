<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Woocommerce_Email_Attachment
 * @author    Michal Zuber <info@nevilleweb.sk>
 * @license   GPL-2.0+
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<h3 class="title"><?php esc_html_e( 'For New Order email', $this->plugin_slug ); ?></h3>
	<form action="<?php echo admin_url( "admin.php?page={$this->plugin_slug}" ); ?>" method="post">
		<?php wp_nonce_field( $this->plugin_slug . '-admin' ); ?>

		<table>
			<tr>
				<td>
					<label for="processing_order_attachment0"><?php esc_html_e( 'Attachment ', $this->plugin_slug ); ?> #1:</label>
				</td>
				<td>
					<input type="text" class="regular-text" name="processing_order_attachment[]" id="processing_order_attachment0" value="<?php if ( isset( $processing_order_attachments[0] ) ) esc_attr_e( $processing_order_attachments[0] ); ?>"/>
					<button type="button" data-editor="processing_order_attachment0" class="button insert-media"><?php esc_html_e( 'Select File', $this->plugin_slug ); ?></button>
				</td>
			</tr>
	
			<tr>
				<td>
					<label for="processing_order_attachment1"><?php esc_html_e( 'Attachment ', $this->plugin_slug ); ?> #2:</label>
				</td>
				<td>
					<input type="text" class="regular-text" name="processing_order_attachment[]" id="processing_order_attachment1" value="<?php if ( isset( $processing_order_attachments[1] ) ) esc_attr_e( $processing_order_attachments[1] ); ?>"/>
					<button type="button" data-editor="processing_order_attachment1" class="button insert-media"><?php esc_html_e( 'Select File', $this->plugin_slug ); ?></button>
				</td>
			</tr>
	
			<tr>
				<td>&nbsp;</td>
				<td>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', $this->plugin_slug ); ?></button>
				</td>
			</tr>
		</table>
	</form>

</div>
