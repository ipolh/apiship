<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$moduleID = "ipol.apiship";
CModule::IncludeModule($moduleID);
CModule::IncludeModule("sale");
global $MESS;
$MESS = apishipHelper::getCourierLang();

// echo "<pre>";print_r($_REQUEST);echo "</pre>";

$OrderIDs = $_REQUEST["orderIDs"];
if (empty($OrderIDs))
	die("Empty orderIDs");

$dogovorStr = "";
$dog_num = COption::GetOptionString($moduleID, "DogovorNum", "");
$dog_date = COption::GetOptionString($moduleID, "DogovorDate", "");
if (!empty($dog_num) && !empty($dog_date))
	$dogovorStr = 
		GetMessage("IPOLapiship_COUR_SYMB_NUMBER").
		$dog_num.
		GetMessage("IPOLapiship_COUR_SYMB_OT").
		$dog_date;
	
$companyName = COption::GetOptionString($moduleID, "StorecompanyName", "");
$contactName = COption::GetOptionString($moduleID, "StorecontactName", "");

$dbOrders = CSaleOrder::GetList(
	array(),
	array("ID" => $OrderIDs)
);

$arOrders = array();
while($arOrder = $dbOrders->Fetch())
{
	if (!empty($arOrder["ACCOUNT_NUMBER"]))
	{
		$ordNum = $arOrder["ACCOUNT_NUMBER"];
		$ordNum = preg_replace("/ /", "", $ordNum);
	}
	else
		$ordNum = $arOrder["ID"];
	
	$arOrders[$arOrder["ID"]] = $ordNum;
}

$city = COption::GetOptionString($moduleID,'departure','');
$apishipcity = apishipHelper::getNormalCityByLocationID($city);

?>
<style>
body{
	text-align: justify;
}

.IPOLapiship_goods_table{
	width: 100%;
	border-collapse: collapse;
}
.IPOLapiship_goods_table td, .IPOLapiship_goods_table th{
	padding: 3px 5px;
	border: 1px solid black;
}
.IPOLapiship_sign_table{
	width: 100%;
	border: none;
}
.IPOLapiship_sign_table td{
	width: 50%;
}
.IPOLapiship_mark_place{
	padding: 30px 10px;
}
.IPOLapiship_sign_place{
	height: 20px;
}
.IPOLapiship_sign_place div{
	height: 13px;
	float: left;
}
.IPOLapiship_sign_place div.first{
    width: 50%;
	border-bottom: 1px solid black;
}
.IPOLapiship_sign_place div.second{
	width: 30%;
	border-bottom: 1px solid black;
}
.IPOLapiship_sign_place div.third{
	width: 40%;
	border-bottom: 1px solid black;
}
.IPOLapiship_sign_place div.fourth{
	width: 82%;
	border-bottom: 1px solid black;
}
.IPOLapiship_sign_whitespace{
	height: 10px;
}
</style>

<body>
<div style = "width: 600px;">
<p style = "font-weight: bold;"><?=GetMessage("IPOLapiship_COUR_TITLE");?></p>

<p style = "font-weight: bold;"><?=GetMessage("IPOLapiship_COUR_SUBTITLE", array("#DOGOVOR_STR#" => $dogovorStr));?></p>

<p><?=$apishipcity["NAME"]?></p>
<p><?=date('d.m.Y')?></p>

<p style = ""><?=GetMessage("IPOLapiship_COUR_PARAG1", array("#DOGOVOR_STR#" => $dogovorStr, "#COMPANY_NAME#" => $companyName));?></p>
<table class = "IPOLapiship_goods_table">
	<thead>
		<th><?=GetMessage("IPOLapiship_COUR_TABLE1");?></th>
		<th><?=GetMessage("IPOLapiship_COUR_TABLE2");?></th>
		<th><?=GetMessage("IPOLapiship_COUR_TABLE3");?></th>
	</thead>
	<tbody>
	<?
	$num = 0;
	foreach($arOrders as $oID => $oNum)
	{
		$num++;
		?>
		<tr>
		<td><?=$num?></td>
		<td><?=$oNum?></td>
		<td>1</td>
		</tr>
		<?
	}
	?>
	
	<tr>
		<td colspan = "2"><?=GetMessage("IPOLapiship_COUR_TABLE4");?></td>
		<td><?=$num?></td>
	</tr>
	</tbody>
</table>

<p style = ""><?=GetMessage("IPOLapiship_COUR_TABLE_SIGN", array("#KOLVO#" => $num));?></p>

<p style = ""><?=GetMessage("IPOLapiship_COUR_PARAG2");?></p>
<p style = ""><?=GetMessage("IPOLapiship_COUR_PARAG3");?></p>

<table class = "IPOLapiship_sign_table">
	<tr>
		<td><?=GetMessage("IPOLapiship_COUR_FORM_SIGN1");?></td>
		<td><?=GetMessage("IPOLapiship_COUR_FORM_SIGN2");?></td>
	<tr>
	<tr>
		<td><?=GetMessage("IPOLapiship_COUR_FORM_SIGN3", array("#CONTACT_FIO#" => $contactName, "#COMPANY_NAME#" => $companyName));?></td>
		<td><?=GetMessage("IPOLapiship_COUR_FORM_SIGN4");?></td>
	<tr>
	<tr>
		<td class = "IPOLapiship_sign_place"><div class = "first"></div><div class = "slah">/</div><div class = "second"></div></td>
		<td class = "IPOLapiship_sign_place"><div class = "first"></div><div class = "slah">/</div><div class = "second"></div></td>
	<tr>
	<tr>
		<td class = "IPOLapiship_sign_place"><div class = "third"></div></td>
		<td class = "IPOLapiship_sign_place"><div class = "third"></div></td>
	<tr>
	<tr>
		<td class = "IPOLapiship_sign_whitespace"></td>
		<td class = "IPOLapiship_sign_whitespace"></td>
	<tr>
	<tr>
		<td class = "IPOLapiship_sign_place"><div class = "fourth"></div></td>
		<td class = "IPOLapiship_sign_place"><div class = "fourth"></div></td>
	<tr>
	<tr>
		<td class = "IPOLapiship_sign_place"><div class = "fourth"></div></td>
		<td class = "IPOLapiship_sign_place"><div class = "fourth"></div></td>
	<tr>
	<tr>
		<td><?=GetMessage("IPOLapiship_COUR_FORM_SIGN5");?></td>
		<td><?=GetMessage("IPOLapiship_COUR_FORM_SIGN6");?></td>
	<tr>
	<tr>
		<td class = "IPOLapiship_mark_place"><?=GetMessage("IPOLapiship_COUR_FORM_SIGN7");?></td>
		<td class = "IPOLapiship_mark_place"><?=GetMessage("IPOLapiship_COUR_FORM_SIGN7");?></td>
	<tr>
</table>

</div>
</body>