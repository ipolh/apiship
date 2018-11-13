<?
IncludeModuleLangFile(__FILE__);

class apishipdriver
{
	static $MODULE_ID = "ipol.apiship";
	static $arStatusMap = array();
	static $PayDeliveredOrder = "";
	
	static $errors = array();
	///////////////////////////////////////////////////////////////////////////
	///// выполнение запроса к api апишипа ////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////
	static $clientNumber = array();
	static $setTrackNum = false;
	
	///////////////////////////////////////////////////////////////////////////
	///// Отправка заявки курьеру /////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////
	static $getTrackNum = false;
	
	///////////////////////////////////////////////////////////////////////////
	///// Создание заказа /////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////
	static $companyID = false;
	
	// получаем ПВЗ города для доставок
	// $params = array(
	// "city" => "Чебоксары"
	// "providerKey" => "box2box",
	// "limit" => 100
	// );
	
	public function GetError()
	{
		return self::$errors;
	}
	
	function sendCourierCall($params)
	{
		$data = $params['data'];
		
		$emailFrom = "";
		$rsSites = CSite::GetList($by = "sort", $order = "desc", Array())->Fetch();
		$emailFrom = COption::GetOptionString("sale", "order_email", "");
		if (empty($emailFrom))
			$emailFrom = $rsSites["EMAIL"];
		
		$error = array();
		if (empty($emailFrom))
			$error[] = GetMessage("IPOLapiship_COURIER_EMAIL_ERR_EMAIL_FROM");
		
		if (!empty($error))
		{
			$arReturn = array("success" => false, "data" => $error);
			echo json_encode(apishipHelper::zajsonit($arReturn));
			
			return false;
		}
		
		$headers = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/html; charset=utf-8\r\n";
		$headers .= "From: APISHIP Bitrix <info@ipolh.com>\r\n";
		$headers .= "Reply-To: info@ipolh.com\r\n";
		
		$subject = GetMessage("IPOLapiship_COURIER_EMAIL_TITLE");
		
		$message = '<b>' . GetMessage("IPOLapiship_COURIER_EMAIL_SHOP") . '</b> ' . $rsSites["SERVER_NAME"] . '<br>'
			. '<b>' . GetMessage("IPOLapiship_COURIER_EMAIL_ORDER_NUM") . '</b> ' . $params['order_num'] . '<br><br>';
		
		$message .= '<b>' . GetMessage("IPOLapiship_JSC_SOD_CONTACT_NAME") . '</b> ' . $data['gear_name'] . '<br>
		<b>' . GetMessage("IPOLapiship_JSC_SOD_CONTACT_PHONE") . '</b> ' . $data['gear_phone'] . '<br>
		<b>' . GetMessage("IPOLapiship_JSC_SOD_CONTACT_COMPANY") . '</b> ' . $data['gear_client'] . '<br>
		<b>' . GetMessage("IPOLapiship_JSC_SOD_COURIER_DATE") . '</b> ' . $data['gear_date'] . '<br>
		<b>' . GetMessage("IPOLapiship_JSC_SOD_VOLUME") . '</b> ' . ($data['gear_volume'] == 1 ? GetMessage("IPOLapiship_JSC_SOD_VOLUME_BEFORE_05") : GetMessage("IPOLapiship_JSC_SOD_VOLUME_AFTER_05")) . '<br>
		<b>' . GetMessage("IPOLapiship_JSC_SOD_COURIER_ADDRESS") . '</b> ' . $data['gear_address'] . '<br>
		<b>' . GetMessage("IPOLapiship_JSC_SOD_COURIER_TIME") . '</b> ' . $data['gear_time'] . '<br>
		<b>' . GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY") . '</b> ' . implode(', ', $data['gear_provider']) . '<br>';
		
		if (!empty($data['gear_comment']))
			$message .= '<b>' . GetMessage("IPOLapiship_JSC_SOD_COURIER_COMMENT_SHORT") . '</b> ' . $data['gear_comment'];
		
		$_OPTIONS['contact_email'] = 'info@gearlog.ru';
		
		mail($_OPTIONS['contact_email'], $subject, $message, $headers);
		
		$arReturn = array("success" => true, "data" => true);
		echo json_encode(apishipHelper::zajsonit($arReturn));
		
		return true;
	}
	
	// кнопка "Сохранить и отправить" в редакторе заказа
	
	function orderCreate($orderID, $orderFields, $arParams)
	{
		if ((!cmodule::includemodule('sale')) || (!self::controlProps()))
			return true;
		
		$deliveryIDs = apishipHelper::getDeliveryProfilesIDs();
		
		if (preg_match("/apiship/", $orderFields["DELIVERY_ID"]) || in_array($orderFields["DELIVERY_ID"], $deliveryIDs))
		{
			// запоминаем данные в свойство заказа, если доставка - apiship
			$arRequestValues = CDeliveryapiship::getRequestedValues();
			$providerKey = $arRequestValues["apiship_providerKey"]["VALUE"];
			
			$apishipData = array(
				"goods" => CDeliveryapiship::$goods,
				"pvzInfo" => array(
					"providerKey" => $arRequestValues["apiship_providerKey"]["VALUE"],
					"apiship_pvzID" => $arRequestValues["apiship_pvzID"]["VALUE"],
					"tariffId" => $arRequestValues["apiship_tariffId"]["VALUE"]
				)
			);
			
			// добавляем название профиля
			$bxProfileName = "(" . GetMessage("IPOLapiship_JS_SOD_deliveryPickup") . ") ";
			
			// если это курьер, то изменяем поля
			if ((preg_match("/courier/", $orderFields["DELIVERY_ID"]) || ($orderFields["DELIVERY_ID"] == $deliveryIDs["courier"])) /*&& $_REQUEST["apiship_isPickup"] == "false"*/)
			{
				$bxProfileName = "(" . GetMessage("IPOLapiship_JS_SOD_deliveryCourier") . ") ";
				
				foreach (CDeliveryapiship::$bestsTariffs["deliveryToDoorShown"] as $val)
					$tariffData = $val;
				
				$providerKey = $tariffData["providerKey"];
				
				$apishipData["pvzInfo"] = array(
					"providerKey" => $tariffData["providerKey"],
					"apiship_pvzID" => false,
					"tariffId" => $tariffData["tariffId"]
				);
			}
			
			self::Add(array('ORDER_ID' => $orderID, 'PARAMS' => serialize($apishipData), 'STATUS' => "NEW", "apiship_ID" => false, "MESSAGE" => false));
			
			// добавляем название тарифа
			$chosenTariffName = "";
			foreach (CDeliveryapiship::$bestsTariffs as $profile)
				foreach ($profile as $provider)
					foreach ($provider["tariffs"] as $tariff)
						if ($tariff["tariffId"] == $apishipData["pvzInfo"]["tariffId"])
							$chosenTariffName = $tariff["tariffName"];
			
			if (!empty($chosenTariffName))
				$chosenTariffName = " / " . $chosenTariffName;
			
			// добавляем адрес ПВЗ
			$PVZaddress = "";
			if ($apishipData["pvzInfo"]["apiship_pvzID"])
			{
				$pvzID = $apishipData["pvzInfo"]["apiship_pvzID"];
				if (CDeliveryapiship::$cityPVZs[$pvzID])
					$PVZaddress = " / " . CDeliveryapiship::$cityPVZs[$pvzID]["Address"];
			}
			
			// записываем свойство
			$arPropValues = array(
				"IPOLAPISHIP_PROVIDER" => $bxProfileName . $providerKey . $chosenTariffName . $PVZaddress,
				"IPOLAPISHIP_PVZ_DELIVERER_ID" => $pvzProviderID
			);
			
			$fillDelivPVZID = COption::getOptionString(self::$MODULE_ID, "fillDelivPVZID", "N");
			
			foreach ($arPropValues as $propCode => $propValue)
			{
				$continue = true;
				if ($propCode == "IPOLAPISHIP_PVZ_DELIVERER_ID" && $fillDelivPVZID != "Y")
					$continue = false;
				
				if ($continue)
				{
					if ($propValue)
					{
						$op = CSaleOrderProps::GetList(array(), array("PERSON_TYPE_ID" => $orderFields['PERSON_TYPE_ID'], "CODE" => $propCode))->Fetch();
						
						if ($op)
						{
							$arFields = array(
								"ORDER_ID" => $orderID,
								"ORDER_PROPS_ID" => $op['ID'],
								"NAME" => GetMessage('IPOLapiship_prop_name_' . $propCode),
								"CODE" => $propCode,
								// "VALUE" => $propValue
								"VALUE" => ($propValue == "false") ? "" : $propValue
							);
							
							$dbOrderProp = CSaleOrderPropsValue::GetList(
								array(),
								array(
									"ORDER_PROPS_ID" => $op['ID'],
									"CODE" => $propCode,
									"ORDER_ID" => $orderID
								)
							);
							
							if ($existProp = $dbOrderProp->Fetch())
								CSaleOrderPropsValue::Update($existProp["ID"], $arFields);
							else
								CSaleOrderPropsValue::Add($arFields);
						}
					}
				}
			}
		}
		
		return true;
	}
	
