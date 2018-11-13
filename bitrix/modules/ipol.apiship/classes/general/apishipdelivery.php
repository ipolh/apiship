<?
cmodule::includeModule('sale');
IncludeModuleLangFile(__FILE__);
include_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/ipol.apiship/classes/general/apishiphelper.php');

/*
	IPOLapiship_CACHE_TIME - время кэша в секундах
	IPOLapiship_NOCACHE    - если задан - не использовать кэш
*/

// define(IPOLapiship_NOCACHE, 1);

class CDeliveryapiship
{
	static $MODULE_ID = 'ipol.apiship';
	
	static $profiles = false;
	static $bestsTariffs = false;
	// static $hasPVZ       = false;//грузим ли ПВЗ
	
	// static $price        = false;
	
	static $orderWeight = false;
	static $orderPrice = false;
	
	static $CityTo = false;
	static $CityToID = false;
	static $CityFrom = false;
	static $RegionTo = false;
	static $RegionFrom = false;
	
	static $goods = false;
	static $PasSystemID = false;
	
	static $cityPVZs = false;
	static $defaultVals = false;
	
	static $CompabilityPerform = false;
	static $arPVZTariff = array();
	static $arPVZAvailTariff = array();
	static $baseTariffs;
	static $selectedDelivery = "";
	static $round_price_val = false;
	
	function GetBitrixProfiles()
	{
		return array(
			"courier" => array(
				"TITLE" => GetMessage("IPOLapiship_DELIV_COURIER_TITLE"),
				"DESCRIPTION" => GetMessage("IPOLapiship_DELIV_COURIER_DESCR"),
				"RESTRICTIONS_WEIGHT" => array(0, 75000),
				"RESTRICTIONS_SUM" => array(0),
				"RESTRICTIONS_MAX_SIZE" => "100",
				"RESTRICTIONS_DIMENSIONS_SUM" => "150"
			),
			"pickup" => array(
				"TITLE" => GetMessage("IPOLapiship_DELIV_PICKUP_TITLE"),
				"DESCRIPTION" => GetMessage("IPOLapiship_DELIV_PICKUP_DESCR"),
				"RESTRICTIONS_WEIGHT" => array(0, 75000),
				"RESTRICTIONS_SUM" => array(0),
				"RESTRICTIONS_MAX_SIZE" => "100",
				"RESTRICTIONS_DIMENSIONS_SUM" => "150"
			)
		);
	}
	
	
	// получение профили услуг
	
	function Init()
	{
		// получаем поставщиков услуг и делаем из них профили
		$arProfiles = self::GetBitrixProfiles();
		
		return array(
			/* Basic description */
			"SID" => "apiship",
			"NAME" => GetMessage("IPOLapiship_DELIV_NAME"),
			"DESCRIPTION" => GetMessage('IPOLapiship_DELIV_DESCR'),
			"DESCRIPTION_INNER" => GetMessage('IPOLapiship_DELIV_DESCRINNER'),
			"BASE_CURRENCY" => COption::GetOptionString("sale", "default_currency", "RUB"),
			"HANDLER" => __FILE__,
			
			/* Handler methods */
			"DBGETSETTINGS" => array("CDeliveryapiship", "GetSettings"),
			"DBSETSETTINGS" => array("CDeliveryapiship", "SetSettings"),
			// "GETCONFIG" => array("CDeliveryapiship", "GetConfig"),
			
			"COMPABILITY" => array("CDeliveryapiship", "Compability"),
			"CALCULATOR" => array("CDeliveryapiship", "Calculate"),
			
			/* List of delivery profiles */
			"PROFILES" => $arProfiles
		);
	}
	
	// общие действия для Compability и Calculate
	
	function SetSettings($arSettings)
	{
		return serialize($arSettings);
	}
	
	// метод проверки совместимости в данном случае практически аналогичен рассчету стоимости
	
	function GetSettings($strSettings)
	{
		return unserialize($strSettings);
	}
	
	// пишет в свойства класса города
	
	function GetProvidersList()
	{
		$obCache = new CPHPCache();
		$token = COption::GetOptionString(self::$MODULE_ID, "token");
		
		$cachename = "IPOLapishipProviders|" . $token;
		
		if ($obCache->InitCache(defined("IPOLapiship_CACHE_TIME") ? IPOLapiship_CACHE_TIME : 86400, $cachename, "/IPOLapiship/") && !defined("IPOLapiship_NOCACHE"))
		{
			$arRet = $obCache->GetVars();
		}
		else
		{
			// формируем запрос в apiship
			$toRequest = array(
				"WHERE" => "lists/providers",
				"METHOD" => "GET",
				"FILTER" => array(),
				"limit" => 100
			);
			
			$req_res = apishipdriver::MakeRequest($toRequest);
			
			if ($req_res["code"] == 200)
			{
				$arRet = array();
				foreach ($req_res["result"]["rows"] as $provider)
				{
					if ($provider["key"] != "gett" && $provider["key"] != "dostavista")
						$arRet[] = $provider["key"];
				}
				
				$obCache->StartDataCache();
				$obCache->EndDataCache($arRet);
			}
			else
				return false;
		}
		
		return $arRet;
	}
	
	// пишет в свойства класса размеры и вес корзины
	
	function GetUsingProvidersList()
	{
		// формируем запрос в apiship
		$toRequest = array(
			"WHERE" => "frontend/providers/params",
			"METHOD" => "GET",
			"limit" => 100
		);
		$req_res = apishipdriver::MakeRequest($toRequest);
		
		if ($req_res["code"] == 200)
			return $req_res["result"]['rows'];
		else
			return false;
	}
	
