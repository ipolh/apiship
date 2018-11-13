<?
IncludeModuleLangFile(__FILE__);

class apishipHelper{
	static $MODULE_ID = "ipol.apiship";
	// чистка кеша
	public static function clearCache($param)
	{
		$obCache = new CPHPCache();
		$obCache->CleanDir('/IPOLapiship/');
		echo "Y";
	}
	
	// обработка авторизации
	public static function auth($params){
		if(!$params['login'] || !$params['password'])
			die('No auth data');
		if(!class_exists('CDeliveryapiship'))
			die('No main class founded');
		
		$arSend = array(
			"WHERE" => "login",
			"DATA" => array
			(
				"login" => $params["login"],
				"password" => $params["password"]
			)
		);
		
		$res = apishipdriver::MakeRequest($arSend);
		
		if ($res["code"] != 200)
		{
			json_encode();
			$retStr=GetMessage('IPOLapiship_AUTH_NO');
			// foreach($res["result"]["errors"][0] as $erCode => $erText)
				// $retStr.=$erText." (".$erCode."). ";
			$retStr .= $res["result"]["errors"];
			echo $retStr."/n";
			echo self::pre(self::zaDEjsonit($res));
		}
		else
		{
			COption::SetOptionString(apishipdriver::$MODULE_ID,'logapiship',$params['login']);
			COption::SetOptionString(apishipdriver::$MODULE_ID,'pasapiship',$params['password']);
			COption::SetOptionString(apishipdriver::$MODULE_ID,'logged',true);
			COption::SetOptionString(apishipdriver::$MODULE_ID,'token',$res["result"]["accessToken"]);
			
			RegisterModuleDependences("main", "OnEpilog", apishipdriver::$MODULE_ID, "apishipdriver", "onEpilog");
			RegisterModuleDependences("main", "OnEndBufferContent", apishipdriver::$MODULE_ID, "CDeliveryapiship", "onBufferContent");
			RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepDelivery", apishipdriver::$MODULE_ID, "CDeliveryapiship", "pickupLoader",900);
			RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepProcess", apishipdriver::$MODULE_ID, "CDeliveryapiship", "loadComponent",900);
			// RegisterModuleDependences("main", "OnEpilog", imldriver::$MODULE_ID, "CDeliveryIML", "onOEPageLoad"); // editing order
			RegisterModuleDependences("main", "OnAdminListDisplay", apishipdriver::$MODULE_ID, "apishipdriver", "displayActPrint");
			RegisterModuleDependences("main", "OnBeforeProlog", apishipdriver::$MODULE_ID, "apishipdriver", "OnBeforePrologHandler");
			RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepComplete", apishipdriver::$MODULE_ID, "apishipdriver", "orderCreate"); // создание заказа
			RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepPaySystem", apishipdriver::$MODULE_ID, "CDeliveryapiship", "checkNalD2P"); // проверка платежных систем
			RegisterModuleDependences("sale", "OnSaleComponentOrderOneStepDelivery", apishipdriver::$MODULE_ID, "CDeliveryapiship", "checkNalP2D"); // проверка платежных систем

			// CAgent::AddAgent("apishipdriver::agentUpdateList();", apishipdriver::$MODULE_ID);//обновление листов
			CAgent::AddAgent("apishipdriver::agentOrderStates();",apishipdriver::$MODULE_ID,"N",1800);//обновление статусов заказов
			
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".apishipdriver::$MODULE_ID."/install/delivery/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/include/sale_delivery/", true, true);
			
			echo "G".GetMessage('IPOLapiship_AUTH_YES');
		}
	}
	
	public static function makeIpolServerRequest($arSend)
	{
		$url = "http://ipolh.com/webService/apiship_bitrix/register.php";
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS,  http_build_query(apishipHelper::zajsonit($arSend)));
		curl_setopt($ch, CURLOPT_URL, $url);
		
