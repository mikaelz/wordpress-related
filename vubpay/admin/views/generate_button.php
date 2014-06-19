<?php
/**
 * Generate HTML code for embedding payment button
 * 
 * @package   vubpay
 * @author    Michal Zuber <info@nevilleweb.sk>
 * @license   GPL-2.0+
 * @link      http://nevilleweb.sk
 * @copyright 2014 Michal Zuber
 */

$btn_lang_arr = array(
	'en' => __( 'English', $this->plugin_slug ),
	'hu' => __( 'Magyar', $this->plugin_slug ),
	'sk' => __( 'Slovenčina', $this->plugin_slug ),
	'cz' => __( 'Čeština', $this->plugin_slug ),
);
$btn_lang_opts = '';
foreach ( $btn_lang_arr as $id => $title ) {
	$selected = isset($_POST['btn_lang']) && $_POST['btn_lang'] == $id ? ' selected="selected"' : '';
	$btn_lang_opts .= '<option value="'.$id.'"'.$selected.'>'.$title.'</option>';
}

$btn_currency_arr = array(
	978 => 'EUR',
	203 => 'CZK',
	348 => 'HUF',
	985 => 'PLN',
);
$btn_currency_opts = '';
foreach ( $btn_currency_arr as $id => $title ) {
	$selected = isset($_POST['btn_currency']) && $_POST['btn_currency'] == $id ? ' selected="selected"' : '';
	$btn_currency_opts .= '<option value="'.$id.'"'.$selected.'>'.$title.'</option>';
}
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<form action="<?php echo admin_url( "edit.php?post_type={$this->plugin_slug}&page=generate_button" ); ?>" method="post">
		<?php settings_fields( $this->plugin_slug ); ?>

		<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="lang"><?php _e( 'Language', $this->plugin_slug ); ?></label></th>
				<td>
					<select name="btn_lang" id="lang">
						<option value=""><?php _e( 'Select', $this->plugin_slug); ?></option>
						<?php echo $btn_lang_opts; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="amount"><?php _e( 'Price', $this->plugin_slug ); ?></label></th>
				<td>
					<input type="text" name="btn_amount" id="amount" maxlength="10" value="<?php if ( ! empty( $_POST['btn_amount']) ) echo esc_attr( $_POST['btn_amount'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="currency"><?php _e( 'Currency', $this->plugin_slug ); ?></label></th>
				<td>
					<select name="btn_currency" id="currency">
						<option value=""><?php _e( 'Select', $this->plugin_slug); ?></option>
						<?php echo $btn_currency_opts; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="description"><?php _e( 'Description', $this->plugin_slug ); ?></label></th>
				<td>
					<input type="text" name="btn_description" id="description" maxlength="50" value="<?php if ( ! empty( $_POST['btn_description']) ) echo esc_attr( $_POST['btn_description'] ); ?>" class="regular-text"/>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="text"><?php _e( 'Button text', $this->plugin_slug ); ?></label></th>
				<td>
					<input type="text" name="btn_text" id="text" maxlength="10" value="<?php echo ( ! empty( $_POST['btn_text']) ) ? esc_attr( $_POST['btn_text'] ) : __( 'Pay', $this->plugin_slug ); ?>" class="regular-text"/>
				</td>
			</tr>
		</tbody>
		</table>

		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Generate button', $this->plugin_slug ); ?>"></p>
	</form>

	<h3><?php _e( 'Copy&paste code', $this->plugin_slug ); ?></h3>
	<pre>
<?php echo $html_btn; ?>
	</pre>
</div>

