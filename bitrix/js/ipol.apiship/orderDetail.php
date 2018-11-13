<?
$OrderID = $_REQUEST['ID'];
$orderinfo = CSaleOrder::GetByID($OrderID);//параметры заказа

$MODULE_ID = "ipol.apiship";
if(
	COption::GetOptionString(self::$MODULE_ID,'showInOrders','Y') == 'N' &&
	(
		!preg_match('/apiship:/', $orderinfo['DELIVERY_ID']) //||
		// !in_array($orderinfo['DELIVERY_ID'], apishipHelper::getDeliveryProfilesIDs())
	)
)
	return;

$deliveryType = false;

if (preg_match('/apiship:courier/', $orderinfo['DELIVERY_ID']))
	$deliveryType = "courier";
if (preg_match('/apiship:pickup/', $orderinfo['DELIVERY_ID']))
	$deliveryType = "pickup";

CJSCore::Init(array("jquery"));

if(!$status)
	$status='NEW';

// собираем из свойств заказа данные в соответствии с настройками модуля
$arUserProfile = CSaleOrderPropsValue::GetList(
	array(),
	array("ORDER_ID" => $OrderID),
	false,
	false,
	array()
);

$arUserOptionsKeys = array(
	"CityTo" => COption::GetOptionString(self::$MODULE_ID, "location"),
	"postIndex" => COption::GetOptionString(self::$MODULE_ID, "zip"),
	"street" => COption::GetOptionString(self::$MODULE_ID, "street"),
	"house" => COption::GetOptionString(self::$MODULE_ID, "house"),
	"block" => COption::GetOptionString(self::$MODULE_ID, "block"),
	"office" => COption::GetOptionString(self::$MODULE_ID, "flat"),
	"phone" => COption::GetOptionString(self::$MODULE_ID, "phone"),
	"email" => COption::GetOptionString(self::$MODULE_ID, "email"),
	"contactName" => COption::GetOptionString(self::$MODULE_ID, "name"),
	"Address" => COption::GetOptionString(self::$MODULE_ID, "address")
);

$orderPropsMas = array();
while($pr = $arUserProfile->Fetch())
{
	$orderPropsMas[$pr["CODE"]] = $pr;
	
	foreach($arUserOptionsKeys as $key => $val)
	{
		if ($val == $pr["CODE"])
			$ordrVals["pvzInfo"][$key] = $pr["VALUE"];
	}	
	
	if ($pr["CODE"] == "IPOLAPISHIP_CNTDTARIF")
	{
		$arProp = unserialize($pr["VALUE"]);
		$ordrVals['GABS'] = $arProp["goods"];
	}
}
// если есть запись с данными заказа в БД, берем данные оттуда
$dbOrdrVals = array();
if ($dbOrdrVals=self::GetByOI($OrderID))
{
	$apiship_ID = $dbOrdrVals["apiship_ID"];
	$status = $dbOrdrVals['STATUS'];
	$MESS = $dbOrdrVals['MESSAGE'];
	
	$info = unserialize($dbOrdrVals["PARAMS"]);
	$ordrVals["pvzInfo"] = array_merge($ordrVals["pvzInfo"], $info["pvzInfo"]);
}

if (!empty($ordrVals["pvzInfo"]["apiship_pvzID"]))
	$deliveryType = "pickup";
else
	$deliveryType = "courier";

$arSQLProp = sqlapishipOrders::select(array(),array("ORDER_ID"=>$OrderID))->Fetch();
if (!empty($arSQLProp["PARAMS"]))
{
	$arProp = unserialize($arSQLProp["PARAMS"]);
	$ordrVals['GABS'] = $arProp["goods"];
}

// проверяем габариты и высталяем дефолтные, если где-то нулевой габарит
$setWeight = false;
if ($ordrVals['GABS']["W"] <= 0)
	$setWeight = true;

$setGabs = false;
$isGabs = false;
foreach ($ordrVals['GABS'] as $key => $gaab)
{
	$isGabs = true;
	if ($key != "W" && $gaab <= 0)
		$setGabs = true;
}

if ($setWeight)
	$ordrVals['GABS']["W"] = COption::GetOptionString($MODULE_ID, "weightD", "1000");
if ($setGabs || !$isGabs)
{
	$ordrVals['GABS']["D_W"] = COption::GetOptionString($MODULE_ID, "widthD", "30");
	$ordrVals['GABS']["D_L"] = COption::GetOptionString($MODULE_ID, "lengthD", "40");
	$ordrVals['GABS']["D_H"] = COption::GetOptionString($MODULE_ID, "heightD", "20");
}

if($deliveryType == "courier" && IsModuleInstalled('ipol.kladr')){ // ибо кладр = хорошо
	$propCode = COption::GetOptionString(self::$MODULE_ID,'address','');
	
	if($propCode && $orderPropsMas[$propCode]){
		$containment = explode(",",$orderPropsMas[$propCode]["VALUE"]);
		if(is_numeric($containment[0])) $start = 2;
		else $start = 1;		
		if($containment[$start])
		{
			$orderPropsMas['address'] = ''; 
			$ordrVals["pvzInfo"]['street'] = trim($containment[$start]);
		}
		if($containment[($start+1)])
		{ 
			$containment[($start+1)] = trim($containment[($start+1)]); 
			$ordrVals["pvzInfo"]['house'] = trim(substr($containment[($start+1)],strpos($containment[($start+1)]," ")));
			// $ordrVals['block'] = 0;
		}
		if($containment[($start+2)])
		{ 
			$containment[($start+2)] = trim($containment[($start+2)]);
			$ordrVals["pvzInfo"]['office']  = trim(substr($containment[($start+2)],strpos($containment[($start+2)]," ")));
		}
	}
}