		$result = json_decode(curl_exec($ch));
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		return array("result" => $result, "code" => $code);
	}
	
	public static function register($data)
	{
		$arSend = array(
			"action" => "userCreate",
			"data" => array(
				"login" => $data["login"],
				"password" => $data["password"]
			)
		);
		
		$req_res = self::makeIpolServerRequest($arSend);
		
		echo json_encode(array("res" => $req_res["result"], "code" => $req_res["code"]));
		return;
	}
	
	// получаем доставщиков, у которых pointInId надо выставить
	public static function getPickupProviders()
	{
		$obCache = new CPHPCache();
		$cachename = "IPOLapiship|pickipProviders";
		
		if($obCache->InitCache(defined("IPOLapiship_CACHE_TIME")?IPOLapiship_CACHE_TIME:86400,$cachename,"/IPOLapiship/") && !defined("IPOLapiship_NOCACHE"))
		{
			$req_res = $obCache->GetVars();
		}
		else
		{
			$arSend = array(
				"action" => "getPickupProviders"
			);
		
			$req_res = self::makeIpolServerRequest($arSend);
			
			if ($req_res["code"] == 200)
			{
				$obCache->StartDataCache();
				$obCache->EndDataCache($req_res);
			}
			else
			{
				$req_res["result"] = array("boxberry");
			}
		}
		
		return $req_res["result"];
	}
	
	public static function logoff(){
		COption::SetOptionString(apishipdriver::$MODULE_ID,'logapiship','');
		COption::SetOptionString(apishipdriver::$MODULE_ID,'pasapiship','');
		COption::SetOptionString(apishipdriver::$MODULE_ID,'logged',false);
		COption::SetOptionString(apishipdriver::$MODULE_ID,'token',"");
		COption::SetOptionString(apishipdriver::$MODULE_ID,'companyID',"");
		CAgent::RemoveModuleAgents('ipol.apiship');
		UnRegisterModuleDependences("main", "OnEpilog", apishipdriver::$MODULE_ID, "apishipdriver", "onEpilog");
		UnRegisterModuleDependences("main", "OnEndBufferContent", apishipdriver::$MODULE_ID, "CDeliveryapiship", "onBufferContent");
		UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepDelivery", apishipdriver::$MODULE_ID, "CDeliveryapiship", "pickupLoader",900);
		UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepProcess", apishipdriver::$MODULE_ID, "CDeliveryapiship", "loadComponent",900);
		UnRegisterModuleDependences("main", "OnAdminListDisplay", apishipdriver::$MODULE_ID, "apishipdriver", "displayActPrint");
		UnRegisterModuleDependences("main", "OnBeforeProlog", apishipdriver::$MODULE_ID, "apishipdriver", "OnBeforePrologHandler");
		UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepComplete", apishipdriver::$MODULE_ID, "apishipdriver", "orderCreate");
		UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepPaySystem", apishipdriver::$MODULE_ID, "CDeliveryapiship", "checkNalD2P");
		UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepDelivery", apishipdriver::$MODULE_ID, "CDeliveryapiship", "checkNalP2D");
		DeleteDirFilesEx("/bitrix/php_interface/include/sale_delivery/delivery_apiship.php");
		
		self::clearCache();
	}
	
	// Свойство заказа, куда пишутся тарифы
	function controlProps($mode){//1-add/update, 2-delete "TOKEN-prop"
		if(!CModule::IncludeModule("sale"))
			return false;
		
		$arPropertyCodes = array("IPOLAPISHIP_PVZ_DELIVERER_ID", "IPOLAPISHIP_PROVIDER");
		
		$tmpGet=CSaleOrderProps::GetList(
			array("SORT" => "ASC"),
			array("CODE" => $arPropertyCodes)
		);
		
		$existedProps=array();
		while($tmpElement=$tmpGet->Fetch())
			$existedProps[$tmpElement["CODE"]][$tmpElement['PERSON_TYPE_ID']] = $tmpElement["ID"];
		
		if($mode=='1')
		{
			$tmpGet = CSalePersonType::GetList(
				Array("SORT" => "ASC"), 
				Array()
			);
			
			$allPayers=array();
			while($tmpElement=$tmpGet->Fetch())
				$allPayers[]=$tmpElement['ID'];
			
			$return = true;
			// тут проверяем созданы ли все свойства
			foreach ($arPropertyCodes as $needCode)
				foreach($allPayers as $payer)
					if (empty($existedProps[$needCode][$payer]))
					{
						$return = false;
						// записываем поля, которых нет
						$existedProps[$needCode][$payer] = 1;
					}
					else
						unset($existedProps[$needCode][$payer]);
				
			if ($return)
				return $return;
			
			// создаем свойства, каких нет
			$return = true;
			
			$PropsGroup = array();
			$tmpGet = CSaleOrderPropsGroup::GetList(
				array("SORT" => "ASC"),
				array(),
				false
				// array('nTopCount' => '1')
			);
			
			while($tmpElement=$tmpGet->Fetch())
				$PropsGroup[$tmpElement["PERSON_TYPE_ID"]]=$tmpElement['ID'];
			
			foreach ($existedProps as $propCode => $prop)
				foreach ($prop as $payer => $val)
				{
					$arFields = array(
						"PERSON_TYPE_ID" => $payer,
						"NAME" => GetMessage('IPOLapiship_prop_name_'.$propCode),
						"TYPE" => "TEXT",
						"REQUIED" => "N",
						"DEFAULT_VALUE" => "",
						"SORT" => 100,
						"CODE" => $propCode,
						"USER_PROPS" => "N",
						"IS_LOCATION" => "N",
						"IS_LOCATION4TAX" => "N",
						"PROPS_GROUP_ID" => $PropsGroup[$payer],
						"SIZE1" => 10,
						"SIZE2" => 1,
						"DESCRIPTION" => GetMessage('IPOLapiship_prop_descr_'.$propCode),
						"IS_EMAIL" => "N",
						"IS_PROFILE_NAME" => "N",
						"IS_PAYER" => "N",
						"IS_FILTERED" => "Y",
						"IS_ZIP" => "N",
						"UTIL" => "Y"
					);
					
					if(!CSaleOrderProps::Add($arFields))
						$return = false;
					
				}
				
			return $return;
			
		}
		
		if($mode=='2'){
			foreach($existedProps as $existedPropId)
				foreach ($existedPropId as $payerProp)
				if (!CSaleOrderProps::Delete($payerProp))
					echo "Error delete CNTDTARIF-prop id: ".$payerProp."<br>";
		}
		
		return false;
	}
	
	function getRegionName($region)
	{
		$region = explode(" ", $region);
		
		$arExc = array(
			self::toUpper(GetMessage("IPOLapiship_EXCEP_REGION_RESPUBL"))
		);
		
		if (in_array(self::toUpper($region[0]), $arExc))
			return $region[1];
		else
			return $region[0];
	}
	
	// получение названия города
	function getNormalCity($cityID)
	{
		return self::getNormalCityByLocationID($cityID);
		// $arCity = CSaleLocation::GetList(
			// array(),
			// array("CITY_ID" => $cityID, "CITY_LID" => LANGUAGE_ID, "COUNTRY_LID" => LANGUAGE_ID, "REGION_LID" => LANGUAGE_ID),
			// false,
			// false,
			// array()
		// )->Fetch();

		// if ($arCity)
			// return array("BITRIX_ID" => $arCity["ID"], "CITY_ID" => $arCity["CITY_ID"], "NAME" => $arCity["CITY_NAME"], "REGION" => self::getRegionName($arCity["REGION_NAME"]));
	}
	
	function getNormalCityByLocationID($locationID)
	{
		if(method_exists("CSaleLocation","isLocationProMigrated") && CSaleLocation::isLocationProMigrated() && strlen($locationID) > 8)
			$locationID = CSaleLocation::getLocationIDbyCODE($locationID);
			
		$arFilter = array(
			"ID" => $locationID,
			"CITY_LID" => "RU",
			"REGION_LID" => "RU"
		);
		
		$arCity = CSaleLocation::GetList(array(), $arFilter)->Fetch();
		
		if (!$arCity)
		{
			unset($arFilter["REGION_LID"]);
			$arCity = CSaleLocation::GetList(array(), $arFilter)->Fetch();
			if ($arCity)
				$arCity["REGION_NAME"] = "";
		}
		
		if (!$arCity)
			$arCity = CSaleLocation::GetByID($locationID, "RU");
		
		if ($arCity)
		{
			// удаляем из названия города исключения
			$cityDeleteParts = array(
				GetMessage("IPOLapiship_EXCEP_CITY_PGT_1"),
				GetMessage("IPOLapiship_EXCEP_CITY_PGT_2")
			);
			
			foreach ($cityDeleteParts as $template)
				$arCity["CITY_NAME"] = preg_replace("/( )?".$template."( )?/", "", $arCity["CITY_NAME"]);
			
			return array("BITRIX_ID" => $arCity["ID"], "CITY_ID" => $arCity["CITY_ID"], "NAME" => $arCity["CITY_NAME"], "REGION" => self::getRegionName($arCity["REGION_NAME"]));
		}
	}
	
	function getDelivery(){
		if(!cmodule::includeModule("sale")) 
			return false;
		
		if(self::isConverted()){
			$dS = Bitrix\Sale\Delivery\Services\Table::getList(array(
				 'order'  => array('SORT' => 'ASC', 'NAME' => 'ASC'),
				 'filter' => array('CODE' => 'apiship', "ACTIVE" => "Y")
			))->Fetch();
		}else
			$dS = CSaleDeliveryHandler::GetBySID('apiship')->Fetch();
		return $dS;
	}
	
	function getDeliveryProfilesIDs()
	{
		if(!cmodule::includeModule("sale")) 
			return false;
		
		$arDeliveryIDs = array();
		if(self::isConverted()){
			$dS = Bitrix\Sale\Delivery\Services\Table::getList(array(
				 'order'  => array('SORT' => 'ASC', 'NAME' => 'ASC'),
				 'filter' => array('CODE' => 'apiship:%')
			));
			
			while($dataShip = $dS->Fetch())
				$arDeliveryIDs[preg_replace("/apiship:/", "", $dataShip["CODE"])] = $dataShip["ID"];
		}
		
		return $arDeliveryIDs;
	}
	
	// Проверка активности СД
	function isActive(){
		$dS = self::getDelivery();
		return ($dS && $dS['ACTIVE'] == 'Y');
	}
	
	function getProvderIcons()
	{
		$providersList = CDeliveryapiship::GetProvidersList();
		$arProvidersIcons = array();
		
		foreach ($providersList as $providerCode)
			$arProvidersIcons[$providerCode] = $providerCode."-30px.png";
		
		return $arProvidersIcons;
	}
	
	function getProviderIconsURL()
	{
		return "https://storage.apiship.ru/icons/providers/";
	}
	
	////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////// Вспомогательные ///////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	
	function toUpper($str){
		$str = str_replace( //H8 ANSI
			array(
				GetMessage('IPOLapiship_LANG_YO_S'),
				GetMessage('IPOLapiship_LANG_CH_S'),
				GetMessage('IPOLapiship_LANG_YA_S')
			),
			array(
				GetMessage('IPOLapiship_LANG_YO_B'),
				GetMessage('IPOLapiship_LANG_CH_B'),
				GetMessage('IPOLapiship_LANG_YA_B')
			),
			$str
		);
		if(function_exists('mb_strtoupper'))
			return mb_strtoupper($str,LANG_CHARSET);
		else
			return strtoupper($str);
	}
	
	//кодировки
	function zajsonit($handle){
		if(LANG_CHARSET !== 'UTF-8'){
			if(is_array($handle))
				foreach($handle as $key => $val){
					unset($handle[$key]);
					$key=self::zajsonit($key);
					$handle[$key]=self::zajsonit($val);
				}
			else
				$handle=$GLOBALS['APPLICATION']->ConvertCharset($handle,LANG_CHARSET,'UTF-8');
		}
		return $handle;
	}
	function zaDEjsonit($handle){
		if(LANG_CHARSET !== 'UTF-8'){
			if(is_array($handle))
				foreach($handle as $key => $val){
					unset($handle[$key]);
					$key=self::zaDEjsonit($key);
					$handle[$key]=self::zaDEjsonit($val);
				}
			else
				$handle=$GLOBALS['APPLICATION']->ConvertCharset($handle,'UTF-8',LANG_CHARSET);
		}
		return $handle;
	}
	
	function isConverted(){
		return (COption::GetOptionString("main","~sale_converted_15",'N') == 'Y');
	}
	
	function pre($val, $stream=false)
	{
		if ($stream)
			return print_r($val, true);
		
		echo "<pre>";
		print_r($val);
		echo "</pre>";
	}
	
	function errorLog($val)
	{
		$fileName = $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".self::$MODULE_ID."/errLog.txt";
		
		if ($_REQUEST["apishipDebugToLog"] == "SWITCH_ON")
			$_SESSION["IPOLAPISHIP_print_logfile"] = true;
		if ($_REQUEST["apishipDebugToLog"] == "SWITCH_OFF")
			unset($_SESSION["IPOLAPISHIP_print_logfile"]);
		
		if ($_SESSION["IPOLAPISHIP_print_logfile"])
		{
			$file=fopen($fileName,"w");
			fwrite($file,"\n\n".date("H:i:s d-m-Y")."\n");
			fwrite($file,print_r($val,true));
			fclose($file);
		}
	}
	
	function getCourierLang()
	{
		$arMess = IncludeModuleLangFile(__FILE__, false, true);
		return $arMess;
	}
}