	function GetPVZ($params)
	{
		$obCache = new CPHPCache();
		
		// костыли для городов, например, Калининград
		$reqCity = $params["city"];
		if ($reqCity == GetMessage("IPOLapiship_EXCEPT_KALININGRAD"))
			$reqCity .= GetMessage("IPOLapiship_EXCEPT_KALININGRAD_GOROD");
		
		$cachename = "IPOLapishipPVZs|" . $params["city"];
		
		if ($obCache->InitCache(defined("IPOLapiship_CACHE_TIME") ? IPOLapiship_CACHE_TIME : 86400, $cachename, "/IPOLapiship/") && !defined("IPOLapiship_NOCACHE"))
			$arRet = $obCache->GetVars();
		else
		{
			$params["city"] = $reqCity;
			
			// формируем запрос в apiship
			$toRequest = array(
				"WHERE" => "lists/points",
				"METHOD" => "GET",
				"limit" => $params["limit"]
			);
			unset($params["limit"]);
			$toRequest["FILTER"] = $params;
			
			$req_res = apishipdriver::MakeRequest($toRequest);
			
			if ($req_res["code"] == 200)
			{
				$arRet = $req_res["result"]["rows"];
				
				$obCache->StartDataCache();
				$obCache->EndDataCache($arRet);
			}
			
		}
		
		return $arRet;
	}
	
	// отправка заказа в apiship
	
	function saveOrderParams($params, $req_res = false, $arStatus = false, $showSaveResult = true)
	{
		$orderId = $params['orderId'];
		// преобразуем params в нужный для сохранения вид
		foreach ($params as $key => $val)
		{
			if ($key == "goods" || $key == "orderId")
				continue;
			
			$params["pvzInfo"][$key] = $val;
			unset($params[$key]);
		}
		unset($params['orderId']);
		unset($params['action']);
		
		$toSave = array(
			'ORDER_ID' => $orderId,
			'PARAMS' => serialize($params),
		);
		
		if ($arStatus)
		{
			$message = $arStatus["status"]["name"];
			if (preg_match("/uploadingError/", $arStatus["status"]["key"]))
				$message .= GetMessage("IPOLapiship_uploadingError_descr") . $arStatus["status"]["description"];
			
			$toSave["STATUS"] = $arStatus["status"]["key"];
			$toSave["MESSAGE"] = $message;
			
		}
		
		if ($req_res)
			$toSave["apiship_ID"] = $req_res["orderId"];
		
		if ($newId = self::Add($toSave))
		{
			if ($showSaveResult)
				echo json_encode(apishipHelper::zajsonit(GetMessage('IPOLapiship_SOD_UPDATED')));
			
			return $newId;
		}
		else
		{
			if ($showSaveResult)
				echo json_encode(apishipHelper::zajsonit(GetMessage('IPOLapiship_SOD_NOTUPDATED')));
			
			return false;
		}
	}
	
	function saveAndSend($params)
	{
		$params = self::zaDEjsonit($params);
		$orderId = IntVal($params["orderId"]);
		$req_res = false;
		$arStatus = false;
		
		if ($req_res = self::sendOrderRequest($params))
		{
			if (!$arStatus = self::GetCurStatus($orderId))
				$arStatus = "NEW";
			
			$showSaveResult = true;
		}
		else
			$showSaveResult = false;
		
		if (!self::saveOrderParams($params, $req_res, $arStatus, $showSaveResult))
			return false;
		
		if ($req_res)
			return true;
		else
			return false;
	}
	
	// получение номера заказа по id битрикса
	
	function checkPhone($phone)
	{
		$arDelSym = array("\(", "\)", "-", " ");
		
		foreach ($arDelSym as $sym)
			$phone = preg_replace("/" . $sym . "/", "", $phone);
		
		return $phone;
	}
	
	// получение статуса заказа
	