	function GetProfilsList()
	{
		$obCache = new CPHPCache();
		$token = COption::GetOptionString(self::$MODULE_ID, "token");
		
		$cachename = "IPOLapishipProfiles|" . $token;
		
		if ($obCache->InitCache(defined("IPOLapiship_CACHE_TIME") ? IPOLapiship_CACHE_TIME : 86400, $cachename, "/IPOLapiship/") && !defined("IPOLapiship_NOCACHE"))
		{
			$arRet = $obCache->GetVars();
		}
		else
		{
			// формируем запрос в apiship
			$toRequest = array(
				"WHERE" => "lists/tariffs",
				"METHOD" => "GET",
				"FILTER" => array(),
				"limit" => 100
			);
			
			$req_res = apishipdriver::MakeRequest($toRequest);
			if ($req_res["code"] == 200)
			{
				$arRet = array();
				foreach ($req_res["result"]["rows"] as $provider)
					$arRet[$provider["id"]] = $provider;
				
				$obCache->StartDataCache();
				$obCache->EndDataCache($arRet);
			}
			else
				return false;
		}
		
		return $arRet;
	}
	
	function CommonAction(&$arOrder)
	{
		// устанавливаем цену
		self::$orderPrice = $arOrder["PRICE"];
		
		// считаем сумму наложенного платежа
		// $nal_plateg = 0;
		$nal_plateg = self::$orderPrice;
		$tmpPaySys = COption::GetOptionString(self::$MODULE_ID, "paySystems");
		
		// получаем город отправитель и получатель
		self::PrepareCitys($arOrder["LOCATION_TO"]);
		
		// устанавливаем параметры веса и габариты заказа
		self::PrepareDimensions($arOrder["ID"]);
		
		// берем поставщиков услуг
		$arProviders = array();
		
		// формируем список возможных поставщиков услуг
		$arProfiles = array();
		$arProfilesIDs = array();
		foreach ($arProfiles as $key => $profile)
			$arProfilesIDs[] = $key;
		
		$obCache = new CPHPCache();
		$cachename = "IPOLapiship|" . self::$CityFrom . "|" . self::$CityTo . "|" . implode('|', self::$goods);
		
		if ($obCache->InitCache(defined("IPOLapiship_CACHE_TIME") ? IPOLapiship_CACHE_TIME : 86400, $cachename, "/IPOLapiship/") && !defined("IPOLapiship_NOCACHE"))
		{
			$req_res = $obCache->GetVars();
		}
		else
		{
			$assessedCost = IntVal(COption::GetOptionString(self::$MODULE_ID, "assessedCost", 0));
			if (!$assessedCost)
				$assessedCost = self::$orderPrice;
			
			// формируем запрос в apiship
			$toRequest = array(
				"WHERE" => "calculator",
				"DATA" => array(
					"from" => array(
						"region" => self::$RegionFrom,
						"city" => self::$CityFrom,
						"countryCode" => "RU"
					),
					"to" => array(
						"region" => self::$RegionTo,
						"city" => self::$CityTo,
						"countryCode" => "RU"
					),
					"weight" => ceil(self::$goods["W"]),
					"width" => ceil(self::$goods["D_W"]),
					"height" => ceil(self::$goods["D_H"]),
					"length" => ceil(self::$goods["D_L"]),
					"assessedCost" => $assessedCost,// оценочная стоимость в руб
					"pickupDate" => date("Y-m-d", (time() + $time_shift * 60 * 60 * 24)),//== исправить
					"codCost" => $nal_plateg, //сумма наложенного платежа
					"providerKeys" => array_values(
						$arProviders
					),
					"tariffIds" => array_values(
						$arProfilesIDs
					)
				
				)
			);
			
			//			CheckTime("start");
			$req_res = apishipdriver::MakeRequest($toRequest);
			//			CheckTime("stop");
			
			if ($req_res["code"] == 200)
			{
				$obCache->StartDataCache();
				$obCache->EndDataCache($req_res);
			}
		}
		
		apishipHelper::errorLog(array(
			"apishipLogin" => COption::GetOptionString(apishipdriver::$MODULE_ID, 'logapiship', ''),
			"request" => $toRequest["DATA"],
			"result" => $req_res,
			"json_request" => json_encode(apishipHelper::zajsonit($toRequest["DATA"])),
			"json_result" => json_encode($req_res)
		));
		
		return $req_res;
	}
	
