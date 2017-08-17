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
            <?php for ($i = 0; $i < apply_filters( 'woocommerce_email_attachment_input_count', 4 ); $i++) : ?>
			<tr>
				<td>
					<label for="processing_order_attachment<?php echo $i; ?>"><?php esc_html_e( 'Attachment ', $this->plugin_slug ); ?> #<?php echo $i+1 ?>:</label>
				</td>
				<td>
					<input type="text" class="regular-text" name="processing_order_attachment[]" id="processing_order_attachment<?php echo $i; ?>" value="<?php if ( isset( $processing_order_attachments[$i] ) ) esc_attr_e( $processing_order_attachments[$i] ); ?>"/>
					<button type="button" data-editor="processing_order_attachment<?php echo $i; ?>" class="button insert-media"><?php esc_html_e( 'Select File', $this->plugin_slug ); ?></button>
				</td>
			</tr>
            <?php endfor; ?>
	
			<tr>
				<td>&nbsp;</td>
				<td>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', $this->plugin_slug ); ?></button>
				</td>
			</tr>
		</table>
	</form>

</div>
