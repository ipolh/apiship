<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!$GLOBALS["USER"]->IsAdmin())
	die("Access denied!");

$module_id = "ipol.apiship";
CModule::IncludeModule($module_id);

$arModuleClass = array(
	"apishipHelper",
	"apishipdriver",
	"CDeliveryapiship"
);

foreach ($arModuleClass as $class)
	if(method_exists($class,$_POST['action']))
		call_user_func($class."::".$_POST['action'], $_POST);
?>