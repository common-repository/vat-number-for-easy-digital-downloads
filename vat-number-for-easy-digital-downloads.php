<?php
/**
 * Plugin Name: VAT Number for Easy Digital Downloads
 * Description: Facilitates the collection and validation of EU VAT numbers on the Easy Digital Downloads checkout page.
 * Version: 1.0.1
 * Author: Potent Plugins
 * Author URI: http://potentplugins.com/?utm_source=vat-number-for-easy-digital-downloads&utm_medium=link&utm_campaign=wp-plugin-author-uri
 * License: GNU General Public License version 2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 */
if (!defined('ABSPATH')) exit;

add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'pp_eddvat_action_links');
function pp_eddvat_action_links($links) {
	array_unshift($links, '<a href="'.esc_url(get_admin_url(null, 'edit.php?post_type=download&page=edd-settings&tab=taxes&section=vat_number')).'">Settings</a>');
	return $links;
}

add_filter('edd_settings_taxes', 'pp_eddvat_edd_settings_taxes');
function pp_eddvat_edd_settings_taxes($settings) {
	$settings['vat_number'] = array(
		'pp_eddvat_header' => array(
			'id' => 'pp_eddvat_header',
			'name' => '<h3>VAT Number Settings</h3>',
			'type' => 'header'
		),
		'pp_eddvat_enabled' => array(
			'id' => 'pp_eddvat_enabled',
			'name' => 'Enable VAT number collection',
			'desc' => '',
			'type' => 'checkbox'
		),
		'pp_eddvat_countries' => array(
			'id' => 'pp_eddvat_countries',
			'name' => 'VAT countries',
			'desc' => 'Provide a comma-separated list of all country codes for which a VAT number is required. Do not include spaces.',
			'type' => 'text'
		),
		'pp_eddvat_optional' => array(
			'id' => 'pp_eddvat_optional',
			'name' => 'VAT number is optional',
			'desc' => 'If checked, a VAT number will not be required for checkout. The VAT number field is mandatory by default.',
			'type' => 'checkbox'
		),
		'pp_eddvat_ignore_service_failure' => array(
			'id' => 'pp_eddvat_ignore_service_failure',
			'name' => 'Continue on validation service failure',
			'desc' => '<strong>Not recommended!</strong> If checked, the order will be allowed to proceed if the validation service cannot be used to validate the VAT number.',
			'type' => 'checkbox'
		),
		'pp_eddvat_ignore_invalid' => array(
			'id' => 'pp_eddvat_ignore_invalid',
			'name' => 'Accept invalid VAT numbers',
			'desc' => '<strong>Not recommended!</strong> If checked, the order will be allowed to proceed if the validation service says that the VAT number is invalid.',
			'type' => 'checkbox'
		),
		'pp_eddvat_field_label' => array(
			'id' => 'pp_eddvat_field_label',
			'name' => 'Checkout field name',
			'desc' => '',
			'type' => 'text'
		),
		'pp_eddvat_field_desc' => array(
			'id' => 'pp_eddvat_field_desc',
			'name' => 'Checkout field description',
			'desc' => '',
			'type' => 'text'
		),
		'pp_eddvat_error_message' => array(
			'id' => 'pp_eddvat_error_message',
			'name' => 'Validation error message',
			'desc' => '',
			'type' => 'text'
		)
	);
	return $settings;
}

add_filter('edd_settings_sections_taxes', 'pp_eddvat_edd_settings_sections_taxes');
function pp_eddvat_edd_settings_sections_taxes($section) {
	$section['vat_number'] = 'VAT Number';
	return $section;
}

