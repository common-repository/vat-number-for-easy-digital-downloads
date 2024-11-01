<?php
/**
 * Author: Potent Plugins
 * License: GNU General Public License version 2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 */
if (!defined('ABSPATH')) exit;
class PP_VIES {
	
	private static $wsdl = 'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';
	
	public static function validate($vatNumber) {
		$vatCountry = substr($vatNumber, 0, 2);
		$vatNumber = substr($vatNumber, 2);

		$service = new SoapClient(PP_VIES::$wsdl);
		try {
			$response = $service->checkVat(array('countryCode' => $vatCountry, 'vatNumber' => $vatNumber));
		} catch (Exception $ex) {
			return false;
		}
		if (!isset($response->valid))
			return false;
		return array(
			'valid' => $response->valid,
			'name' => (empty($response->name) ? '' : $response->name),
			'address' => (empty($response->address) ? '' : $response->address),
			'time' => current_time('timestamp'),
			'source' => 'VIES'
		);
	}
}
?>