// заполняем "-" пустые поля адреса
if (empty($ordrVals["pvzInfo"]["street"]))
	$ordrVals["pvzInfo"]["street"] = "-";
if (empty($ordrVals["pvzInfo"]["house"]))
	$ordrVals["pvzInfo"]["house"] = "-";
if (empty($ordrVals["pvzInfo"]["block"]))
	$ordrVals["pvzInfo"]["block"] = "-";
if (empty($ordrVals["pvzInfo"]["office"]))
	$ordrVals["pvzInfo"]["office"] = "-";

// платежные системы
$tmpPaySys = unserialize(COption::GetOptionString(self::$MODULE_ID, "paySystems"));
if (in_array($orderinfo["PAY_SYSTEM_ID"], $tmpPaySys))
	$payment = true;
else
	$payment = false;

// название города
$arCity = apishipHelper::getNormalCity($ordrVals["pvzInfo"]["CityTo"]);
$cityName = $arCity["NAME"];

if (!CDeliveryapiship::$CompabilityPerform)
{
	$arOrdr = array("ID" => $OrderID, "PRICE" => $orderinfo["PRICE"], "LOCATION_TO" => $ordrVals["pvzInfo"]["CityTo"]);
	if (!$res = CDeliveryapiship::Compability($arOrdr))
		return;
}

// устанавливаем город и область отправителя из настроек главного модуля
$cityDef = COption::GetOptionString($MODULE_ID,'departure', "");

if (!$cityDef)
	return false;
else
	$cityFrom = apishipHelper::getNormalCity($cityDef);

// собираем пвз города отправителя
$pvzFilter = array(
	"city" => $cityFrom["NAME"],
	"availableOperation" => "1,3"
);
$reqPVZ = self::GetPVZ($pvzFilter);

foreach ($reqPVZ as $key => $val)
{
	if ($val["availableOperation"] == 2)
		continue;
	
	$pickupPVZ[$val["providerKey"]][$val["id"]] = $val;
}
unset($reqPVZ);

$defaultPickupPVZ = unserialize(COption::GetOptionString(self::$MODULE_ID, "defPickupPVZs", array()));


// получаем провайдеров, у которых необходимо задавать пункт приема товара
$arPickupProviderReq = apishipHelper::getPickupProviders();
if (empty($arProp["pvzInfo"]["providerKey"]) || $arProp["pvzInfo"]["providerKey"] == "false")
{
	foreach (CDeliveryapiship::$bestsTariffs as $key => $val)
		foreach ($val as $providerKey => $val1)
			if (empty($arProp["pvzInfo"]["providerKey"]) || $arProp["pvzInfo"]["providerKey"] == "false")
				$arProp["pvzInfo"]["providerKey"] = $providerKey;
}

if (isset($arProp["pvzInfo"]["payerPayment"]))
	$orderinfo["PRICE"] = $arProp["pvzInfo"]["payerPayment"];
?>
<style type='text/css'>
	.PropWarning{
		background: url('/bitrix/images/<?=self::$MODULE_ID?>/trouble.png') no-repeat transparent;
		background-size: contain;
		display: inline-block;
		height: 12px;
		position: relative;
		width: 12px;
	}
	.PropWarning:hover{
		background: url('/bitrix/images/<?=self::$MODULE_ID?>/trouble.png') no-repeat transparent !important;
		background-size: contain !important;
	}
	.PropHint {
		background: url('/bitrix/images/<?=self::$MODULE_ID?>/hint.gif') no-repeat transparent;
		display: inline-block;
		height: 12px;
		position: relative;
		width: 12px;
	}
	.PropHint:hover{background: url('/bitrix/images/<?=self::$MODULE_ID?>/hint.gif') no-repeat transparent !important;}
	.b-popup {
		background-color: #FEFEFE;
		border: 1px solid #9A9B9B;
		box-shadow: 0px 0px 10px #B9B9B9;
		display: none;
		font-size: 12px;
		padding: 19px 13px 15px;
		position: absolute;
		top: 38px;
		width: 300px;
		z-index: 12;
	}
	.b-popup .pop-text {
		margin-bottom: 10px;
		color:#000;
	}
	.pop-text i {color:#AC12B1;}
	.b-popup .close {
		background: url('/bitrix/images/<?=self::$MODULE_ID?>/popup_close.gif') no-repeat transparent;
		cursor: pointer;
		height: 10px;
		position: absolute;
		right: 4px;
		top: 4px;
		width: 10px;
	}
	#IPOLapiship_wndOrder{
		width: 100%;
	}
	#IPOLapiship_allTarifs{
		border-collapse: collapse;
		width: 100%;
	}
	#IPOLapiship_allTarifs td{
		border: 1px dotted black;
		padding: 3px;
	}
	#IPOLapiship_tarifWarning{
		display:none;
	}
	
	#IPOLapiship_wndOrder textarea, #IPOLapiship_wndOrder input[type="text"], #IPOLapiship_pickupPVZ, #IPOLapiship_tariffs,
	#gear_form textarea
	{
		width: 240px;
	}
	
	
</style>
<script>

