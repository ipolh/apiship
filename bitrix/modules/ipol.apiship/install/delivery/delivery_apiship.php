<?
$module_id="ipol.apiship";
CModule::IncludeModule($module_id);

// установим метод CDeliveryapiship::Init в качестве обработчика события
if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$module_id.'/classes/general/apishipdelivery.php'))
	AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CDeliveryapiship', 'Init')); 
?>