	function sendOrderRequest(&$params)
	{
		$OrderID = IntVal($params["orderId"]);
		
		if (!CModule::IncludeModule("sale"))
			return false;
		
		if (!CModule::IncludeModule("catalog"))
			return false;
		
		// берем заказ
		$arOrder = CSaleOrder::GetList(
			array(),
			array("ID" => $OrderID),
			false,
			false,
			array()
		)->Fetch();
		
		// собираем адрес отправителя
		// берем из настроек модуля адрес и т.д. магазина-отправителя
		$arSender = array(
			"street" => COption::GetOptionString(self::$MODULE_ID, "Storestreet", ""),
			"house" => COption::GetOptionString(self::$MODULE_ID, "Storehouse", ""),
			"block" => COption::GetOptionString(self::$MODULE_ID, "Storeblock", ""),
			"office" => COption::GetOptionString(self::$MODULE_ID, "Storeoffice", ""),
			"companyName" => COption::GetOptionString(self::$MODULE_ID, "StorecompanyName", ""),
			"contactName" => COption::GetOptionString(self::$MODULE_ID, "StorecontactName", ""),
			"phone" => self::checkPhone(COption::GetOptionString(self::$MODULE_ID, "Storephone", "")),
			"email" => COption::GetOptionString(self::$MODULE_ID, "Storeemail", ""),
		);
		
		// устанавливаем город и область отправителя из настроек главного модуля
		$cityDef = COption::GetOptionString('sale', 'location', false);
		
		if (!$cityDef)
			return false;
		else
			$cityFrom = apishipHelper::getNormalCity($cityDef);
		
		$arProp = $params["goods"];// габариты
		
		// собираем корзину заказа
		$dbBasket = CSaleBasket::GetList(
			array(),
			array("ORDER_ID" => $OrderID),
			false,
			false,
			array()
		);
		
		$arOrderBasket = array();
		$arOrderBasketPrices = array();
		
		$zeroWeightCount = 0; //количество товаров с нулевым весом
		$totalWeight = 0; // суммарный вес
		$set_wight = false; // флаг, что вес товарам потом пересчитать
		
		$totalPrice = 0;// суммараная цена товаров корзины
		$basketIterator = 0;
		
		while ($arBasket = $dbBasket->Fetch())
		{
			$arDim = unserialize($arBasket["DIMENSIONS"]);
			$arDim["WEIGHT"] = $arBasket["WEIGHT"];
			
			if (floatVal($arDim["WEIGHT"]) == 0)
			{
				$set_wight = true;
				$zeroWeightCount += IntVal($arBasket["QUANTITY"]);
			}
			
			$totalWeight += IntVal($arBasket["QUANTITY"]) * IntVal($arDim["WEIGHT"]);
			
			$arOrderBasket[$basketIterator] = array(
				"articul" => $arBasket["PRODUCT_ID"],
				"description" => substr($arBasket["NAME"], 0, 99),// ограничиваем длину описания 100 символов
				"quantity" => IntVal($arBasket["QUANTITY"]),
				"height" => ceil($arDim["HEIGHT"] / 10),
				"length" => ceil($arDim["LENGTH"] / 10),
				"width" => ceil($arDim["WIDTH"] / 10),
				"weight" => IntVal($arDim["WEIGHT"]),
				"assessedCost" => round($arBasket["PRICE"], 2),// в рублях
				"cost" => round($arBasket["PRICE"], 2),// в рублях
				"costVat" => round($arBasket["VAT_RATE"]*100, 0),// в %, целое число
			);
			
			$arOrderBasketPrices[$basketIterator] = $arOrderBasket[$basketIterator]["cost"] * $arOrderBasket[$basketIterator]["quantity"];
			$totalPrice += $arOrderBasketPrices[$basketIterator];
			
			$basketIterator++;
		}
		
		// пересчитываем вес товаров и заказа, если есть товары с 0 весом
		if ($set_wight)
		{
			$defWeight = COption::GetOptionString(self::$MODULE_ID, "weightD", 1000);// вес по умолчанию
			
			if ($totalWeight >= $defWeight)
				$good_width = 10;
			else
				$good_width = ceil(($defWeight - $totalWeight) / $zeroWeightCount);
			
			$totalWeight = 0;
			
			foreach ($arOrderBasket as $key => $val)
			{
				if ($val["weight"] == 0)
					$arOrderBasket[$key]["weight"] = $good_width;
				
				$totalWeight += $arOrderBasket[$key]["weight"] * $arOrderBasket[$key]["quantity"];
			}
		}
		
		$CityTo = apishipHelper::getNormalCity($params["CityTo"]);
		if (!$CityTo)
			return false;
		
		// берем данные, присланные из формы отправки заказа в админке
		$recipient = array(
			"postIndex" => $params["postIndex"],
			"countryCode" => "RU",
			"city" => $CityTo["NAME"],
			"region" => $CityTo["REGION"],
			"street" => $params["street"],
			"house" => $params["house"],
			"block" => $params["block"],
			"office" => $params["office"],
			"contactName" => $params["contactName"],
			"phone" => self::checkPhone($params["phone"]),
			"email" => $params["email"],
			"comment" => $params["comment"]
		);
		
		// определяем сумму наложенного платежа по принципу: заказ оплачен наложенный = 0, не оплачен наложенный = сумма заказа+доставка
		$deliveryCost = 0;
		if ($arOrder["PAYED"] == "Y")
		{
			$deliveryCost = 0;
			
			foreach ($arOrderBasket as $key => $arBasket)
				$arOrderBasket[$key]["cost"] = 0;
		}
        elseif ($arOrder["PAYED"] == "N")
			$deliveryCost = round($arOrder["PRICE_DELIVERY"], 2);
		
		// распределяем наложенный платеж на товары, так как его могли изменить на форме
		// оценочная должна остаться равной реальной стоимости товаров, ибо утеря товара при доставке должна возмещаться по ней
		$codCost = floatVal($params["payerPayment"]);
		$codCost -= $deliveryCost;
		$newTotalPrice = 0;
		if ($codCost != $totalPrice)
		{
			if ($totalPrice != 0)
				$koeff = $codCost / $totalPrice;
			else
				$koeff = 0;
			
			foreach ($arOrderBasketPrices as $key => $totalPrice)
			{
				$arOrderBasketPrices[$key] *= $koeff;
				
				if ($arOrderBasket[$key]["quantity"])
					$arOrderBasket[$key]["cost"] = round($arOrderBasketPrices[$key] / $arOrderBasket[$key]["quantity"], 2);
				
				$newTotalPrice += $arOrderBasket[$key]["cost"] * $arOrderBasket[$key]["quantity"];
			}
			
			$codCost = $newTotalPrice;
		}
		
		$codCost += $deliveryCost;
		$params["payerPayment"] = $codCost;
		
		$arOrder["LOCATION_TO"] = $params["CityTo"];
		
		if (preg_match("/apiship:pickup/", $arOrder["DELIVERY_ID"]))
			$profile = "pickup";
		if (preg_match("/apiship:courier/", $arOrder["DELIVERY_ID"]))
			$profile = "courier";
		
		$calc = CDeliveryapiship::Calculate($profile, false, $arOrder);
		
		if (preg_match("/-/", $calc["TRANSIT"]))
		{
			$time_shift = preg_replace("/ /", "", $calc["TRANSIT"]);
			$time_shift = preg_replace("/.*-/", "", $time_shift);
			$time_shift = IntVal($time_shift);
		}
		else
			$time_shift = IntVal($calc["TRANSIT"]);
		
		// оценочная стоимость
		$assessedCost = IntVal(COption::GetOptionString(self::$MODULE_ID, "assessedCost", 0));
		if (!$assessedCost)
			$assessedCost = round(floatVal($arOrder["PRICE"]) - floatval($arOrder["PRICE_DELIVERY"]), 2);
		
		// пишем номер заказа в переменную класса
		self::GetClientNumber($OrderID);
		
		// определяем тип доставки и тип приема товара
		// if (preg_match("/pickup/", $arOrder["DELIVERY_ID"]))
		if (preg_match("/pickup/", $params["deliveryType"]))
			$deliveryType = 2;
		else
			$deliveryType = 1;
		
		$pickupType = IntVal($params["pickupType"]);
		
		if (!$params["deliveryDate"]) {
			$params["deliveryDate"] = date("Y-m-d", (time() + $time_shift * 60 * 60 * 24));
		} else {
			$tmp_deliveryDate = explode('.', $params["deliveryDate"]);
			$params["deliveryDate"] = $tmp_deliveryDate[2].'-'.$tmp_deliveryDate[1].'-'.$tmp_deliveryDate[0];
		}
		
		if (!$params["deliveryTimeStart"]) {
			$params["deliveryTimeStart"] = '10:00';
		}
		
		if (!$params["deliveryTimeEnd"]) {
			$params["deliveryTimeEnd"] = '18:00';
		}
		
		$req_order = array(
			"clientNumber" => (string)self::$clientNumber[$OrderID],
			// "description"=> $arOrder["USER_DESCRIPTION"],
			"description" => $arOrder["USER_DESCRIPTION"],
			"height" => ceil($arProp["D_H"]),//
			"length" => ceil($arProp["D_L"]),//
			"width" => ceil($arProp["D_W"]),//
			"weight" => $totalWeight,//
			// "providerKey"=> $arProp["pvzInfo"]["providerKey"],//
			"providerKey" => $params["providerKey"],//
			"pickupType" => $pickupType,//
			"deliveryType" => $deliveryType,//
			// "tariffId"=> $arProp["pvzInfo"]["tariffId"],
			"tariffId" => $params["tariffId"],
			"pickupDate" => date("Y-m-d", (time() + $time_shift * 60 * 60 * 24)),
			"deliveryDate"=> $params["deliveryDate"],
			"deliveryTimeStart" => $params["deliveryTimeStart"],
			"deliveryTimeEnd" => $params["deliveryTimeEnd"]
		);
		
		if ($params["providerKey"] == "shoplogist")//== костыль
		{
			$req_order["deliveryDate"] = date("Y-m-d", (time() + $time_shift * 60 * 60 * 24));
			$req_order["deliveryTimeStart"] = '10:00';
			$req_order["deliveryTimeEnd"] = '18:00';
		}
		
		$defaultPickupPVZ = unserialize(COption::GetOptionString(self::$MODULE_ID, "defPickupPVZs"));
		if ($defaultPickupPVZ[$params["providerKey"]] != $params["pointInId"])
		{
			$defaultPickupPVZ[$params["providerKey"]] = $params["pointInId"];
			COption::SetOptionString(self::$MODULE_ID, "defPickupPVZs", serialize($defaultPickupPVZ));
		}
		
		if (!empty($params["apiship_pvzID"]))
			$req_order["pointOutId"] = $params["apiship_pvzID"];
		
		if (!empty($params["pointInId"]))
			$req_order["pointInId"] = $params["pointInId"];
		
		// проверяем отправлялся ли уже заказ
		$requests = sqlapishipOrders::select(array(), array("ORDER_ID" => $OrderID));
		$orderSended = false;
		while ($request = $requests->Fetch())
			$orderSended = $request["apiship_ID"];
		
		if ($orderSended)
			$where = "orders/" . $orderSended;
		else
			$where = "orders";
		
		$toRequest = array(
			"WHERE" => $where,
			"DATA" => array(
				"order" => $req_order,
				"cost" => array(
					// "insuranceCost"=> 0,//Сумма страховки (в рублях) ,
					"assessedCost" => $assessedCost, //Оценочная стоимость (в рублях) ,
					"deliveryCost" => $deliveryCost,// в рублях
					"codCost" => $codCost //Сумма наложенного платежа (в рублях)
				),
				"sender" => array(
					"countryCode" => "RU",
					"city" => $cityFrom["NAME"],
					"region" => $cityFrom["REGION"],
					"street" => $arSender["street"],
					"house" => $arSender["house"],
					"block" => $arSender["block"],
					"office" => $arSender["office"],
					"companyName" => $arSender["companyName"],
					"contactName" => $arSender["contactName"],
					"phone" => $arSender["phone"],
					"email" => $arSender["email"],
					"comment" => ""
				),
				"recipient" => $recipient,
				"items" => array_values($arOrderBasket)
			)
		);
		
		if ($orderSended)
			$toRequest["METHOD"] = "PUT";
		
		$arReturn = array();
		
//		die(print_r($toRequest, true));
		
		$req_res = apishipdriver::MakeRequest($toRequest);
		
		if ($req_res["code"] == 200)
		{
			if ($orderSended)
			{
				// если заказ успешно изменен, то заново отправляем его доставщику
				$toRequest = array(
					"WHERE" => "orders/" . $orderSended . "/resend",
					"DATA" => array(
						"orderId" => $orderSended
					)
				);
				
				$req_res = apishipdriver::MakeRequest($toRequest);
				
				if ($req_res["code"] == 200)
				{
					$arReturn = array("success" => true);
					
					return $req_res["result"];
				}
				else
				{
					$arReturn["is_error"] = true;
					$arReturn["error"] = self::$errors;
					$arReturn["error_msg"] = "";
					foreach (self::$errors as $arErr)
						foreach ($arErr["res"]["errors"] as $err)
							$arReturn["error_msg"] .= print_r(apishipHelper::zaDEjsonit($err), true);
					
					echo json_encode(apishipHelper::zajsonit($arReturn));
					
					return false;
				}
				
			}
			else
				return $req_res["result"];
		}
		else
		{
			$arReturn["is_error"] = true;
			$arReturn["error"] = self::$errors;
			$arReturn["error_msg"] = "";
			foreach (self::$errors as $arErr)
				foreach ($arErr["res"]["errors"] as $err)
					$arReturn["error_msg"] .= print_r(apishipHelper::zaDEjsonit($err), true);
			
			echo json_encode(apishipHelper::zajsonit($arReturn));
			
			return false;
		}
	}
	///////////////////////////////////////////////////////////////////////////
	///// Визуальное оформление(оформление заказа + таблица) //////////////////
	///////////////////////////////////////////////////////////////////////////
	