// добавляем кнопку
$(document).ready(function(){
	var btn = $('[onclick="IPOLapiship_Sender.ShowDialog()"]');
	if (btn.length <= 0)
	{
		$('.adm-detail-toolbar').find('.adm-detail-toolbar-right').prepend("<a href='javascript:void(0)' onclick='IPOLapiship_Sender.ShowDialog()' class='adm-btn'><?=GetMessage('IPOLapiship_JSC_SOD_BTNAME')?></a>");
		btn = $('[onclick="IPOLapiship_Sender.ShowDialog()"]');
	}
	switch(IPOLapiship_Sender.status){
		case 'NEW'    : break;
		case 'uploadingError'  : btn.css('color','#F13939'); break;
		default       : btn.css('color','#3A9640'); break;
	}
});

if (typeof IPOLapiship_Sender)
	var IPOLapiship_Sender = {
		orderinfo: <?=CUtil::PhpToJSObject($orderinfo)?>,
		PVZ: <?=CUtil::PhpToJSObject(CDeliveryapiship::$cityPVZs)?>,
		pickupPVZ: <?=CUtil::PhpToJSObject($pickupPVZ)?>,
		status: "<?=$status?>",
		orderID: "<?=$OrderID?>",
		tariffs: <?=CUtil::PhpToJSObject(CDeliveryapiship::$bestsTariffs)?>,
		PVZTariffs: <?=CUtil::PhpToJSObject(CDeliveryapiship::$arPVZAvailTariff)?>,
		location: "<?=$ordrVals["pvzInfo"]["CityTo"]?>",
		pickupProviderRequire: <?=CUtil::PhpToJSObject($arPickupProviderReq)?>,
		
		inputs:{
			providers: "#IPOLapiship_providers",
			
			delivType: "#IPOLapiship_deliveryTypes",
			
			tariffs: "#IPOLapiship_tariffs",
			tariff_price: "#IPOLapiship_tariff_price",
			tariff_transit: "#IPOLapiship_tariffs_transit",
			payerPayment: "#IPOLapiship_payerPayment",
			
			pvz: "#IPOLapiship_PVZ",
			pickup_pvz: "#IPOLapiship_pickupPVZ",
		},
		
		curVals:{
			provider: "<?=$arProp["pvzInfo"]["providerKey"]?>",
			pvz_id: "<?=$arProp["pvzInfo"]["apiship_pvzID"]?>",
			tariffId: "<?=$arProp["pvzInfo"]["tariffId"]?>",
			delivType: "<?=$deliveryType?>",
			
			pickupType: "",
			pickup_pvz_id: <?=CUtil::PhpToJSObject($defaultPickupPVZ)?>,
			
			goods: <?=CUtil::PhpToJSObject($ordrVals['GABS'])?>,// так вышло - это габариты
		},
		
		deliveryData:{
			"Address": "<?=preg_replace("/\\r\\n/", "", $ordrVals["pvzInfo"]["Address"])?>",
			"postIndex": "<?=$ordrVals["pvzInfo"]["postIndex"]?>",
			"street": "<?=$ordrVals["pvzInfo"]["street"]?>",
			"house": "<?=$ordrVals["pvzInfo"]["house"]?>",
			"block": "<?=$ordrVals["pvzInfo"]["block"]?>",
			"office": "<?=$ordrVals["pvzInfo"]["office"]?>",
		},
		
		// recipientData: {
			// "contactName": "<?=$ordrVals["contactName"]?>",
			// "phone": "<?=$ordrVals["phone"]?>",
			// "email": "<?=$ordrVals["email"]?>",
			// "comment": "<?=$ordrVals["comment"]?>",
		// },
		
		
		Dialog: false,
		
		ShowDialog: function()
		{
			var prntButStat = 'style="display:none"',
				savButStat = '',
				delButStat = 'style="display:none"',
				courierButStat = 'style="display:none"';
				
			if(IPOLapiship_Sender.status != 'NEW' && IPOLapiship_Sender.status != 'uploadingError')
			{
				prntButStat='';
				courierButStat='';
				savButStat = 'style="display:none"';
			}
			
			if (!IPOLapiship_Sender.Dialog)
			{
				var html = $('#IPOLapiship_wndOrder').parent().html();
				$('#IPOLapiship_wndOrder').parent().remove();
				
				IPOLapiship_Sender.Dialog = new BX.CDialog({
					title: "<?=GetMessage('IPOLapiship_JSC_SOD_WNDTITLE')?>",
					content: html,
					icon: 'head-block',
					resizable: true,
					draggable: true,
					height: '500',
					width: '475',
					buttons: [
						// сохранить
						'<input type=\"button\" value=\"<?=GetMessage('IPOLapiship_JSC_SOD_SAVE')?>\"  '+savButStat+'onclick=\"IPOLapiship_Sender.SaveAndSendParams(\'saveOrderParams\');\"/>',
					
						// сохранить и отправить
						'<input type=\"button\" value=\"<?=GetMessage('IPOLapiship_JSC_SOD_SAVESEND')?>\"  '+savButStat+'onclick=\"IPOLapiship_Sender.SaveAndSendParams();\"/>',
						
						// удалить
						'<input type=\"button\" value=\"<?=GetMessage('IPOLapiship_JSC_SOD_DELETE')?>\" '+delButStat+' onclick=\"IPOLapiship_Sender.delReq()\"/>', 
						
						// печать штрихкода
						'<input type=\"button\" id=\"IPOLMSHP_PRINT\" value=\"<?=GetMessage('IPOLapiship_JSC_SOD_PRNTSH')?>\" '+prntButStat+' onclick="IPOLapiship_Sender.printDocs(\''+IPOLapiship_Sender.orderID+'\'); return false;"/>',
						
						// вызов курьера
						'<input type=\"button\" id=\"IPOLMSHP_COURIE_CALL\" value=\"<?=GetMessage('IPOLapiship_JSC_SOD_COURTITLE')?>\" '+courierButStat+' onclick="IPOLapiship_Sender.showCourierForm(); return false;"/>',
					]
				});
			}
			
			IPOLapiship_Sender.fillInputs();// заполняем все поля
			IPOLapiship_Sender.fillCallbacks();// вешаем обработчики
			IPOLapiship_Sender.Dialog.Show();
		},
		
		fillCallbacks: function()
		{
			$(IPOLapiship_Sender.inputs.providers).on("change", function(){
				IPOLapiship_Sender.changeProvider();
			});
			
			$(IPOLapiship_Sender.inputs.delivType).on("change", function(){
				IPOLapiship_Sender.changeDeliveryType();
			});
			
			$(IPOLapiship_Sender.inputs.tariffs).on("change", function(){
				IPOLapiship_Sender.changeTariff();
			});
			
			$(IPOLapiship_Sender.inputs.pvz).on("change", function(){
				IPOLapiship_Sender.changePVZ();
			});
			
			$(IPOLapiship_Sender.inputs.pickup_pvz).on("change", function(){
				IPOLapiship_Sender.changePickupPVZ();
			});
			
		},
		
		fillInputs: function()
		{
			IPOLapiship_Sender.fillProviders();
			IPOLapiship_Sender.fillDeliveryType();
			IPOLapiship_Sender.fillTariffs();
			IPOLapiship_Sender.fillPVZ();
			IPOLapiship_Sender.fillPickupPVZ();
			IPOLapiship_Sender.fillDataInputs();
		},
		
		fillProviders: function()
		{
			var providerList = {};
			
			for (var i in IPOLapiship_Sender.tariffs)
				for (var k in IPOLapiship_Sender.tariffs[i])
					if (k == IPOLapiship_Sender.curVals.provider)
						providerList[k] = true;
					else
						providerList[k] = false;
			
			var html = "";
			for (var i in providerList)
			{
				html += "<option";
				if (providerList[i])
					html += " selected";
				html += ">"+ i +"</option>";
			}
			
			$(IPOLapiship_Sender.inputs.providers).html(html);
		},
		
		fillDeliveryType: function()
		{
			var delivTypes = {
					"courier": "<?=GetMessage("IPOLapiship_JS_SOD_deliveryCourier")?>",
					"pickup": "<?=GetMessage("IPOLapiship_JS_SOD_deliveryPickup")?>"
				},
				html = "";
			
			for (var i in delivTypes)
			{
				html += "<option value = '"+ i +"'";
				
				if (i == IPOLapiship_Sender.curVals.delivType)
					html += " selected";
				
				html += ">" + delivTypes[i];
				html += "</option>";
			}
			
			$(IPOLapiship_Sender.inputs.delivType).html(html);
			
			// проверяем какие поля показывать, какие скрывать
			if (IPOLapiship_Sender.curVals.delivType == "courier")
			{
				$("[data-deliveryType='courier']").css("display", "table-row");
				$("[data-deliveryType='pickup']").css("display", "none");
			}
			else
			{
				$("[data-deliveryType='courier']").css("display", "none");
				$("[data-deliveryType='pickup']").css("display", "table-row");
			}
		},
		
		fillTariffs: function()
		{
			var tariffKey;
			
			if (IPOLapiship_Sender.curVals.delivType == "courier")
				tariffKey = "deliveryToDoor";
			else
				tariffKey = "deliveryToPoint";
			
			var html = "",
				price,
				transit;
					
			var tariffList = false;
			
			if (typeof IPOLapiship_Sender.tariffs[tariffKey][IPOLapiship_Sender.curVals.provider] != "undefined")
				tariffList = IPOLapiship_Sender.tariffs[tariffKey][IPOLapiship_Sender.curVals.provider].tariffs;
			
			html += "<option value = ''></option>";
			for (var i in tariffList)
			{
				html += "<option";
				if (i == IPOLapiship_Sender.curVals.tariffId)
				{
					html += " selected";
					price = tariffList[i].deliveryCost;
					transit = IPOLapiship_Sender.getTransitStr(tariffList[i].daysMin, tariffList[i].daysMax);
					
					// устанавливаем тип забора для данного тарифа
					IPOLapiship_Sender.curVals.pickupType = tariffList[i].from;
				}
				html += " value = '"+i+"'>";
				html += tariffList[i].tariffName;
				html += " (" + tariffList[i].deliveryCost + " <?=GetMessage("IPOLapiship_JS_SOD_RUB")?>)";
				html += "</option>";
			}
			
			$(IPOLapiship_Sender.inputs.tariffs).html(html);
			$(IPOLapiship_Sender.inputs.tariff_price).html(price);
			$(IPOLapiship_Sender.inputs.tariff_transit).html(transit);
			
			// с получателя
			var payerPayment = IPOLapiship_Sender.orderinfo["PRICE"];
			$(IPOLapiship_Sender.inputs.payerPayment).val(payerPayment);
		},
		
		getTransitStr: function(daysMin, daysMax)
		{
			if (daysMin == daysMax)
				return daysMax;
			else
				return daysMin + " - " + daysMax;
		},
		
		fillPVZ: function()
		{
			var html = "";
			
			html += "<option value = ''></option>";
			for (var i in IPOLapiship_Sender.PVZ)
			{
				if (IPOLapiship_Sender.PVZ[i].providerKey == IPOLapiship_Sender.curVals.provider)
				{
					// проверяем доступен ли пвз в выбранном тарифе
					var isPVZavail = false;
					
					for (var k in IPOLapiship_Sender.PVZTariffs[IPOLapiship_Sender.curVals.provider][i])
						if (IPOLapiship_Sender.PVZTariffs[IPOLapiship_Sender.curVals.provider][i][k] == IPOLapiship_Sender.curVals.tariffId)
							isPVZavail = true;
						
					if (isPVZavail)
					{
						html += "<option value = '"+ i + "'";
						
						if (i == IPOLapiship_Sender.curVals.pvz_id)
							html += " selected";
						
						html += ">";
						
						html += IPOLapiship_Sender.PVZ[i].street;
						
						html += "</option>";
					}
				}
			}
			
			$(IPOLapiship_Sender.inputs.pvz).html(html);
		},
		
		chekProviderPickup: function()
		{
			for (var i in IPOLapiship_Sender.pickupProviderRequire)
				if (IPOLapiship_Sender.pickupProviderRequire[i] == IPOLapiship_Sender.curVals.provider)
					return true;
			
			return false;
		},		
		
		fillPickupPVZ: function()
		{
			// проверяем есть ли доставщик в списке на вывод поля с выбором точки забора
			var chekProviderPickup = IPOLapiship_Sender.chekProviderPickup();
			
			if (IPOLapiship_Sender.curVals.pickupType == "door" || !chekProviderPickup)
				$("[data-pickuptype='pickup']").css("display", "none");
			else
				$("[data-pickuptype='pickup']").css("display", "table-row");
			
			var html = "",
				setFirstPickupPVZ = false;
				
			if (!IPOLapiship_Sender.curVals.pickup_pvz_id)
					IPOLapiship_Sender.curVals.pickup_pvz_id = {};
			
			if (IPOLapiship_Sender.curVals.pickupType == "pointOrDoor")
				html += "<option value = ''></option>";
			else
				if (IPOLapiship_Sender.curVals.pickupType == "point")
					setFirstPickupPVZ = true;// тут надо установить первый попавшийся пвз выбраным
			
			for (var i in IPOLapiship_Sender.pickupPVZ[IPOLapiship_Sender.curVals.provider])
			{
				if (setFirstPickupPVZ)
				{
					IPOLapiship_Sender.curVals.pickup_pvz_id[IPOLapiship_Sender.curVals.provider] = i;
					setFirstPickupPVZ = false;
				}
				
				html += "<option value = '"+ i + "'";
						
				if (i == IPOLapiship_Sender.curVals.pickup_pvz_id[IPOLapiship_Sender.curVals.provider])
					html += " selected";
				
				html += ">";
				
				html += IPOLapiship_Sender.pickupPVZ[IPOLapiship_Sender.curVals.provider][i].street;
				
				html += "</option>";
			}
			
			$(IPOLapiship_Sender.inputs.pickup_pvz).html(html);
		},
		
		fillDataInputs: function()
		{
			if (IPOLapiship_Sender.curVals.delivType == "courier")
			{
				for (var i in IPOLapiship_Sender.deliveryData)
					$("#IPOLapiship_"+i).val(IPOLapiship_Sender.deliveryData[i]);
			}
			
			if (IPOLapiship_Sender.curVals.delivType == "pickup")
			{
				
				for (var i in IPOLapiship_Sender.deliveryData)
				{		
					if (IPOLapiship_Sender.curVals.pvz_id)
					{
						if (typeof IPOLapiship_Sender.PVZ[IPOLapiship_Sender.curVals.pvz_id] == "undefined")
							$("#pickupPVZMessage").html("<?=GetMessage("IPOLapiship_JS_SOD_PVZNotAvailable")?>" + IPOLapiship_Sender.curVals.pvz_id);
						else
							if (typeof IPOLapiship_Sender.PVZ[IPOLapiship_Sender.curVals.pvz_id][i] != "undefined")
								if (IPOLapiship_Sender.PVZ[IPOLapiship_Sender.curVals.pvz_id][i].length > 0)
									$("#IPOLapiship_"+i).val(IPOLapiship_Sender.PVZ[IPOLapiship_Sender.curVals.pvz_id][i]);
								else
									$("#IPOLapiship_"+i).val("-");
							else
								$("#IPOLapiship_"+i).val("-");
					}
					else
						$("#IPOLapiship_"+i).val("");
				}
			}
		},
		
		// обработчики
		changeProvider: function()
		{
			var selectedProvider = $(IPOLapiship_Sender.inputs.providers).find(":selected").val();
			IPOLapiship_Sender.curVals.provider = selectedProvider;
			
			IPOLapiship_Sender.curVals.tariffId = false;
			IPOLapiship_Sender.curVals.pvz_id = false;
			
			IPOLapiship_Sender.fillInputs();// заполняем все поля
		},
		
		changeDeliveryType: function()
		{
			var selectedDeliveryType = $(IPOLapiship_Sender.inputs.delivType).find(":selected");
			IPOLapiship_Sender.curVals.delivType = selectedDeliveryType.val();
			
			IPOLapiship_Sender.curVals.tariffId = false;
			IPOLapiship_Sender.curVals.pvz_id = false;
			
			IPOLapiship_Sender.fillInputs();// заполняем все поля
		},
		
		changeTariff: function()
		{
			var selectedTariff = $(IPOLapiship_Sender.inputs.tariffs).find(":selected");
			IPOLapiship_Sender.curVals.tariffId = selectedTariff.val();
			
			IPOLapiship_Sender.curVals.pvz_id = false;
			
			IPOLapiship_Sender.fillInputs();// заполняем все поля
		},
		
		changePVZ: function()
		{
			var selectedPVZ = $(IPOLapiship_Sender.inputs.pvz).find(":selected");
			IPOLapiship_Sender.curVals.pvz_id = selectedPVZ.val();
			
			IPOLapiship_Sender.fillInputs();// заполняем все поля
		},
		
		changePickupPVZ: function()
		{
			var selectedPVZ = $(IPOLapiship_Sender.inputs.pickup_pvz).find(":selected");
			
			if (!IPOLapiship_Sender.curVals.pickup_pvz_id)
				IPOLapiship_Sender.curVals.pickup_pvz_id = {};
			
			IPOLapiship_Sender.curVals.pickup_pvz_id[IPOLapiship_Sender.curVals.provider] = selectedPVZ.val();
		},
		
		// собираем инфу с формы
		getFormData: function()
		{
			var arReturn = {};
			for(var i in IPOLapiship_Sender.deliveryData)
				arReturn[i] = $("#IPOLapiship_"+i).val();
			
			arReturn["CityTo"] = $("#IPOLapiship_location").val();
			
			var personParams = ["contactName", "phone", "email", "comment", "deliveryTimeStart", "deliveryTimeEnd", "deliveryDate"];
			for(var i in personParams)
			{
				var tmpVal = $("#IPOLapiship_"+personParams[i]).val();
				if (tmpVal.length > 0)
					arReturn[personParams[i]] = tmpVal;
			}
			
			if (IPOLapiship_Sender.curVals.delivType == "pickup")
				arReturn["apiship_pvzID"] = IPOLapiship_Sender.curVals.pvz_id;
			
			// определяем pointInId и его необходимость
			if (IPOLapiship_Sender.curVals.pickupType == "door")
				arReturn["pickupType"] = 1;
			else
			{
				var pointInId = $(IPOLapiship_Sender.inputs.pickup_pvz).find("option:selected").val();
				
				if (IPOLapiship_Sender.curVals.pickupType == "point")
				{
					arReturn["pickupType"] = 2;
					arReturn["pointInId"] = pointInId;
				}
				else
				{
					var pointInId = $(IPOLapiship_Sender.inputs.pickup_pvz).find("option:selected").val();
					
					// если точка выбрана, то ставим соответственные данные
					if (pointInId)
					{
						arReturn["pickupType"] = 2;
						arReturn["pointInId"] = pointInId;
					}
					else
						arReturn["pickupType"] = 1;
				}
			}
			
			arReturn["deliveryType"] = $(IPOLapiship_Sender.inputs.delivType).find(":selected").val();
			arReturn["tariffId"] = IPOLapiship_Sender.curVals.tariffId;
			arReturn["providerKey"] = IPOLapiship_Sender.curVals.provider;
			arReturn["payerPayment"] = $(IPOLapiship_Sender.inputs.payerPayment).val();
			arReturn["goods"] = IPOLapiship_Sender.curVals.goods;
			
			console.log(arReturn);
			
			var check = IPOLapiship_Sender.checkFormData(arReturn);
			
			if (check.is_error)
				return $('#IPOLapiship_'+check.field).closest('tr').children('td').html();
			else
				return arReturn;
		},
		
		checkFormData: function(params)
		{
			var arReturn;
			// console.log(params);
			for(var i in params)
				if (i != "comment" && i != "goods" && i != "pointInId" && i != "Address")
					if (params[i].length <= 0)
					{
						arReturn = {"is_error": true, "field": i};
						console.log(i);
						return arReturn;
					}
			
			arReturn = {"is_error": false};
			return arReturn;
		},
		
		SaveAndSendParams: function(action)
		{
			if (typeof action == "undefined")
				action = "saveAndSend";
			
			var dataObject = IPOLapiship_Sender.getFormData();
			
			if(typeof dataObject != 'object')
			{
				alert('<?=GetMessage('IPOLapiship_JSC_SOD_ZAPOLNI')?> "'+dataObject+'"');
				return;
			}
			dataObject['action'] = action;
			dataObject['orderId'] = IPOLapiship_Sender.orderID;
			
			$.ajax({
				url: "/bitrix/js/<?=apishipdriver::$MODULE_ID?>/ajax.php",
				data: dataObject,
				type:"POST",
				dataType: "json",
				error: function(XMLHttpRequest, textStatus){
					console.log(XMLHttpRequest.responseText);
					console.log(textStatus);
				},
				success: function(data)
				{
					console.log(data);
					if (typeof data.is_error != "undefined")
					{
						console.log(data);
						confirm("<?=GetMessage('IPOLapiship_JSC_SOD_SEND_ORDER_ERR')?>" + data.error_msg);
					}
					else
						confirm(data);
					
				},
			});
		},
		
		printDocs: function(BXorderId)// id заказа в битрикс
		{
			$('#IPOLMSHP_PRINT').attr('disabled','true');
			$('#IPOLMSHP_PRINT').val('<?=GetMessage("IPOLapiship_JSC_SOD_LOADING")?>');
			$.ajax({
				url  : "/bitrix/js/<?=self::$MODULE_ID?>/ajax.php",
				type : 'POST',
				data : {
					action : 'printOrderInvoice',
					oId    : BXorderId
				},
				dataType : 'json',
				success  : function(data){
					$('#IPOLMSHP_PRINT').removeAttr('disabled');
					$('#IPOLMSHP_PRINT').val('<?=GetMessage("IPOLapiship_JSC_SOD_PRNTSH")?>');
					console.log(data);
					
					var errStr = "";
						
					if (data.errors)
						for (var i in data.errors)
							for (var k in data.errors[i])
							{
								console.log(data.errors[i]);
								console.log(data.errors[i][k]);
								errStr += data.errors[i][k].orderId + ": " + data.errors[i][k].message + "\n";
							}
					
					if(data.result == 'ok')
					{
						var result = true;
						if (errStr != "")
						{
							errStr = "<?=GetMessage("IPOLapiship_JSC_SOD_PRNTSH_ERROR")?>"+"\n"+"<?=GetMessage("IPOLapiship_JSC_SOD_PRNTSH_ERROR_BUT_PRINT")?>"+"\n\n" + errStr;
							var result = confirm(errStr);
						}
						
						
						if (result)
							for (var i in data.file)
								window.open('/upload/<?=self::$MODULE_ID?>/'+data.file[i]);
					}
					else
					{
						alert("<?GetMessage("IPOLapiship_JSC_SOD_PRNTSH_ERROR")."\n"?>" + errStr);
					}
				},
				error: function(XMLHttpRequest, textStatus){
					console.log(XMLHttpRequest.responseText);
					console.log(textStatus);
				}
			});
		}
		
	};