add_action('edd_settings_tab_top_taxes_vat_number', 'pp_eddvat_settings_top');
function pp_eddvat_settings_top() {
	echo('
		<p style="clear: both; padding-top: 20px;">This feature enables the collection and validation of EU VAT numbers on checkout. <strong>The scope of this plugin is limited and no representation is made in regard to its suitability or sufficiency in meeting the legal requirements associated with the EU VAT. It is entirely your responsibility to ensure that you are meeting all applicable VAT regulations.</strong></p>
		<p>It is also important to note the following:</p>
		<ul style="list-style-type: disc; padding-left: 2em; margin-bottom: 30px;">
			<li>This plugin uses the <a href="http://ec.europa.eu/taxation_customs/vies/" target="_blank">VIES service</a> to validate VAT numbers. Use of this plugin in conjunction with the VIES service is subject to the <a href="http://ec.europa.eu/taxation_customs/vies/disclaimer.html" target="_blank">VIES service disclaimer</a>.</li>
			<li>The VIES service is subject to maintenance outages, during which periods certain countries\' VAT numbers cannot be validated through VIES. <a href="http://ec.europa.eu/taxation_customs/vies/help.html" target="_blank">Click here</a> for details and schedules.</li>
			<li>VIES data is cached and may be up to one day old.</li>
			<li>At this time, VAT number validation consists only of querying VIES to check whether the VAT number is valid. For example, the plugin does not check whether the VAT number matches the country of the billing address or the customer\'s IP address location.</li>
		</ul>
	');
	
	$potent_slug = 'vat-number-for-easy-digital-downloads';
	include(__DIR__.'/plugin-credit.php');
}

if (function_exists('edd_get_option') && edd_get_option('pp_eddvat_enabled')) {
	add_action('edd_cc_billing_bottom', 'pp_eddvat_checkout_field');
	add_action('edd_checkout_error_checks', 'pp_eddvat_validate_checkout', 10, 2);
	add_filter('edd_payment_meta', 'pp_eddvat_payment_meta');
	add_filter('edd_use_taxes', 'pp_eddvat_true');
}

function pp_eddvat_true() {
	return true;
}

function pp_eddvat_checkout_field() {
	$isRequired = !edd_get_option('pp_eddvat_optional', false);
	echo('<p id="edd-vat-number-wrap">
			<label class="edd-label" for="vat_number">'.edd_get_option('pp_eddvat_field_label').($isRequired ? ' <span class="edd-required-indicator">*</span>' : '').'</label>
			<span class="edd-description" for="vat_number">'.edd_get_option('pp_eddvat_field_desc').'</span>
			<input type="text" class="edd-input'.($isRequired ? ' required' : '').'" name="vat_number" id="vat_number"'.($isRequired ? ' required="required"' : '').' />
		</p>
		<script>
			var vatCountries = \''.htmlspecialchars(edd_get_option('pp_eddvat_countries')).'\'.split(\',\');
			jQuery(document).ready(function($) {
				$(\'#billing_country\').change(function() {
					var $ = jQuery;
					var isVatCountry = (vatCountries.indexOf($(this).val()) != -1);
					$(\'#edd-vat-number-wrap\').toggle(isVatCountry);
					'.($isRequired ? '$(\'#vat_number\').prop(\'required\', isVatCountry);' : '').'
				});
				$(\'#billing_country\').change();
			});
		</script>
		');
}

function pp_eddvat_validate_checkout($valid_data, $data) {
	if (!edd_get_option('pp_eddvat_enabled') || !in_array($valid_data['cc_info']['card_country'], explode(',', edd_get_option('pp_eddvat_countries'))))
		return;
	if (!empty($data['vat_number'])) {
		$validateResult = pp_eddvat_validate($data['vat_number']);
		if ($validateResult === false) {
			if (edd_get_option('pp_eddvat_ignore_service_failure', false))
				return;
		} else if (!empty($validateResult['valid']) || edd_get_option('pp_eddvat_ignore_invalid', false)) {
			return;
		}
	} else if (edd_get_option('pp_eddvat_optional', false)) { // VAT number was empty
		return;
	}
	edd_set_error('invalid_vat_number', edd_get_option('pp_eddvat_error_message'));
}

function pp_eddvat_payment_meta($meta) {
	if (isset($_POST['vat_number'])) {
		$meta['vat_number'] = $_POST['vat_number'];
		$validateResult = pp_eddvat_validate($_POST['vat_number']);
		if ($validateResult !== false) {
			foreach ($validateResult as $key => $value) {
				$meta['vat_'.$key] = $value;
			}
		}
	}
	return $meta;
}

function pp_eddvat_validate($vatNumber) {
	
	$vatNumber = strtoupper(trim(str_replace(' ', '', $vatNumber)));
	if (!ctype_alnum($vatNumber) || strlen($vatNumber) < 3)
		return array('valid' => false);
	
	$cache = get_option('pp_eddvat_cache', array());
	$cacheKey = date('Ymd');
	if (!empty($cache[$cacheKey][$vatNumber])) {
		return $cache[$cacheKey][$vatNumber];
	}
	
	$service = 'PP_VIES';
	require_once(__DIR__.'/'.$service.'.class.php');
	$result = $service::validate($vatNumber);
	
	if ($result !== false) {
		foreach ($cache as $key => $value) {
			if ($key != $cacheKey)
				unset($cache[$key]);
		}
		if (!isset($cache[$cacheKey]))
			$cache[$cacheKey] = array();
		$cache[$cacheKey][$vatNumber] = $result;
		update_option('pp_eddvat_cache', $cache, false);
	}
	
	return $result;
}

add_action('edd_view_order_details_billing_after', 'pp_eddvat_order_box');
function pp_eddvat_order_box($paymentId) { $paymentMeta = edd_get_payment_meta($paymentId); ?>
	<div id="edd-vat-info" class="postbox">
		<h3 class="hndle"><span>VAT Info</span></h3>
		<div class="inside">
			<p>
				<strong>VAT Number:</strong>
				<?php echo(empty($paymentMeta['vat_number']) ? 'None' : $paymentMeta['vat_number']); ?>
			</p>
			<?php if (!empty($paymentMeta['vat_number'])) { ?>
			<p>
				<strong>Validated:</strong>
				<?php echo(empty($paymentMeta['vat_time']) || empty($paymentMeta['vat_valid']) ? 'No' : 'via '.$paymentMeta['vat_source'].' at '.date(get_option('date_format').' '.get_option('time_format'), $paymentMeta['vat_time'])); ?>
			</p>
			<?php if (!empty($paymentMeta['vat_name']) && !empty($paymentMeta['vat_valid'])) { ?>
			<p>
				<strong>VAT Company:</strong>
				<?php echo(htmlspecialchars($paymentMeta['vat_name'].(empty($paymentMeta['vat_address']) ? '' : ', '.$paymentMeta['vat_address']))); ?>
			</p>
			<?php } ?>
			<?php } ?>
		</div>
	</div>
<?php }

add_action('edd_add_email_tags', 'pp_eddvat_email_tags');
function pp_eddvat_email_tags() {
	edd_add_email_tag('vat_number', 'The buyer\'s VAT number', 'pp_eddvat_get_vat_number_for_payment');
}

function pp_eddvat_get_vat_number_for_payment($paymentId) {
	$paymentMeta = edd_get_payment_meta($paymentId);
	return (empty($paymentMeta['vat_number']) ? 'N/A' : $paymentMeta['vat_number']);
}


register_activation_hook(__FILE__, 'pp_eddvat_activate');
function pp_eddvat_activate() {
	$defaultOptions = array(
		'pp_eddvat_enabled' => false,
		'pp_eddvat_countries' => 'BE,BG,CZ,DK,DE,EE,GR,ES,FR,HR,IE,IT,CY,LV,LT,LU,HU,MT,NL,AT,PL,PT,RO,SI,SK,FI,SE,GB',
		'pp_eddvat_optional' => false,
		'pp_eddvat_ignore_service_failure' => false,
		'pp_eddvat_ignore_invalid' => false,
		'pp_eddvat_field_label' => 'VAT Number',
		'pp_eddvat_field_desc' => 'EU customers must enter a VAT number to purchase online. If you do not have a VAT number, please contact us to complete the transaction manually.',
		'pp_eddvat_error_message' => 'VAT number validation failed; please check your VAT number or try again in a few minutes.'
	);
	foreach ($defaultOptions as $option => $value) {
		if (edd_get_option($option, null) === null)
			edd_update_option($option, $value);
	}
}

?>