	function GetClientNumber($orderID)
	{
		if (!is_array($orderID))
			$orderID = array($orderID);
		
		// проверяем не получилил ли мы уже номер для некоторых заказов
		$requestOrderIDs = array();
		foreach ($orderID as $oID)
			if (empty(self::$clientNumber[$oID]))
				$requestOrderIDs[] = $oID;
		
		// запрашиваем номера для закаов, для которых номеров нет
		if (!empty($requestOrderIDs))
		{
			$dbOrders = CSaleOrder::GetList(
				array(),
				array("ID" => $requestOrderIDs)
			);
			
			while ($arOrder = $dbOrders->Fetch())
			{
				if (!empty($arOrder["ACCOUNT_NUMBER"]))
				{
					$ordNum = $arOrder["ACCOUNT_NUMBER"];
					$ordNum = preg_replace("/ /", "", $ordNum);
				}
				else
					$ordNum = $arOrder["ID"];
				
				self::$clientNumber[$arOrder["ID"]] = $ordNum;
			}
		}
	}
	
	function GetCurStatus($OrderID)
	{
		self::GetClientNumber($OrderID);
		
		// формируем запрос в apiship
		$toRequest = array(
			"WHERE" => "orders/status",
			"METHOD" => "GET",
			"FILTER_SPEC" => array("clientNumber" => self::$clientNumber[$OrderID])
		);
		
		$req_res = self::MakeRequest($toRequest);
		
		if ($req_res["code"] == 200)
			return $req_res["result"];
		else
			return false;
	}
	
	function onEpilog()
	{//Отображение формы
		if (
			(
			!(preg_match("/\/bitrix\/admin\/sale_order_detail.php/", $_SERVER['PHP_SELF']) ||
				preg_match("/\/bitrix\/admin\/sale_order_view.php/", $_SERVER['PHP_SELF'])
			)
			) ||
			!cmodule::includeModule('sale')
		)
			return false;
		include_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/js/" . apishipdriver::$MODULE_ID . "/orderDetail.php");
	}
	
	// помечает заказ на удаление
	
	function GetVarStatuses()
	{
		
		if ($dbStatuses = sqlapishipOrders::getStatuses())
		{
			$arStatuses = array();
			while ($status = $dbStatuses->Fetch())
				$arStatuses[] = $status["STATUS"];
			
			return $arStatuses;
		}
		else
			return false;
		
	}
	
	////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////// Печать документов //////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	
	// получение сопроводительных документов
	