</script>


<div style='display:none'>
	<table id='IPOLapiship_wndOrder'>
		<tr>
			<td><?=GetMessage('IPOLapiship_JS_SOD_STATUS')?></td>
			<td><?=$status.$satBut?></td>
		</tr>
		
		<tr>
			<td colspan='2'><small><?=($MESS)?($MESS):GetMessage('IPOLapiship_JS_SOD_STAT_'.$status)?></small><?=$message['number']?></td>
		</tr>
		
		<?if($apiship_ID){?>
			<tr>
				<td><?=GetMessage('IPOLapiship_JS_SOD_apiship_ID')?></td>
				<td><?=$apiship_ID?></td>
			</tr>
		<?}?>
		
		<?if($MESS_ID){?>
			<tr>
				<td><?=GetMessage('IPOLapiship_JS_SOD_MESS_ID')?></td>
				<td><?=$MESS_ID?></td></tr>
		<?}?>
		
		
		
		<?//Заявка?>
		<tr class='heading'>
			<td colspan='2'><?=GetMessage('IPOLapiship_JS_SOD_HD_PARAMS')?></td>
		</tr>
		<tr>
			<td><?=GetMessage('IPOLapiship_JS_SOD_number')?></td>
			<td><?=($orderinfo['ACCOUNT_NUMBER'])?$orderinfo['ACCOUNT_NUMBER']:$orderId?></td>
		</tr>
		
		<tr style = "display: none;">
			<td><?=GetMessage('IPOLapiship_JS_SOD_isBeznal')?></td>
			<td>
				<?if($payment === true || floatval($payment) >= floatval($orderinfo['PRICE'])){?>
					<input type='checkbox' id='IPOLapiship_isBeznal' value='Y' <?=($ordrVals['isBeznal']=='Y')?'checked':''?>>
				<?}else{?>
					<input type='checkbox' id='IPOLapiship_isBeznal' value='Y' checked disabled><br>
					<?
						if(!$payment)
							echo GetMessage("IPOLapiship_JS_SOD_NONALPAY");
						else
							echo str_replace("#VALUE#",$payment,GetMessage("IPOLapiship_JS_SOD_TOOMANY"));
				}?>
			</td>
		</tr>
		
		<tr>
			<td><?=GetMessage('IPOLapiship_JS_SOD_provider')?></td>
			<td><select id = "IPOLapiship_providers"></select></td>
		</tr>
		
		<tr>
			<td><?=GetMessage('IPOLapiship_JS_SOD_deliveryTypes')?></td>
			<td><select id = "IPOLapiship_deliveryTypes"></select></td>
		</tr>
		
		<tr>
			<td><?=GetMessage('IPOLapiship_JS_SOD_tariffName')?></td>
			<td><select id = "IPOLapiship_tariffs"></select></td>
		</tr>
		
		<tr>
			<td><?=GetMessage('IPOLapiship_JS_SOD_tariffPrice')?></td>
			<td id = "IPOLapiship_tariff_price"></td>
		</tr>
		
		<tr>
			<td><?=GetMessage('IPOLapiship_JS_SOD_tariffTransit')?></td>
			<td id = "IPOLapiship_tariffs_transit"></td>
		</tr>
		
		<tr>
			<td><?=GetMessage('IPOLapiship_JS_SOD_payerPayment')?></td>
			<td><input type = "text" id = "IPOLapiship_payerPayment" value = ""></td>
		</tr>
		
		<?//Ошибки?>
		<?if(count($message['troubles'])){?>
			<tr class='heading'>
				<td colspan='2'><?=GetMessage('IPOLapiship_JS_SOD_HD_ERRORS')?></td>
			</tr>
			<tr>
				<td colspan='2'><?=$message['troubles']?></td>
			</tr>
		<?}?>
		
		<?//Адрес?>
		<tr class='heading'>
			<td colspan='2'><?=GetMessage('IPOLapiship_JS_SOD_HD_ADDRESS')?></td>
		</tr>
		<tr>
			<td><?=GetMessage('IPOLapiship_JS_SOD_location')?></td>
			<td><?=$cityName?>
				<input id='IPOLapiship_location' type='hidden' value='<?=$ordrVals["pvzInfo"]['CityTo']?>'><?=$message['location']?>
			</td>
		</tr>
		
		<?// выводим свойства адреса?>
		<?
		$arNeedOrderProps = array(
			"Address" => "textarea",
			"postIndex" => "text",
			"street" => "text",
			"house" => "text",
			"block" => "text",
			"office" => "text",
		);
		
		foreach ($arNeedOrderProps as $prop => $input)
		{
			$add_data = 'data-deliveryType = "courier"';
			if ($prop == "Address")
				$add_data = "";
			?>
			<tr <?=$add_data?>>
				<td><?=GetMessage('IPOLapiship_JS_SOD_'.$prop)?></td>
				<td>
					<?if ($input == "textarea"){?>
					<textarea id='IPOLapiship_<?=$prop?>' disabled><?=$ordrVals["pvzInfo"][$prop]?></textarea>
					<?}else{?>
					<input id='IPOLapiship_<?=$prop?>' type='text' value='<?=$ordrVals["pvzInfo"][$prop]?>'>
					<?}?>
				</td>
			</tr>
			<?
		}
		?>
		
		<?// Выводим список ПВЗ?>
		
		<tr data-deliveryType = "pickup">
			<td colspan = "2" id = "pickupPVZMessage" style = "color: red;"></td>
		</tr>
		
		<tr data-deliveryType = "pickup">
			<td><?=GetMessage('IPOLapiship_JS_SOD_PVZ')?></td>
			<td>
				<select id='IPOLapiship_PVZ'></select>
			</td>
		</tr>
		
		<?
		$providerStr = "";
		foreach($arPickupProviderReq as $val)
			if ($providerStr)
				$providerStr .= ", ".$val;
			else
				$providerStr .= $val;
		?>
		<tr data-pickupType = "pickup">
			<td colspan = "2"><br><?=preg_replace("/#pickupProviders#/", $providerStr, GetMessage('IPOLapiship_JS_SOD_pickupPVZdescritption'))?></td>
		</tr>
		<tr data-pickupType = "pickup">
			<td><?=GetMessage('IPOLapiship_JS_SOD_pickupPVZ')?></td>
			<td>
				<select id='IPOLapiship_pickupPVZ'></select>
			</td>
		</tr>
		
		<?//Получатель?>
		<tr class='heading'>
			<td colspan='2'><?=GetMessage('IPOLapiship_JS_SOD_HD_RESIEVER')?></td>
		</tr>
		<tr>
			<td><?=GetMessage('IPOLapiship_JS_SOD_name')?></td>
			<td>
				<input id='IPOLapiship_contactName' type='text' value='<?=$ordrVals["pvzInfo"]['contactName']?>'><?=$message['name']?>
			</td>
		</tr>
		<tr>
			<td valign="top"><?=GetMessage('IPOLapiship_JS_SOD_phone')?></td>
			<td>
				<input id='IPOLapiship_phone' type='text' value='<?=$ordrVals["pvzInfo"]['phone']?>'></td>
			</tr>
		<tr>
			<td valign="top"><?=GetMessage('IPOLapiship_JS_SOD_email')?></td>
			<td>
				<input id='IPOLapiship_email' type='text' value='<?=$ordrVals["pvzInfo"]['email']?>'></td>
		</tr>
		<tr>
			<td><?=GetMessage('IPOLapiship_JS_SOD_comment')?></td>
			<td><textarea id='IPOLapiship_comment'><?=$ordrVals["pvzInfo"]['comment']?></textarea><?=$message['comment']?></td>
		</tr>
		<tr>
			<td valign="top"><?=GetMessage('IPOLapiship_JS_SOD_deliveryDate')?></td>
			<td>
				<input onclick="BX.calendar({node: this, field: this, bTime: false});"  id='IPOLapiship_deliveryDate' type='text' value='<?=$ordrVals["pvzInfo"]['deliveryDate']?>'></td>
		</tr>
		<tr>
			<td valign="top"><?=GetMessage('IPOLapiship_JS_SOD_deliveryTimeStart')?></td>
			<td>
				<input id='IPOLapiship_deliveryTimeStart' type='time' value='<?=$ordrVals["pvzInfo"]['deliveryTimeStart']?>'></td>
		</tr>
		<tr>
			<td valign="top"><?=GetMessage('IPOLapiship_JS_SOD_deliveryTimeEnd')?></td>
			<td>
				<input id='IPOLapiship_deliveryTimeEnd' type='time' value='<?=$ordrVals["pvzInfo"]['deliveryTimeEnd']?>'></td>
		</tr>
		<tr><td colspan='2'>
			<div id="pop-date" class="b-popup" >
				<div class="pop-text"><?=GetMessage("IPOLapiship_JSC_SOD_HELPER_date")?></div>
				<div class="close" onclick="$(this).closest('.b-popup').hide();"></div>
			</div>
			<div id="pop-time" class="b-popup" >
				<div class="pop-text"><?=GetMessage("IPOLapiship_JSC_SOD_HELPER_time")?></div>
				<div class="close" onclick="$(this).closest('.b-popup').hide();"></div>
			</div>
		</td></tr>


		<?// О заказе?>
		<tr class='heading'><td colspan='2'><a onclick='$(".IPOLapiship_detOrder").css("display","")' href='javascript:void(0)'><?=GetMessage('IPOLapiship_JS_SOD_ABOUT')?></td></tr>
		<tr class='IPOLapiship_detOrder' style='display:none'>
			<td><?=GetMessage('IPOLapiship_JS_SOD_GABARITES')?></td>
			<td>
				<?=($ordrVals['GABS']['D_W'])*10?><?=GetMessage("IPOLapiship_mm")?> x <?=($ordrVals['GABS']['D_L'])*10?><?=GetMessage("IPOLapiship_mm")?> x <?=($ordrVals['GABS']['D_H'])*10?><?=GetMessage("IPOLapiship_mm")?>, <?=$ordrVals['GABS']['W']?><?=GetMessage("IPOLapiship_g")?><br>
			</td>
		</tr>
		
	</table>
</div>


<?include($_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$MODULE_ID."/courierDetail.php");?>