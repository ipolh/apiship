<?
#################################################
#        Company developer: IPOL
#        Developer: Dmitry Kadrichev
#        Site: http://www.ipol.com
#        E-mail: om-sv2@mail.ru
#        Copyright (c) 2006-2012 IPOL
#################################################
?>
<?
IncludeModuleLangFile(__FILE__); 

if(class_exists("ipol_apiship")) 
    return;
	
Class ipol_apiship extends CModule{
    var $MODULE_ID = "ipol.apiship";
    var $MODULE_NAME;
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "N";
        var $errors;

	function ipol_apiship(){
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php"); // ������� ������!

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("IPOLapiship_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("IPOLapiship_INSTALL_DESCRIPTION");
        
        $this->PARTNER_NAME = "Ipol";
        $this->PARTNER_URI = "http://www.ipolh.com";
	}
	
	function InstallDB(){
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;
		
		if(!$DB->Query("SELECT 'x' FROM ipol_apiship", true))
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/db/mysql/install.sql");
		
		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}
		
		return true;
	}


	function UnInstallDB(){
		global $DB, $DBType, $APPLICATION;
		$this->errors = false;
		
		$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/db/mysql/uninstall.sql");
		if(!empty($this->errors)){
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		return true;
	}
	
	function InstallEvents(){
		//������� ��������������� � ����� /classes/general/apishiphelper.php ������� auth
		return true;
	}
	function UnInstallEvents() {
		UnRegisterModuleDependences("main", "OnEpilog", $this->MODULE_ID, "apishipdriver", "onEpilog"); // ���������� ������		
		UnRegisterModuleDependences("main", "OnEndBufferContent", $this->MODULE_ID, "CDeliveryapiship", "onBufferContent"); // ���������� ������ � ������ ��� ������������
		UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepOrderProps", $this->MODULE_ID, "CDeliveryapiship", "pickupLoader");
		UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepProcess", $this->MODULE_ID, "CDeliveryapiship", "loadComponent",900);
		UnRegisterModuleDependences("main", "OnAdminListDisplay", $this->MODULE_ID, "apishipdriver", "displayActPrint"); // ������
		UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "apishipdriver", "OnBeforePrologHandler");
		UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepComplete", $this->MODULE_ID, "apishipdriver", "orderCreate"); // �������� ������
		UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepPaySystem", $this->MODULE_ID, "CDeliveryapiship", "checkNalD2P");
		UnRegisterModuleDependences("sale", "OnSaleComponentOrderOneStepDelivery", $this->MODULE_ID, "CDeliveryapiship", "checkNalP2D");
		return true;
	}

	function InstallFiles(){
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/images/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/images/".$this->MODULE_ID, true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID, true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/components/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/", true, true);
		//���� �������� ���������� �  ����� /classes/general/imlhelper.php ������� auth
		// $fileOfActs = $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID."/printActs.php";
		// if(file_exists($fileOfActs) && LANG_CHARSET === 'UTF-8')
			// file_put_contents($fileOfActs,$GLOBALS['APPLICATION']->ConvertCharset(file_get_contents($fileOfActs),'windows-1251','UTF-8'));
		return true;
	}
	function UnInstallFiles(){
		DeleteDirFilesEx("/bitrix/js/".$this->MODULE_ID);
		DeleteDirFilesEx("/bitrix/images/".$this->MODULE_ID);
		DeleteDirFilesEx("/bitrix/php_interface/include/sale_delivery/delivery_apiship.php");
		DeleteDirFilesEx("/bitrix/components/ipol/ipol.apishipPickup");
		DeleteDirFilesEx("/upload/".$this->MODULE_ID);
		$arrayOfFiles=scandir($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/ipol');
		$flagForDelete=true;
		foreach($arrayOfFiles as $element){
			if(strlen($element)>2)
				$flagForDelete=false;
		}
		if($flagForDelete)
			DeleteDirFilesEx("/bitrix/components/ipol");
		return true;
	}
	
    function DoInstall(){
        global $DB, $APPLICATION, $step;
		$this->errors = false;
		
		$this->InstallDB();
		$this->InstallEvents();
		$this->InstallFiles();
		
		RegisterModule($this->MODULE_ID);
		
        $APPLICATION->IncludeAdminFile(GetMessage("IPOLapiship_INSTALL"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
    }

    function DoUninstall(){
        global $DB, $APPLICATION, $step;
		$this->errors = false;
		
		COption::SetOptionString($this->MODULE_ID,'logapiship','');
		COption::SetOptionString($this->MODULE_ID,'pasapiship','');
		COption::SetOptionString($this->MODULE_ID,'logged',false);
		 
		$this->UnInstallDB();
		$this->UnInstallFiles();
		$this->UnInstallEvents();
		
		CAgent::RemoveModuleAgents('ipol.apiship');
		
		UnRegisterModule($this->MODULE_ID);
        $APPLICATION->IncludeAdminFile(GetMessage("IPOLapiship_DEL"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/unstep1.php");
    }
}
?>