	function tableHandler($params)
	{ // отображение таблицы о заявках
		$arSelect[0] = ($params['by']) ? $params['by'] : 'ID';
		$arSelect[1] = ($params['sort']) ? $params['sort'] : 'DESC';
		
		$arNavStartParams['iNumPage'] = ($params['page']) ? $params['page'] : 1;
		$arNavStartParams['nPageSize'] = ($params['pgCnt'] !== false) ? $params['pgCnt'] : 1;
		
		foreach ($params as $code => $val)
			if (strpos($code, 'F') === 0)
				$arFilter[substr($code, 1)] = $val;
		
		$requests = self::select($arSelect, $arFilter, $arNavStartParams);
		$strHtml = '';
		
		while ($request = $requests->Fetch())
		{
			$reqParams = unserialize($request['PARAMS']);
			$paramsSrt = '';
			
			foreach ($reqParams as $parCode => $parVal)
			{
				if (!preg_match("/isBeznal/", $parCode))
					$paramsSrt .= GetMessage("IPOLapiship_JS_SOD_$parCode") . ": ";
				
				$parVal = self::zaDEjsonit($parVal);
				switch ($parCode)
				{
					case "CityTo":
						break;
					case "GABS"    :
						$paramsSrt .= $parVal['BS_D_W'] . "x" . $parVal['BS_D_L'] . "x" . $parVal['BS_D_H'] . GetMessage("IPOLapiship_mm") . " " . $parVal['BS_W'] . " " . GetMessage('IPOLapiship_g');
						break;
					case "isBeznal" :
						break;
					default        :
						$paramsSrt .= $parVal . "<br>";
						break;
				}
			}
			
			$addClass = '';
			if ($request['STATUS'] == 'OK')
				$addClass = 'IPOLapiship_TblStOk';
			if ($request['STATUS'] == 'uploadingError')
				$addClass = 'IPOLapiship_TblStErr';
			if ($request['STATUS'] == 'TRANZT')
				$addClass = 'IPOLapiship_TblStTzt';
			if ($request['STATUS'] == 'DELETE')
				$addClass = 'IPOLapiship_TblStDel';
			if ($request['STATUS'] == 'STORE')
				$addClass = 'IPOLapiship_TblStStr';
			if ($request['STATUS'] == 'CORIER')
				$addClass = 'IPOLapiship_TblStCor';
			if ($request['STATUS'] == 'PVZ')
				$addClass = 'IPOLapiship_TblStPVZ';
			if ($request['STATUS'] == 'OTKAZ')
				$addClass = 'IPOLapiship_TblStOtk';
			if ($request['STATUS'] == 'DELIVD')
				$addClass = 'IPOLapiship_TblStDvd';
			
			$contMenu = '<td class="adm-list-table-cell adm-list-table-popup-block" onclick="BX.adminList.ShowMenu(this.firstChild,[{\'DEFAULT\':true,\'GLOBAL_ICON\':\'adm-menu-edit\',\'DEFAULT\':true,\'TEXT\':\'' . GetMessage('IPOLapiship_STT_TOORDR') . '\',\'ONCLICK\':\'BX.adminPanel.Redirect([],\\\'sale_order_detail.php?ID=' . $request['ORDER_ID'] . '&lang=ru\\\', event);\'}';
			
			$contMenu .= ',{\'GLOBAL_ICON\':\'adm-menu-delete\',\'TEXT\':\'' . GetMessage('IPOLapiship_JSC_SOD_DELETE') . '\',\'ONCLICK\':\'IPOLapiship_delSign(' . $request['apiship_ID'] . ', ' . $request['ORDER_ID'] . ')\'}';
			
			$contMenu .= '])"><div class="adm-list-table-popup"></div></td>';
			$strHtml .= '<tr class="adm-list-table-row ' . $addClass . '">
							' . $contMenu . '
							<td class="adm-list-table-cell"><div>' . $request['ID'] . '</div></td>
							<td class="adm-list-table-cell"><div>' . $request['MESS_ID'] . '</div></td>
							<td class="adm-list-table-cell"><div><a href="/bitrix/admin/sale_order_detail.php?ID=' . $request['ORDER_ID'] . '&lang=ru" target="_blank">' . $request['ORDER_ID'] . '</div></td>
							<td class="adm-list-table-cell"><div>' . $request['STATUS'] . '</div></td>
							<td class="adm-list-table-cell"><div>' . $request['apiship_ID'] . '</div></td>
							<td class="adm-list-table-cell"><div><a href="javascript:void(0)" onclick="IPOLapiship_shwPrms($(this).siblings(\'div\'))">' . GetMessage('IPOLapiship_STT_SHOW') . '</a><div style="height:0px; overflow:hidden">' . $paramsSrt . '</div></div></td>
							<td class="adm-list-table-cell"><div>' . $request['MESSAGE'] . '</div></td>
							<td class="adm-list-table-cell"><div>' . date("d.m.y H:i", $request['UPTIME']) . '</div></td>
						</tr>';
		}
		echo json_encode(
			self::zajsonit(
				array(
					'ttl' => $requests->NavRecordCount,
					'mP' => $requests->NavPageCount,
					'pC' => $requests->NavPageSize,
					'cP' => $requests->NavPageNomer,
					'sA' => $requests->NavShowAll,
					'html' => $strHtml
				)
			)
		);
	}
	
	// получение ярлыков(штрихкодов) документов
	
	public function delReqOD($param)
	{
		$OrderID = IntVal($param["oid"]);
		$apishipID = IntVal($param["api_id"]);
		
		// формируем запрос в apiship
		$toRequest = array(
			"WHERE" => "orders/",
			"METHOD" => "GET",
			"DEL_ORDER_ID" => $apishipID
		);
		
		$req_res = self::MakeRequest($toRequest);
		
		if ($req_res["code"] == 204)
		{
			if (!sqlapishipOrders::updateStatus(array(
				"ORDER_ID" => $OrderID,
				"STATUS" => "DELETE",
				"MESSAGE" => GetMessage("IPOLapiship_JSC_SOD_DELETE_STATUS")
			))
			)
			{
				echo GetMessage("IPOLapiship_JSC_SOD_DB_UPDATE_ERR");
				
				return false;
			}
			echo GetMessage("IPOLapiship_JSC_SOD_DELETE_SUCCESS");
			
			return true;
		}
		echo GetMessage("IPOLapiship_JSC_SOD_DELETE_ERROR");
		print_r($req_res);
		
		return false;
	}
	
	public function GetWayBills($OrderID)
	{
		if (!is_array($OrderID))
			$OrderID = array($OrderID);
		
		$toRequest = array(
			"WHERE" => "orders/waybills",
			"DATA" => array(
				"orderIds" => $OrderID
			)
		);
		
		$req_res = self::MakeRequest($toRequest);
		
		if ($req_res["code"] != 200)
			return false;
		else
			return $req_res["result"];
	}
	
	public function GetLabels($OrderID)
	{
		if (!is_array($OrderID))
			$OrderID = array($OrderID);
		
		$toRequest = array(
			"WHERE" => "orders/labels",
			"DATA" => array(
				"orderIds" => $OrderID,
				"format" => "pdf"
			)
		);
		
		$req_res = self::MakeRequest($toRequest);
		/*?><script type="text/javascript">window.console.log(<?=CUtil::PHPToJSObject(array("ids" => $toRequest, "res" => $req_res))?>);</script><?//[0] => 3931136 [1] => 3931137*/
		if ($req_res["code"] != 200)
			return false;
		else
			return $req_res["result"];
	}
	
	function getOrderInvoice($oId)
	{ // получаем квитанцию от сдека
		self::killOldInvoices(); //удаляем старые квитанции
		if (!$oId)
		{
			return array(
				'result' => 'error',
				'error' => 'No order id'
			);
		}
		if (!is_array($oId))
			$oId = array($oId);
		
		$requests = sqlapishipOrders::select(array(), array("ORDER_ID" => $oId));
		
		$apishipIDs = array();
		
		while ($request = $requests->Fetch())
			$apishipIDs[] = IntVal($request["apiship_ID"]);
		
		$errors = array();
		
		$Labels = self::GetLabels($apishipIDs);
		if (empty($Labels["url"]))
			$errors[] = $Labels["failedOrders"];
		// return array(
		// 'result' => 'errorLB',
		// 'error'  => $Labels["failedOrders"]
		// );
		
		
		// если нужны акты, то запрашиваем и их
		$PrintActs = (COption::GetOptionString(self::$MODULE_ID, 'prntActOrdr', 'O') == 'A') ? false : true;
		
		if ($PrintActs)
		{
			$WayBills = self::GetWayBills($apishipIDs);
			if (empty($WayBills["waybillItems"]))
				$errors[] = $WayBills["failedOrders"];
			// return array(
			// 'result' => 'errorWB',
			// 'error'  => $WayBills["failedOrders"]
			// );
		}
		
		// записываем полученные файлы на сервер
		if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/upload/" . self::$MODULE_ID))
			mkdir($_SERVER['DOCUMENT_ROOT'] . "/upload/" . self::$MODULE_ID);
		
		$arReturnFiles = array();
		
		if (!empty($Labels["url"]))
			$arReturnFiles[] = self::SaveRequestFile($Labels["url"]);
		
		if ($PrintActs)
		{
			foreach ($WayBills["waybillItems"] as $providerDocs)
				$arReturnFiles[] = self::SaveRequestFile($providerDocs["file"]);
		}
		
		if (empty($arReturnFiles))
			$arReturn["result"] = "error";
		else
		{
			$arReturn["result"] = "ok";
			$arReturn["file"] = $arReturnFiles;
		}
		
		if (!empty($errors))
			$arReturn["errors"] = $errors;
		
		return $arReturn;
	}
	