	function Compability($arOrder, $arConfig = array())
	{
		// Cобытие, позволяющее отключить модуль (или отд.профили) для отдельных городов или по собственным условиям
		// пример работы с событием (код ставится в init.php:
		/*	AddEventHandler('ipol.apiship', 'onCompabilityBefore', 'onCompabilityBeforeapiship');
			function onCompabilityBeforeapiship($order, $conf, $keys) {
				$profile = 'pickup';//профиль, который оставим: pickup - самовывоз, courier - курьер
				if($order['LOCATION_TO'] == <код местоположение нужного города>){
					if(in_array($profile,$keys)) // есть ли вообще профиль для этого города?
						return array($profile);  //возвращаем только его
					else
						return false;//полностью исключаем город
				}
				return true;
			}*/
		// обработка события
		
		$selectPVZButton = "<br><a href = 'javascript:void(0)' id = 'IPOLapiship_injectHere_pickup' onClick = 'IPOLapiship_pvz.selectPVZ();'>" . GetMessage("IPOLapiship_TEMPLATE_SELECTPVZ_PICKUP") . "</a>";
		
		self::$CompabilityPerform = true;// ставим флаг, что функция была вызвана и в переменных класса данные уже есть
		self::$profiles = array();
		
		$ifPrevent = true;
		foreach (GetModuleEvents(self::$MODULE_ID, "onCompabilityBefore", true) as $arEvent)
			$ifPrevent = ExecuteModuleEventEx($arEvent, Array(&$arOrder, &$arConfig, &$arKeys));
		
		if (is_array($ifPrevent))
		{
			$newKeys = array();
			foreach ($ifPrevent as $val)
			{
				if (in_array($val, $arKeys))
					$newKeys[] = $val;
			}
			$arKeys = $newKeys;
		}
		
		if (!$ifPrevent)
			return array();
		
		// получаем результат расчета от api
		$req_res = self::CommonAction($arOrder);
		
		if ($req_res["code"] != 200)
			return false;
		
		$RetProfiles = array();
		
		// наценки для курьера и самовывоза
		$courierPlus = floatVal(COption::GetOptionString(self::$MODULE_ID, "courierPlus", "0"));
		$pickupPlus = floatVal(COption::GetOptionString(self::$MODULE_ID, "pickupPlus", "0"));
		
		if ($courierPlus || $pickupPlus)
			foreach ($req_res["result"] as $profileName => $profile)
				foreach ($profile as $provider_profile_id => $provider_profile)
					foreach ($provider_profile["tariffs"] as $tariffNum => $tariff)
					{
						$deliveryPlus = 0;
						if (preg_match("/deliveryToDoor/", $profileName))
							$deliveryPlus = $courierPlus;
						elseif (preg_match("/deliveryToPoint/", $profileName))
							$deliveryPlus = $pickupPlus;
						
						$req_res["result"][$profileName][$provider_profile_id]["tariffs"][$tariffNum]["deliveryCost"] =
							floatval($req_res["result"][$profileName][$provider_profile_id]["tariffs"][$tariffNum]["deliveryCost"])
							+ $deliveryPlus;
					}
		
		// событие для редактирования списка тарифов
		foreach (GetModuleEvents(self::$MODULE_ID, "onTariffListComplete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, Array(&$req_res["result"], $arOrder, $arConfig));
		
		foreach ($req_res["result"] as $profileName => $profile)
		{
			if (preg_match("/deliveryToDoor/", $profileName))
			{
				// собираем профили доставки курьером
				$arCourierProfiles = array();
				foreach ($profile as $provider_profile)
				{
					// запоминаем тарифы и соединяем в один массив для определения лучшего
					self::$bestsTariffs["deliveryToDoor"][$provider_profile["providerKey"]]["providerKey"] = $provider_profile["providerKey"];
					
					foreach ($provider_profile["tariffs"] as $tariff)
					{
						$arCourierProfiles["tariffs"][] = array_merge(
							array("providerKey" => $provider_profile["providerKey"]),
							$tariff
						);
						
						self::$bestsTariffs["deliveryToDoor"][$provider_profile["providerKey"]]["tariffs"][$tariff["tariffId"]] = $tariff;
					}
				}
				
				$BestTariff = self::GetBestTariff($arCourierProfiles);
				
				if ($BestTariff)
				{
					self::$bestsTariffs["deliveryToDoorShown"][$BestTariff["providerKey"]] = $BestTariff;
					
					$tarif_name = "courier";
					
					self::$profiles[$tarif_name] = array(
						"VALUE" => $BestTariff['deliveryCost'],
						"TRANSIT" => ($BestTariff['daysMin'] == $BestTariff['daysMax']) ? ($BestTariff['daysMax'] + $addTerm) : ($BestTariff['daysMin'] + $addTerm) . '-' . ($BestTariff['daysMax'] + $addTerm),
						"TARIF" => $tarif_name,
					);
					
					$RetProfiles[] = $tarif_name;
				}
			}
			else
			{
				// если есть непустой тариф доставки до ПВЗ, выводим профиль доставки в ПВЗ
				$empty_pickup = 1;
				
				// сумму доставки и сроки считаем как минимум и максимум по всем профилям c ПВЗ
				$vals = array(
					"VALUE_MIN" => 10000,
					"VALUE_MAX" => 0,
					"TRANSIT_MIN" => 0,
					"TRANSIT_MAX" => 0,
				);
				
				// если в компоненте уже выбрали ПВЗ
				$ajax_tariffId = false;
				if (self::isAjax())
				{
					$ajax_tariffId = self::getRequestValue("apiship_tariffId_first");
					if ($tmpTariffID = self::getRequestValue("apiship_tariffId"))
						$ajax_tariffId = $tmpTariffID;
					
					// if (is_numeric($_REQUEST["apiship_tariffId_first"]) && ($_REQUEST["apiship_tariffId_first"] != 0))
					// $ajax_tariffId = $_REQUEST["apiship_tariffId_first"];
					
					// if (is_numeric($_REQUEST["order"]["apiship_tariffId_first"]) && ($_REQUEST["order"]["apiship_tariffId_first"] != 0))
					// $ajax_tariffId = $_REQUEST["order"]["apiship_tariffId_first"];
					
					// if (is_numeric($_REQUEST["apiship_tariffId"]) && ($_REQUEST["apiship_tariffId"] != 0))
					// $ajax_tariffId = $_REQUEST["apiship_tariffId"];
					
					// if (is_numeric($_REQUEST["order"]["apiship_tariffId"]) && ($_REQUEST["order"]["apiship_tariffId"] != 0))
					// $ajax_tariffId = $_REQUEST["order"]["apiship_tariffId"];
				}
				
				$arPickupTariffs = array();
				foreach ($profile as $key => $provider_profile)
				{
					
					self::fillPVZTariff($provider_profile);
					$arPickupTariffs[$provider_profile["providerKey"]] = $provider_profile;
					
					// если выбран пвз и тариф
					if ($ajax_tariffId)
					{
						if ($arPickupTariffs[$provider_profile["providerKey"]]["tariffs"][$ajax_tariffId])
						{
							$BestTariff = $arPickupTariffs[$provider_profile["providerKey"]]["tariffs"][$ajax_tariffId];
							
							$empty_pickup = 0;
							
							$vals = array(
								"VALUE_MIN" => $BestTariff["deliveryCost"],
								"VALUE_MAX" => $BestTariff["deliveryCost"],
								"TRANSIT_MIN" => $BestTariff["daysMin"],
								"TRANSIT_MAX" => $BestTariff["daysMax"]
							);
						}
					}
					else// если не выбирали пвз, надо выдать начальные данные
					{
						// берем для доставщика лучший тариф
						$BestTariff = self::GetBestTariff($provider_profile);
						
						if ($BestTariff)
							$empty_pickup = 0;
					}
				}
				
				// сохраняем, чтобы использовать вне класса
				self::$bestsTariffs["deliveryToPoint"] = $arPickupTariffs;
				
				if (!$empty_pickup)
				{
					// проверяем наличие пвз для отобранных тарифов
					$arPVZ = apishipdriver::GetPVZ(array("city" => self::$CityTo, "limit" => 5000));
					
					foreach ($arPVZ as $key => $PVZ)
					{
						if (
							!empty($arPickupTariffs[$PVZ["providerKey"]]["tariffs"]) &&
							(IntVal($PVZ["availableOperation"]) != 1) &&
							self::$arPVZTariff[$PVZ["providerKey"]][$PVZ["id"]]
						)
						{
							$arPVZ[$key]["tariff"] = $arPickupTariffs[$PVZ["providerKey"]]["tariffs"][self::$arPVZTariff[$PVZ["providerKey"]][$PVZ["id"]]];
						}
						else
							unset($arPVZ[$key]);
					}
					
					//формируем список пвз для компонента в нужном формате
					$PVZtoComponent = array();
					$showAddress = true;
					
					// массив реально доступных тарифов,
					$arAvailableTariffs = array();
					
					foreach ($arPVZ as $descr)
					{
						$arAvailableTariffs["tariffs"][$descr['tariff']['tariffId']] = $descr['tariff'];
						
						if ($showAddress)
							$pvzName = $descr['street'];
						else
							$pvzName = $descr['name'];
						
						$PVZtoComponent[$descr['id']] = array(
							'id' => $descr['id'],
							'code' => $descr['code'],
							'Name' => $pvzName,
							'Address' => "",
							'WorkTime' => $descr['timetable'],
							'Phone' => $descr['phone'],
							'cY' => $descr['lat'],
							'cX' => $descr['lng'],
							
							'providerKey' => $descr['providerKey'],
							
							'tariffId' => $descr['tariff']['tariffId'],
							'tariffName' => $descr['tariff']['tariffName'],
							'deliveryCost' => self::roundPrice($descr['tariff']['deliveryCost']),
							'daysMin' => $descr['tariff']['daysMin'],
							'daysMax' => $descr['tariff']['daysMax'],
							
							'street' => !empty($descr['street']) ? $descr['street'] : 0,
							'house' => !empty($descr['house']) ? $descr['house'] : 0,
							'block' => !empty($descr['block']) ? $descr['block'] : 0,
							'office' => !empty($descr['office']) ? $descr['office'] : 0,
							
							'availableOperation' => $descr['availableOperation']
						);
						
						// обрабатываем доп параметры, типа принадлежности ПВЗ сдека inpost
						if ($descr["extra"])
						{
							foreach ($descr["extra"] as $extraParam)
								$PVZtoComponent[$descr['id']][$extraParam["key"]] = strtolower($extraParam["value"]);
						}
						
						if ($descr['street'])
							$PVZtoComponent[$descr["id"]]["Address"] .=
								(!empty($descr['streetType']) ? $descr['streetType'] : "") .
								". " .
								strtoupper($descr['street']);
						
						if ($descr['house'])
							$PVZtoComponent[$descr["id"]]["Address"] .= GetMessage("IPOLapiship_pvz_house") . ($descr['house']);
						if ($descr['block'])
							$PVZtoComponent[$descr["id"]]["Address"] .= GetMessage("IPOLapiship_pvz_block") . ($descr['block']);
						if ($descr['office'])
							$PVZtoComponent[$descr["id"]]["Address"] .= GetMessage("IPOLapiship_pvz_office") . ($descr['office']);
					}
					
					// событие для редактирования данных пвз
					foreach (GetModuleEvents(self::$MODULE_ID, "onPVZListComplete", true) as $arEvent)
						ExecuteModuleEventEx($arEvent, Array(&$PVZtoComponent, $arOrder, $arConfig));
					
					//запомнили для компонента
					self::$cityPVZs = $PVZtoComponent;
					unset($PVZtoComponent);
					
					if (!empty(self::$cityPVZs))
					{
						// проходим по пвз, собираем реально доступные тарифы и показываем самый дешевый из них
						if (!$ajax_tariffId)
						{
							$BestTariff = self::GetBestTariff($arAvailableTariffs);
							
							$vals = array(
								"VALUE_MIN" => $BestTariff["deliveryCost"],
								"VALUE_MAX" => $BestTariff["deliveryCost"],
								"TRANSIT_MIN" => $BestTariff["daysMin"],
								"TRANSIT_MAX" => $BestTariff["daysMax"]
							);
						}
						
						if (($vals["TRANSIT_MIN"]) <= 0)
							$vals["TRANSIT_MIN"] = 1;//== исправить
						if (($vals["TRANSIT_MIN"] > $vals["TRANSIT_MAX"]) || (empty($vals["TRANSIT_MAX"])))
							$vals["TRANSIT_MAX"] = $vals["TRANSIT_MIN"];
						
						// сохраняем для компонента
						self::$defaultVals = $vals;
						
						$tarif_name = "pickup";
						
						self::$profiles[$tarif_name] = array(
							"VALUE" => $vals["VALUE_MIN"],
							"TRANSIT" => ($vals["TRANSIT_MIN"] == $vals["TRANSIT_MAX"]) ?
								($vals["TRANSIT_MIN"] + $addTerm) . $selectPVZButton :
								(($vals["TRANSIT_MIN"] + $addTerm) . " - " . ($vals["TRANSIT_MAX"] + $addTerm)) . $selectPVZButton,
							"TARIF" => $tarif_name,
						);
						
						$RetProfiles[] = "pickup";
					}
				}
			}
		}
		
		foreach (GetModuleEvents(self::$MODULE_ID, "onCompabilityPerform", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, Array(&$arOrder, &$arConfig, &$arKeys, &$RetProfiles));
		
		return $RetProfiles;
	}
	
	function PrepareCitys($cityToID)
	{
		$apishipcity = apishipHelper::getNormalCity(COption::GetOptionString(self::$MODULE_ID, 'departure'));
		self::$CityFrom = $apishipcity["NAME"];
		self::$RegionFrom = $apishipcity["REGION"];
		
		$apishipcity = apishipHelper::getNormalCityByLocationID($cityToID);
		self::$CityToID = $apishipcity["CITY_ID"];
		self::$CityTo = $apishipcity["NAME"];
		self::$RegionTo = $apishipcity["REGION"];
		$_SESSION['IPOLapiship_city'] = $cityToID;
	}
	
	//Суммируем размеры груза для вычисления объемного веса
	
	public function PrepareDimensions($orderId)
	{
		$ttlPrice = 0;
		
		if (isset($orderId) && $orderId > 0)
			$arFilter = array("ORDER_ID" => $orderId);
		else
			$arFilter = array("FUSER_ID" => CSaleBasket::GetBasketUserID(), "ORDER_ID" => "NULL");
		
		$arGoods = array();
		
		$dbBasketItems = CSaleBasket::GetList(
			array(),
			$arFilter,
			false,
			false,
			array("ID", "PRODUCT_ID", "PRICE", "QUANTITY", 'CAN_BUY', 'DELAY', "NAME", "DIMENSIONS", "WEIGHT", "PRICE", "SET_PARENT_ID", "LID")
		);
		
		while ($arItems = $dbBasketItems->Fetch())
			if ($arItems['CAN_BUY'] == 'Y' && $arItems['DELAY'] == 'N')
			{
				if (!$arItems['DIMENSIONS'])
				{
					//Что-то не так. Читаем из карточки
					$arBaseProduct = CCatalogProduct::GetList(
						array(),
						array('ID' => $arItems['PRODUCT_ID']),
						false,
						false,
						array('WEIGHT', 'WIDTH', 'LENGTH', 'HEIGHT', 'MEASURE')
					)->fetch();
					
					$arItems['DIMENSIONS'] = Array(
						'WIDTH' => $arBaseProduct['WIDTH'],
						'HEIGHT' => $arBaseProduct['HEIGHT'],
						'LENGTH' => $arBaseProduct['LENGTH'],
					);
				}
				else
					$arItems['DIMENSIONS'] = unserialize($arItems['DIMENSIONS']);
				
				$arGoods[$arItems["PRODUCT_ID"]] = $arItems;
			}
		
		$arGoods = self::handleBitrixComplects($arGoods);
		foreach ($arGoods as $arItems)
			$ttlPrice += $arItems['PRICE'] * $arItems['QUANTITY'];
		
		if (!self::$orderPrice)
			self::$orderPrice = $ttlPrice;
		
		if (empty($arGoods))
			$arGoods = self::GetFakeGoods();
		
		self::setGoods($arGoods);
	}
	// конец рассчета размеров корзины
	
	// получаем текущую платежную систему для установки величины наложенного платежа
	
	public function GetFakeGoods()
	{
		return array(array("ID" => 1));
	}
	
	function handleBitrixComplects($goods)
	{
		$arComplects = array();
		foreach ($goods as $good)
			if (
				array_key_exists('SET_PARENT_ID', $good) &&
				$good['SET_PARENT_ID'] &&
				$good['SET_PARENT_ID'] != $good['ID']
			)
				$arComplects[$good['SET_PARENT_ID']] = true;
		foreach ($goods as $key => $good)
			if (array_key_exists($good['ID'], $arComplects))
				unset($goods[$key]);
		
		return $goods;
	}
	
	public function setGoods($arOrderGoods)
	{
		self::$goods = false;
		$arGoods = array();
		$arDefSetups = array(
			'W' => COption::GetOptionString(self::$MODULE_ID, "weightD", 1000),
			'D_W' => COption::GetOptionString(self::$MODULE_ID, "widthD", 30) * 10,
			'D_H' => COption::GetOptionString(self::$MODULE_ID, "heightD", 20) * 10,
			'D_L' => COption::GetOptionString(self::$MODULE_ID, "lengthD", 40) * 10,
		);
		
		$isDef = true;
		$isNoW = false;
		$isNoG = false;
		$NoWCount = 0;//количество товаров без веса
		
		$arOrderGoods = self::handleBitrixComplects($arOrderGoods);
		
		foreach (GetModuleEvents(self::$MODULE_ID, "onBeforeDimensionsCount", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, Array(&$arOrderGoods));
		
		foreach ($arOrderGoods as $arGood)
		{
			$gWeight = (float)$arGood['WEIGHT'];
			if ($isDef && !$gWeight)
			{
				$NoWCount += (int)$arGood['QUANTITY'];
				$isNoW = true;
			}
			if ($isDef && !$isNoG && (!$arGood['DIMENSIONS']['WIDTH'] || !$arGood['DIMENSIONS']['HEIGHT'] || !$arGood['DIMENSIONS']['LENGTH']))
				$isNoG = true;
			$arGoods[] = array(
				'W' => ($gWeight) ? ($gWeight) : ((!$isDef) ? $arDefSetups['W'] : false),
				'D_W' => ($arGood['DIMENSIONS']['WIDTH']) ? ($arGood['DIMENSIONS']['WIDTH']) : ((!$isDef) ? $arDefSetups['D_W'] : false),
				'D_H' => ($arGood['DIMENSIONS']['HEIGHT']) ? ($arGood['DIMENSIONS']['HEIGHT']) : ((!$isDef) ? $arDefSetups['D_H'] : false),
				'D_L' => ($arGood['DIMENSIONS']['LENGTH']) ? ($arGood['DIMENSIONS']['LENGTH']) : ((!$isDef) ? $arDefSetups['D_L'] : false),
				'Q' => $arGood['QUANTITY'],
			);
		}
		
		$TW = 0;
		foreach ($arGoods as $good)
		{
			if (!$isNoG || ($good['D_W'] && $good['D_H'] && $good['D_L']))
				$yp[] = self::sumSizeOneGoods($good['D_W'], $good['D_H'], $good['D_L'], $good['Q']);
			$TW += $good['W'] * $good['Q'];
		}
		
		$result = self::sumSize($yp);
		
		if ($isNoG)
		{
			$vDef = $arDefSetups['D_W'] * $arDefSetups['D_H'] * $arDefSetups['D_L'];
			$vCur = $result['W'] * $result['L'] * $result['H'];
			if ($vCur < $vDef)
				$result = array(
					"W" => $arDefSetups['D_W'],
					"L" => $arDefSetups['D_L'],
					"H" => $arDefSetups['D_H']
				);
		}
		
		if ($isNoW)
			if ($TW >= $arDefSetups['W'])
				$TW += 10 * $NoWCount;// считаем вес товаров без веса как 10грамм
			else
				$TW = $arDefSetups['W'];
		// $TW = ($TW > $arDefSetups['W']) ? $TW : $arDefSetups['W'];
		
		// расчет товаров
		self::$goods = array(
			"D_W" => $result['W'] / 10,// итоговые размеры из мм в см
			"D_L" => $result['L'] / 10,
			"D_H" => $result['H'] / 10,
			"W" => $TW // в граммах
		);
		if (!self::$orderWeight)
			self::$orderWeight = $TW;
	}
	
	function sumSizeOneGoods($xi, $yi, $zi, $qty)
	{
		// отсортировать грузы по возрастанию
		$ar = array($xi, $yi, $zi);
		sort($ar);
		if ($qty <= 1)
			return (array('X' => $ar[0], 'Y' => $ar[1], 'Z' => $ar[2]));
		
		$x1 = 0;
		$y1 = 0;
		$z1 = 0;
		$l = 0;
		
		$max1 = floor(Sqrt($qty));
		for ($y = 1; $y <= $max1; $y++)
		{
			$i = ceil($qty / $y);
			$max2 = floor(Sqrt($i));
			for ($z = 1; $z <= $max2; $z++)
			{
				$x = ceil($i / $z);
				$l2 = $x * $ar[0] + $y * $ar[1] + $z * $ar[2];
				if (($l == 0) || ($l2 < $l))
				{
					$l = $l2;
					$x1 = $x;
					$y1 = $y;
					$z1 = $z;
				}
			}
		}
		
		return (array('X' => $x1 * $ar[0], 'Y' => $y1 * $ar[1], 'Z' => $z1 * $ar[2]));
	}// соответсвие идПВЗ => идЛучшегоТарифа
	
	function sumSize($a)
	{
		$n = count($a);
		if (!($n > 0))
			return (array('length' => '0', 'width' => '0', 'height' => '0'));
		for ($i3 = 1; $i3 < $n; $i3++)
		{
			// отсортировать размеры по убыванию
			for ($i2 = $i3 - 1; $i2 < $n; $i2++)
			{
				for ($i = 0; $i <= 1; $i++)
				{
					if ($a[$i2]['X'] < $a[$i2]['Y'])
					{
						$a1 = $a[$i2]['X'];
						$a[$i2]['X'] = $a[$i2]['Y'];
						$a[$i2]['Y'] = $a1;
					};
					if (($i == 0) && ($a[$i2]['Y'] < $a[$i2]['Z']))
					{
						$a1 = $a[$i2]['Y'];
						$a[$i2]['Y'] = $a[$i2]['Z'];
						$a[$i2]['Z'] = $a1;
					}
				}
				$a[$i2]['Sum'] = $a[$i2]['X'] + $a[$i2]['Y'] + $a[$i2]['Z']; // сумма сторон
			}
			// отсортировать грузы по возрастанию
			for ($i2 = $i3; $i2 < $n; $i2++)
				for ($i = $i3; $i < $n; $i++)
					if ($a[$i - 1]['Sum'] > $a[$i]['Sum'])
					{
						$a2 = $a[$i];
						$a[$i] = $a[$i - 1];
						$a[$i - 1] = $a2;
					}
			// расчитать сумму габаритов двух самых маленьких грузов
			if ($a[$i3 - 1]['X'] > $a[$i3]['X'])
				$a[$i3]['X'] = $a[$i3 - 1]['X'];
			if ($a[$i3 - 1]['Y'] > $a[$i3]['Y'])
				$a[$i3]['Y'] = $a[$i3 - 1]['Y'];
			$a[$i3]['Z'] = $a[$i3]['Z'] + $a[$i3 - 1]['Z'];
			$a[$i3]['Sum'] = $a[$i3]['X'] + $a[$i3]['Y'] + $a[$i3]['Z']; // сумма сторон
		}
		
		return (array(
			'L' => Round($a[$n - 1]['X'], 2),
			'W' => Round($a[$n - 1]['Y'], 2),
			'H' => Round($a[$n - 1]['Z'], 2))
		);
	}// идПВЗ => array(идДоступныхТарифов), надо в отправке заявок
	
	function checkNalD2P(&$arResult, &$arUserResult, $arParams)
	{
		if (
			$arParams['DELIVERY_TO_PAYSYSTEM'] == 'd2p' &&
			preg_match("/apiship:/", $arUserResult['DELIVERY_ID']) &&
			COption::GetOptionString(self::$MODULE_ID, "hideNal", "Y") == 'Y'
		)
		{
			$arBesnalPaySys = unserialize(COption::GetOptionString(self::$MODULE_ID, 'paySystems', 'a:{}'));
		}
		
		return true;
	}
	
	function checkNalP2D(&$arResult, &$arUserResult, $arParams)
	{
		return true;
	}
	
	function Calculate($profile = "", $arConfig = array(), $arOrder = array(), $STEP = null, $TEMP = false)//расчет стоимости
	{
		// получаем результат расчета от api
		$arReturn = array();
		
		if (!self::$CompabilityPerform)
			self::Compability($arOrder, $arConfig);
		
		if (!empty(self::$profiles[$profile]))
		{
			$arReturn = array_merge(array("RESULT" => "OK"), self::$profiles[$profile]);
		}
		
		if (empty($arReturn))
			$arReturn = array(
				"RESULT" => "ERROR",
				"TEXT" => GetMessage("IPOLapiship_CALCULATE_ERROR"),
			);
		
		foreach (GetModuleEvents(self::$MODULE_ID, "onCalculate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, Array(&$arReturn, $profile, $arConfig, $arOrder));
		
		self::$profiles[$profile] = $arReturn;
		
		return $arReturn;
	}
	
	/* END рассчет товаров */
	
	function fillPVZTariff(&$arInputTariffs)
	{
		$arTariffs = $arInputTariffs;
		
		while (!empty($arTariffs["tariffs"]))
		{
			$BestTariff = self::GetBestTariff($arTariffs);
			
			foreach ($BestTariff["pointIds"] as $point)
			{
				if (empty(self::$arPVZTariff[$arTariffs["providerKey"]][$point]))
					self::$arPVZTariff[$arTariffs["providerKey"]][$point] = $BestTariff["tariffId"];
				
				self::$arPVZAvailTariff[$arTariffs["providerKey"]][$point][] = $BestTariff["tariffId"];
			}
			
			foreach ($arTariffs["tariffs"] as $key => $tariff)
				if ($tariff["tariffId"] == $BestTariff["tariffId"])
				{
					unset($arTariffs["tariffs"][$key]);
					$arInputTariffs["tariffs"][$BestTariff["tariffId"]] = $BestTariff;
					unset($arInputTariffs["tariffs"][$key]);
				}
		}
		
		return true;
	}
	
	
	
	// END проверки на возможность оплаты
	
	// Вызывается в компоненте bitrix:sale.order.ajax после формирования списка доступных служб доставки, может быть использовано для модификации данных.
	
	function GetBestTariff($arTariffs)
	{
		if (empty($arTariffs["tariffs"]))
			return false;
		
		// смотрим на минимальную сумму в тарифах доставщика и попутно определяем самый дешевый тариф и если их несколько с одной ценой, то берем самый быстрый, далее логика до и после 500руб.
		$min_sum = PHP_INT_MAX;
		$dayMax = PHP_INT_MAX;
		$chipestKey = -1;
		foreach ($arTariffs["tariffs"] as $key => $tariff)
		{
			if ($min_sum > $tariff["deliveryCost"])
			{
				$min_sum = $tariff["deliveryCost"];
				$dayMax = $tariff["daysMax"];
				$chipestKey = $key;
				
			}
			elseif ($min_sum == $tariff["deliveryCost"])
			{
				if ($tariff["daysMax"] < $dayMax)
				{
					$min_sum = $tariff["deliveryCost"];
					$dayMax = $tariff["daysMax"];
					$chipestKey = $key;
				}
			}
			
			$arTariffs["tariffs"][$key]["deliveryCost"] = self::roundPrice($arTariffs["tariffs"][$key]["deliveryCost"]);
		}
		
		// вытаскиваем бызовые тарифы из файла
		if (empty(self::$baseTariffs))
		{
			require_once($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/js/" . self::$MODULE_ID . "/base_tariffs.php");
			self::$baseTariffs = $BaseTariffs;
		}
		
		if ($min_sum < 500)
		{
			// берем базовый тариф доставщика
			foreach ($arTariffs["tariffs"] as $tariff)
				if (self::$baseTariffs[$arTariffs["providerKey"]] == $tariff["tariffId"])
					return $tariff;
		}
		
		// если базового тарифа в выдаче нет или мин. сумма более 500р, то берем самый дешевый
		return $arTariffs["tariffs"][$chipestKey];
	}
	
	function loadComponent()
	{ // подключает компонент
		if (self::isActive() && !self::isAjax())
			$GLOBALS['APPLICATION']->IncludeComponent(
				"ipol:ipol.apishipPickup",
				"order",
				array("CITY_ID" => self::$CityToID),
				false
			);
	}
	
	function pickupLoader($arResult, $arUserResult)
	{
		if (!self::isActive())
			return;
		
		self::$selectedDelivery = $arUserResult['DELIVERY_ID'];
	}
	
	public function getRequestedValues()
	{
		$arRequestValues = array(
			"apiship_pvzID" => array(
				"TYPE" => "Number"
			),
			"apiship_tariffId" => array(
				"TYPE" => "Number"
			),
			"apiship_providerKey" => array(
				"TYPE" => "String"
			)
		);
		
		foreach ($arRequestValues as $code => $arValParams)
		{
			$arRequestValues[$code]["VALUE"] = self::getRequestValue($code . "_first", $arValParams["TYPE"]);
			if ($tmpVal = self::getRequestValue($code, $arValParams["TYPE"]))
				$arRequestValues[$code]["VALUE"] = $tmpVal;
		}
		
		return $arRequestValues;
	}
	
	function isAjax()
	{
		if (
			$_REQUEST['is_ajax_post'] == 'Y' ||
			$_REQUEST["AJAX_CALL"] == 'Y' ||
			$_REQUEST["ORDER_AJAX"] ||
			$_REQUEST["via_ajax"] == "Y" ||
			$_REQUEST["action"] == "refreshOrderAjax" ||
			$_REQUEST["action"] == "saveOrderAjax"
		)
			return true;
		else
			return false;
	}
	
	function onBufferContent(&$content)
	{
		if (self::isActive() && self::$CityTo && self::isAjax())
		{
			$arRequestValues = self::getRequestedValues();
			
			$moduleValues = array(
				"apiship_city" => array(
					"TYPE" => "value",
					"VALUE" => self::$CityTo
				),
				"apiship_city_id" => array(
					"TYPE" => "value",
					"VALUE" => self::$CityToID
				),
				"apiship_dostav" => array(
					"TYPE" => "value",
					"VALUE" => self::$selectedDelivery
				),
				"ipolapiship_pvz_list_tag_ajax" => array(
					"TYPE" => "json",
					"VALUE" => json_encode(apishipHelper::zajsonit(self::$cityPVZs))
				),
				"ipolapiship_default_vals_tag_ajax" => array(
					"TYPE" => "json",
					"VALUE" => json_encode(apishipHelper::zajsonit(self::$defaultVals))
				)
			);
			
			$noJson = self::no_json($content);
			
			if ($noJson)
			{
				foreach ($arRequestValues as $code => $arValParams)
					$content .= '<input type="hidden" id="' . $code . '"   name="' . $code . '"   value=\'' . $arValParams["VALUE"] . '\' />';
				
				foreach ($moduleValues as $code => $value)
					if ($value["TYPE"] == "value")
						$content .= '<input type="hidden" id="' . $code . '"   name="' . $code . '"   value=\'' . $value["VALUE"] . '\' />';
					elseif ($value["TYPE"] == "json")
						$content .= '<div id = "' . $code . '" style = "display:none;">' . $value["VALUE"] . '</div>';
			}
			elseif (
				($_REQUEST['action'] == 'refreshOrderAjax'
				|| $_REQUEST['soa-action'] == 'refreshOrderAjax')
				&& !$noJson
			)
			{
				$content = substr($content, 0, strlen($content) - 1);
				$content .= ',"apiship":{';
				
				$first = true;
				foreach ($arRequestValues as $code => $arValParams)
				{
					if ($first)
						$first = false;
					else
						$content .= ',';
					
					$content .= '"' . $code . '":"' . $arValParams["VALUE"] . '"';
				}
				
				foreach ($moduleValues as $code => $value)
					if ($value["TYPE"] == "value")
						$content .= ',"' . $code . '":"' . apishipHelper::zajsonit($value["VALUE"]) . '"';
					elseif ($value["TYPE"] == "json")
						$content .= ',"' . $code . '":' . $value["VALUE"];
				
				$content .= '}}';
			}
		}
	}
	
	function no_json($wat)
	{
		return is_null(json_decode(self::zajsonit($wat), true));
	}
	
	// Событие вызывается в самом конце перед отправкой HTML в браузер. Нужно: 1) Чтобы при Ajax запросах передать в JS обновленные данные. 2) Чтобы заменить Макросы в названиях профилей, дописав туда название сл.доставки и описания (для случая когда у нас универсальные профили - Быстрейший, дешевуйший и т.д.
	
	function toUpper($str)
	{
		if (class_exists('apishipHelper'))
			return apishipHelper::toUpper($str);
		else return false;
	}
	
	function roundPrice($price)// округление цены
	{
		if (self::$round_price_val === false)
			self::$round_price_val = IntVal(COption::GetOptionString(self::$MODULE_ID, "roundDP", 0));
		
		if (self::$round_price_val === 0)
			return $price;
		else
			return ceil($price / self::$round_price_val) * self::$round_price_val;
	}
	
	/*()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()
													Общие функции модуля
		== errorLog ==  == zajsonit ==  == zaDEjsonit ==  == findArDif == == toUpper == == getTarifList ==
	()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()()*/
	
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
	
	function isActive()
	{
		if (class_exists('apishipHelper'))
			return apishipHelper::isActive();
		else
			return false;
	}
	
	private function getRequestValue($code, $valueType = "Number")
	{
		$value = false;
		
		$requestValues = array(
			$_REQUEST[$code],
			$_REQUEST["order"][$code]
		);
		
		foreach ($requestValues as $reqVal)
			if (method_exists(__CLASS__, "checkRequestValue" . $valueType))
				if (call_user_func("self::checkRequestValue" . $valueType, $reqVal))
					$value = $reqVal;
		
		return $value;
	}
	
	private function checkRequestValueNumber($value)
	{
		if (is_numeric($value) && ($value != 0))
			return $value;
		else
			return false;
	}
	
	private function checkRequestValueString($value)
	{
		if (is_string($value) && $value != "false")
			return $value;
		else
			return false;
	}
}

?>