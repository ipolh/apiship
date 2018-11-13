<?
$module_id = 'ipol.apiship';

CModule::AddAutoloadClasses(
    $module_id,
    array(
        'apishipdriver'				 => '/classes/general/apishipclass.php',
        'CDeliveryapiship'				 => '/classes/general/apishipdelivery.php',
        'apishipHelper'				 => '/classes/general/apishiphelper.php',
		'sqlapishipOrders'				 => '/classes/mysql/sqlapishipOrders.php',
		// 'sqlapishipCity'				 => '/classes/mysql/sqlapishipCity.php',
		// 'CalculatePriceDeliveryapiship' => '/classes/apishipMercy/calculator.php',
		// 'cityExport'				 => '/classes/apishipMercy/syncCityClass.php'
        )
);
?>