	// добавляем действия для печати актов
	
	function SaveRequestFile($url)
	{
		preg_match("/\?file=(.*)$/", $url, $matches);
		$filename = $matches[1];
		$filepath = $_SERVER['DOCUMENT_ROOT'] . "/upload/" . self::$MODULE_ID . "/";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		
		file_put_contents($filepath . $filename, $result);
		
		return $filename;
	}
	
	// нажатие на печать актов
	
	function killOldInvoices()
	{ // удаляет старые файлы с инвойсами
		$dirPath = $_SERVER['DOCUMENT_ROOT'] . "/upload/" . self::$MODULE_ID . "/";
		$dirContain = scandir($dirPath);
		foreach ($dirContain as $contain)
		{
			if (strpos($contain, '.pdf') !== false && (mktime() - (int)filemtime($dirPath . $contain)) > 1300)
				unlink($dirPath . $contain);
		}
	}
	
	///////////////////////////////////////////////////////////////////////////
	///// Агенты //////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////
	// вызов обновления статусов заказов
	
	function displayActPrint(&$list)
	{
		if (!empty($list->arActions))
			CJSCore::Init(array('ipolapiship_printOrderActs'));
		
		if ($GLOBALS['APPLICATION']->GetCurPage() == "/bitrix/admin/sale_order.php")
		{
			$list->arActions['ipolapiship_printOrderActs'] = GetMessage("IPOLapiship_SIGN_PRNTapiship");
			$list->arActions['ipolapiship_printCourierAct'] = GetMessage("IPOLapiship_SIGN_PRNTapishipCOURIER");
		}
	}
	
	// вызов обновления статусов из админки
	
	function OnBeforePrologHandler()
	{
		if (!array_key_exists('action', $_REQUEST) ||
			!array_key_exists('ID', $_REQUEST) ||
			($_REQUEST['action'] != 'ipolapiship_printOrderActs' &&
				$_REQUEST['action'] != 'ipolapiship_printCourierAct')
		)
			return;
		
		$orderIDs = $_REQUEST["ID"];
		
		if ($_REQUEST['action'] == 'ipolapiship_printOrderActs')
		{
			$res = self::getOrderInvoice($orderIDs);
			
			/*?><script type="text/javascript">window.console.log(<?=CUtil::PHPToJSObject(array("ids" => $orderIDs, "res" => $res))?>);</script><?*/
			
			if ($res["result"] == "ok")
			{
				
				foreach ($res["file"] as $file)
				{
					?>
                    <script type="text/javascript">
                        //console.log("<?=$file?>");
                        window.open('/upload/<?=self::$MODULE_ID?>/<?=$file?>', '_blank');

                    </script>
					<?
				}
			}
			else
			{
				?>
                <script type="text/javascript">
                    alert(<?=$res["error"]?>);
                </script>
				<?
			}
		}
        elseif ($_REQUEST['action'] == 'ipolapiship_printCourierAct')
		{
			$arParams = array(
				"orderIDs" => $orderIDs
			);
			
			$paramsStr = http_build_query($arParams);
			?>
            <script type="text/javascript">
                console.log('/bitrix/js/<?=self::$MODULE_ID?>/courierAct.php?<?=$paramsStr?>');
                window.open('/bitrix/js/<?=self::$MODULE_ID?>/courierAct.php?<?=$paramsStr?>', '_blank');

            </script>
			<?
		}
	}
	
	// непосредственное обновление статусов
	
	function agentOrderStates()
	{
		self::UpdateStatuses();
		
		return 'apishipdriver::agentOrderStates();';
	}// ставить ли трек номер
	
	function callOrderStates()
	{
		if (self::UpdateStatuses())
		{
			echo date("d.m.Y H:i:s", COption::GetOptionString(self::$MODULE_ID, 'statCync', 0));
			
			return true;
		}
		else
		{
			echo json_encode(self::$errors);
			
			return false;
		}
	}// признак, что получена настройка установки трек номера
	
	function UpdateStatuses()
	{
		// получаем статусы
		$req_res = self::GetStatuses();
		
		if ($req_res !== false)
		{
			$OrdIDs = array();
			$arFilter = array();
			// собираем статусы в массив
			
			foreach ($req_res as $res)
			{
				if (empty($OrdIDs[$res["orderInfo"]["orderId"]]))
				{
					$message = $res["status"]["name"];
					if (preg_match("/uploadingError/", $res["status"]["key"]))
						$message .= GetMessage("IPOLapiship_uploadingError_descr") . $res["status"]["description"];
					
					$OrdIDs[$res["orderInfo"]["orderId"]] = array(
						"STATUS" => $res["status"]["key"],
						"MESSAGE" => $message,
						"providerNumber" => $res["orderInfo"]["providerNumber"]
					);
					
					$arFilter["apiship_ID"][] = $res["orderInfo"]["orderId"];
				}
			}
			
			// получаем заказы из БД, которые созданы на сайте, надо, если пришли лишние заказы ненаши
			if (!empty($arFilter["apiship_ID"]))
			{
				$bitrixIDs = array();// собираем id заказов в битрикс
				$dbOrders = sqlapishipOrders::select(array(), $arFilter);
				while ($arOrder = $dbOrders->Fetch())
				{
					$bitrixIDs[$arOrder["ORDER_ID"]] = $OrdIDs[$arOrder["apiship_ID"]]["providerNumber"];
					// если есть отличия, то апдейтим статус и сообщение
					if (
						$OrdIDs[$arOrder["apiship_ID"]]["STATUS"] != $arOrder["STATUS"] ||
						$OrdIDs[$arOrder["apiship_ID"]]["MESSAGE"] != $arOrder["MESSAGE"]
					)
						if (!sqlapishipOrders::updateStatus(array(
							"apiship_ID" => $arOrder["apiship_ID"],
							"STATUS" => $OrdIDs[$arOrder["apiship_ID"]]["STATUS"],
							"MESSAGE" => $OrdIDs[$arOrder["apiship_ID"]]["MESSAGE"]
						))
						)
						{
							return false;
						}
						else
						{
							// обновляем статус заказа в битриксе в соответствии с настройками модуля
							if (!self::UpdateSelfOrderStatus($arOrder["ORDER_ID"], $OrdIDs[$arOrder["apiship_ID"]]["STATUS"]))
								return false;
						}
				}
				
				// далее надо записать идентификатор отправления
				self::$setTrackNum = false;
				if (!self::$getTrackNum)
				{
					self::$setTrackNum = ("Y" == COption::GetOptionString(self::$MODULE_ID, "setDeliveryId")) ? true : false;
					self::$getTrackNum = true;
				}
				
				if (CModule::IncludeModule("sale") && self::$setTrackNum)
				{
					$dbOrders = CSaleOrder::GetList(array(), array_keys($bitrixIDs));
					
					while ($arOrder = $dbOrders->Fetch())
						if (intVal($bitrixIDs[$arOrder["ID"]]) != intVal($arOrder["TRACKING_NUMBER"]))
							CSaleOrder::Update($arOrder["ID"], array("TRACKING_NUMBER" => $bitrixIDs[$arOrder["ID"]]));
				}
			}
			
			// передвигаем дату последней синхронизации
			COption::SetOptionString(self::$MODULE_ID, 'statCync', time());
			
			return true;
		}
		else
		{
			if ($req_res === false)
				return false;
			else
				return true;
		}
	}
	
	function UpdateSelfOrderStatus($OrderID, $apiship_status)
	{
		if (empty(self::$arStatusMap))
		{
			$otkaz = COption::GetOptionString(self::$MODULE_ID, "statusOTKAZ");
			self::$arStatusMap = array(
				"onPointIn" => COption::GetOptionString(self::$MODULE_ID, "statusSTORE"),
				"onWay" => COption::GetOptionString(self::$MODULE_ID, "statusTRANZT"),
				"delivering" => COption::GetOptionString(self::$MODULE_ID, "statusCORIER"),
				"readyForRecipient" => COption::GetOptionString(self::$MODULE_ID, "statusPVZ"),
				"delivered" => COption::GetOptionString(self::$MODULE_ID, "statusDELIVD"),
				"returned" => $otkaz,
				"returnedFromDelivery" => $otkaz,
				"returning" => $otkaz,
				"returnReady" => $otkaz,
				"uploaded" => COption::GetOptionString(self::$MODULE_ID, "uploaded"),
				"uploadingError" => COption::GetOptionString(self::$MODULE_ID, "uploadingError"),
				// "notApplicable" => COption::GetOptionString(self::$MODULE_ID, "uploadingError"),
				"unknown" => COption::GetOptionString(self::$MODULE_ID, "uploadingError")
			);
			
			self::$PayDeliveredOrder = COption::GetOptionString(self::$MODULE_ID, "markPayed");
		}
		
		$bx_status = self::$arStatusMap[$apiship_status];
		if (!empty($bx_status))
		{
			CModule::IncludeModule("sale");
			// обновляем статус
			// if (!CSaleOrder::Update($OrderID, array("STATUS_ID" => $bx_status)))
			if (!CSaleOrder::StatusOrder($OrderID, $bx_status))
			{
				self::$errors[] = array("function" => __FUNCTION__, "code" => "CantUpdate", "ORDER_ID" => $OrderID);
				
				return false;
			}
			// оплачиваем заказа
			if (preg_match("/delivered/", $apiship_status) && (self::$PayDeliveredOrder == "Y"))
			{
				$arOrder = CSaleOrder::GetList(
					array(),
					array("ID" => $OrderID)
				)->Fetch();
				if ($arOrder["PAYED"] != "Y")
					if (!CSaleOrder::PayOrder($OrderID, "Y"))
						self::$errors[] = array("function" => __FUNCTION__, "code" => "CantPayOrder", "ORDER_ID" => $OrderID);
			}
			
		}
		
		return true;
	}
	
	// получение статусов заказов с конкретной даты, зашитой в настройках модуля
	function GetStatuses()
	{
		if (!cmodule::includemodule('sale'))
			return false;
		
		$statCync = COption::GetOptionString(self::$MODULE_ID, 'statCync', 0);
		if (empty($statCync))
			$statCync = 0;
		$dateFirst = date("c", $statCync);
		
		$toRequest = array(
			"WHERE" => "orders/statuses/date/" . urlencode($dateFirst),
			"METHOD" => "GET"
		);
		
		$req_res = self::MakeRequest($toRequest);
		
		if ($req_res["code"] == 200)
			return $req_res["result"];
		else
			return false;
	}
	
	///////////////////////////////////////////////////////////////////////////
	///// Манипуляции со службами доставки ////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////
	// формируем массив доставщиков с доступами к лк
	function showProvidersList()
	{
		$providersList = CDeliveryapiship::GetProvidersList();
		$usingProviders = CDeliveryapiship::GetUsingProvidersList();
		
		$arReturn = array();
		if (!empty($usingProviders))
			foreach ($providersList as $key => $provider)
			{
				foreach ($usingProviders as $useKey => $useProvider)
				{
					if ($useProvider["providerKey"] == $provider)
					{
						if (!self::checkEmptyParams($useProvider["connectParams"]))
						{
							$arReturn[$provider] = array(
								"id" => $useProvider["id"],
								"connectParams" => $useProvider["connectParams"],
								"cashServiceRate" => $useProvider["cashServiceRate"],
								"insuranceRate" => $useProvider["insuranceRate"]
							);
							
							break;
						}
					}
					else
						$arReturn[$provider] = false;
				}
			}
		else
			foreach ($providersList as $key => $provider)
				$arReturn[$provider] = false;
		
		echo json_encode(apishipHelper::zajsonit($arReturn));
		
		return;
	}
	
	// получаем параметры конкретного доставщика
	function getProviderParams($params)
	{
		$provider_key = $params["provider_key"];
		
		$toRequest = array(
			"WHERE" => "lists/providers/" . $provider_key . "/params",
			"METHOD" => "GET"
		);
		
		$req_res = self::MakeRequest($toRequest);
		
		if ($req_res["code"] != 200)
			echo json_encode(apishipHelper::zajsonit(array("is_error" => true, "data" => $req_res["result"]["description"] . $req_res["result"]["errors"][0]["message"])));
		else
			echo json_encode(apishipHelper::zajsonit(array("is_error" => false, "data" => $req_res["result"])));
	}
	
	// провереям данные на пустоту
	function checkEmptyParams($data)
	{
		foreach ($data as $val)
			if (!empty($val))
				return false;
		
		return true;
	}
	
	function getCompanyID()
	{
		if (!self::$companyID)
		{
			$companyID = COption::GetOptionString(self::$MODULE_ID, 'companyID', false);
			
			if (!empty($companyID))
				self::$companyID = $companyID;
			else
			{
				$toRequest = array(
					"WHERE" => "frontend/users/me",
					"METHOD" => "GET"
				);
				
				$req_res = apishipdriver::MakeRequest($toRequest);
				
				if ($req_res["code"] != 200)
					return false;
				else
				{
					// return $req_res[]
					self::$companyID = $req_res["result"]["companyId"];
					COption::SetOptionString(self::$MODULE_ID, 'companyID', self::$companyID);
					
					return self::$companyID;
				}
			}
		}
		
		return self::$companyID;
	}
	
	// получение id компании
	
	function saveProviderParams($params)
	{
		unset($params["action"]);
		$params["companyId"] = self::getCompanyID();
		
		// foreach ($params as $key => $param)
		// if ($param == "false")
		// $params[$key] = false;
		
		$where = "frontend/providers/params";
		
		if ($params["method"] == "update")
		{
			$method = "PUT";
			$where .= "/" . $params["id"];
			unset($params["id"]);
		}
		else
			$method = "POST";
		unset($params["method"]);
		
		$toRequest = array(
			// "WHERE" => "frontend/providers/params",
			"WHERE" => $where,
			// "METHOD" => "POST",
			"METHOD" => $method,
			"DATA" => $params
		);
		
		$req_res = self::MakeRequest($toRequest);
		
		$arReturn = array();
		if ($req_res["code"] != 201 && $req_res["code"] != 200)
		{
			$arReturn["is_error"] = true;
			$arReturn["res"] = $req_res;
			$arReturn["par"] = $params;
			$arReturn["msg"] = $req_res["result"]["message"] . $req_res["result"]["errors"][0]["message"];
		}
		else
		{
			$arReturn["is_error"] = false;
			$arReturn["par"] = $params;
			$arReturn["msg"] = GetMessage("IPOLapiship_DELIVERY_UPDATE_SUCCESS");
		}
		
		echo json_encode(apishipHelper::zajsonit($arReturn));
		// echo json_encode(apishipHelper::zajsonit($params));
	}
	
	// сохраняем доступы к доставщику
	
	function zajsonit($handle)
	{
		if (class_exists('apishipHelper'))
			return apishipHelper::zajsonit($handle);
		else return false;
	}
	
	function zaDEjsonit($handle)
	{
		if (class_exists('apishipHelper'))
			return apishipHelper::zaDEjsonit($handle);
		else return false;
	}
	
	///////////////////////////////////////////////////////////////////////////
	///// Манипуляции с информацией о заявках [БД + удаление]//////////////////
	///////////////////////////////////////////////////////////////////////////
	//База данных
	
	function controlProps($mode = 1)
	{
		if (class_exists('apishipHelper'))
			return apishipHelper::controlProps($mode);
		else return false;
	} // добавление информации о заявке
	
	function printOrderInvoice($params)
	{ // печать заказа
		$resPrint = self::getOrderInvoice($params['oId']);
		echo json_encode(self::zajsonit($resPrint));
	} // удаление информации о заявке
	
	function GetOrderCurInfo($param)
	{
		$apishipOrderID = 0;
		if (!empty($param["bitrix_id"]))
		{
			$requests = sqlapishipOrders::select(array(), array("ORDER_ID" => $param["bitrix_id"]));
			
			while ($request = $requests->Fetch())
				$apishipOrderID = $request["apiship_ID"];
			
		}
        elseif (!empty($param["apiship_id"]))
			$apishipOrderID = $param["apiship_id"];
		
		if (empty($apishipOrderID))
			return array("code" => "Order Not Found");
		
		$toRequest = array(
			"WHERE" => "orders/" . $apishipOrderID . "/info",
			"METHOD" => "GET"
		);
		
		$req_res = self::MakeRequest($toRequest);
		
		if ($req_res["code"] == 200)
			return $req_res;
		else
			return self::$errors;
	} // проверка наличия заявки для заказа
	
	public static function MakeRequest($arSend, $token = false)
	{
		if (!$token)
			$token = COption::GetOptionString(self::$MODULE_ID, "token");
		
		$timeout = IntVal(COption::GetOptionString(self::$MODULE_ID, "dostTimeout", 10));
		
		// $curl_url = "http://api.dev.apiship.ru/v1/";
		$curl_url = "https://api.apiship.ru/v1/";
		
		if (!empty($arSend["FILTER"]))
			$arSend["FILTER"] = apishipHelper::zajsonit($arSend["FILTER"]);
		
		$ch = curl_init();
		
		$headers = array("Content-Type: application/json", "Accept: application/json");
		if (!empty($token))
			$headers[] = "Authorization: " . $token;
		
		$headers[] = "platform: bitrix";
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		
		if (preg_match("/GET/", $arSend["METHOD"]))
		{
			curl_setopt($ch, CURLOPT_GET, true);
			
			$get_params = "";
			if (!empty($arSend["FILTER"]))
				$get_params = "?filter=" . http_build_query($arSend["FILTER"]);
			
			// просто в некоторых запросах пишется через слово фильтр, а в некоторых просто параметром
			if (!empty($arSend["FILTER_SPEC"]))
			{
				if (!$get_params)
					$get_params .= "?";
				else
					$get_params .= "&";
				$get_params .= http_build_query($arSend["FILTER_SPEC"]);
			}
			
			if (!empty($arSend["DEL_ORDER_ID"]))
			{
				$get_params .= http_build_query($arSend["DEL_ORDER_ID"]);
			}
			
			if ($arSend["limit"])
				if (!empty($get_params))
					$get_params .= "&limit=" . $arSend["limit"];
				else
					$get_params = "?limit=" . $arSend["limit"];
			
			curl_setopt($ch, CURLOPT_URL, $curl_url . $arSend["WHERE"] . $get_params);
		}
        elseif (preg_match("/PUT/", $arSend["METHOD"]) || preg_match("/DELETE/", $arSend["METHOD"]))
		{
			if (!empty($arSend["DATA"]))
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(apishipHelper::zajsonit($arSend["DATA"])));
			curl_setopt($ch, CURLOPT_URL, $curl_url . $arSend["WHERE"]);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $arSend["METHOD"]);
			
		}
		else
		{
			curl_setopt($ch, CURLOPT_POST, true);
			if (!empty($arSend["DATA"]))
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(apishipHelper::zajsonit($arSend["DATA"])));
			curl_setopt($ch, CURLOPT_URL, $curl_url . $arSend["WHERE"]);
		}
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		
		$result = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		$arRet = array(
			'code' => $code,
			'result' => apishipHelper::zaDEjsonit(json_decode($result, true))
		);
		
		if ($code != 200)
			// if (1)
		{
			self::$errors[] = array(
				"url" => $curl_url,
				"code" => $code,
				"arSend" => $arSend,
				"res" => json_decode($result, true),
				"json_arSend" => json_encode($arSend["DATA"]),
				"json_ret" => $result
			);
		}
		
		return $arRet;
	}  // выбрать заявку по id заказа
	
	public static function deleteProviderParams($params)
	{
		$id = $params["id"];
		
		$toRequest = array(
			"WHERE" => "frontend/providers/params/" . $id,
			"METHOD" => "DELETE"
		);
		
		$req_res = self::MakeRequest($toRequest);
		
		$arReturn = array();
		if ($req_res["code"] != 204)
		{
			$arReturn["is_error"] = true;
			$arReturn["res"] = $req_res;
			$arReturn["par"] = $params;
			$arReturn["msg"] = GetMessage("IPOLapiship_DELIVERY_UPDATE_ERROR");
		}
		else
		{
			$arReturn["is_error"] = false;
			$arReturn["res"] = $req_res;
			$arReturn["msg"] = GetMessage("IPOLapiship_DELIVERY_UPDATE_SUCCESS");
		}
		
		echo json_encode(apishipHelper::zajsonit($arReturn));
	}  // обновление информации о заявке
	
	public static function Add($Data)
	{
		return sqlapishipOrders::Add($Data);
	} // выборка
	
	
	///////////////////////////////////////////////////////////////////////////
	///// Общие функции модуля ////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////
	
	public static function Delete($orderId)
	{
		return sqlapishipOrders::Delete($orderId);
	}
	
	public static function CheckRecord($orderId)
	{
		return sqlapishipOrders::CheckRecord($orderId);
	}
	
	public static function GetByOI($orderId)
	{
		return sqlapishipOrders::GetByOI($orderId);
	}
	
	public static function updateStatus($arParams)
	{
		return sqlapishipOrders::updateStatus($arParams);
	}
	
	
	///////////////////////////////////////////////////////////////////////////
	///// Не используется в модуле, но нужно для отладки //////////////////////
	///////////////////////////////////////////////////////////////////////////
	// получение данных о заказе
	// $param = array(
	// "apiship_id" => id заказа апишип
	// "bitrix_id" => id заказа в битриксе
	// )
	
	public static function select($arOrder = array("ID", "DESC"), $arFilter = array(), $arNavStartParams = array())
	{
		return sqlapishipOrders::select($arOrder, $arFilter, $arNavStartParams);
	}
}

?>