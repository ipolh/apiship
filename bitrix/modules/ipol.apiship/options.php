<?
#################################################
#        Company developer: IPOL
#        Developers: Dmitry Kadrichev
#        Site: http://www.ipolh.com
#        E-mail: om-sv2@mail.ru
#        Copyright (c) 2006-2014 IPOL
#################################################
?>
<?
IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");

$module_id = "ipol.apiship";
CModule::IncludeModule($module_id);
if(apishipdriver::$MODULE_ID !== $module_id)
	echo "ERROR IN MODULE ID";

CModule::IncludeModule('sale');
CJSCore::Init(array("jquery"));CJSCore::Init(array('window'));
$isLogged = COption::GetOptionString($module_id,"logged",false); 
$converted = apishipHelper::isConverted();

//определяем статусы заказов
$orderState=array(''=>'');
$tmpValue = CSaleStatus::GetList(array("SORT" => "ASC"), array("LID" => LANGUAGE_ID));
while($tmpVal=$tmpValue->Fetch()){
	if(!array_key_exists($tmpVal['ID'],$orderState))
		$orderState[$tmpVal['ID']]=$tmpVal['NAME']." [".$tmpVal['ID']."]";
}
//плательщики
// $tmpPayers=unserialize(COption::GetOptionString($module_id,'payers','a:0:{}'));
$payers=CSalePersonType::GetList(array('ACTIVE'=>'Y'));
$arPayers=array();
while($payer=$payers->Fetch()){
	$arPayers[$payer['ID']]=array('NAME'=>$payer['NAME']." [".$payer['LID']."]");
	// if(in_array($payer['ID'],$tmpPayers))
		$arPayers[$payer['ID']]['sel']=true;
}
//местоположения
$locProp = CSaleOrderProps::GetList(array(),array("IS_LOCATION"=>"Y"));
$locProps = array();
while($element=$locProp->Fetch())
	$locProps[$element['CODE']] = $element['NAME'];

$arAllOptions = array(
	"logData" => array(
		array("logapiship",GetMessage("IPOLapiship_OPT_logapiship"),false,array("text")),
		array("pasapiship",GetMessage("IPOLapiship_OPT_pasapiship"),false,array("password")),
		array("logged","logged",false,array('text')),//залогинен ли пользователь
		array("token","token",false,array('text')),// токен залогининого юзера
		array("defPickupPVZs","defPickupPVZs",false,array('text')),// последний выбранный пвз забора товара
		// array("companyID","companyID",false,array('text')),// токен залогининого юзера
	),
	"common" => Array(
		// array("isTest",GetMessage("IPOLapiship_OPT_isTest"),"Y",array("checkbox")),
		// array("strName",GetMessage("IPOLapiship_OPT_strName"),false,array("text")),
		// array("delReqOrdr",GetMessage("IPOLapiship_OPT_delReqOrdr"),false,array("checkbox")),
		// array("addJQ",GetMessage("IPOLapiship_OPT_addJQ"),"N",array("checkbox")),
		array("departure",GetMessage("IPOLapiship_OPT_depature"),'',array("text")),
		// array("termInc",GetMessage("IPOLapiship_OPT_termInc"),'',array("text",1)),
		array("prntActOrdr",GetMessage("IPOLapiship_OPT_prntActOrdr"),"O",array("selectbox"),array("O" => GetMessage('IPOLapiship_OTHR_ACTSORDRS'),"A" => GetMessage('IPOLapiship_OTHR_ACTSONLY'))),
		// array("numberOfPrints",GetMessage("IPOLapiship_OPT_numberOfPrints"),"2",array("text",2)),
		array("showInOrders",GetMessage("IPOLapiship_OPT_showInOrders"),"Y",array("selectbox"),array("Y" => GetMessage('IPOLapiship_OTHR_ALWAYS'),"N" => GetMessage('IPOLapiship_OTHR_DELIVERY'))),
	),
	"dimensionsDef" => array(//ѓабариты товаров (дефолтные)
		Array("lengthD", GetMessage("IPOLapiship_OPT_lengthD"), '40', Array("text")),
		Array("widthD", GetMessage("IPOLapiship_OPT_widthD"), '30', Array("text")),
		Array("heightD", GetMessage("IPOLapiship_OPT_heightD"), '20', Array("text")),
		Array("weightD", GetMessage("IPOLapiship_OPT_weightD"), '1000', Array("text")),
		// Array("defMode", GetMessage("IPOLapiship_OPT_defMode"), 'O', array("selectbox"), array('O'=>GetMessage("IPOLapiship_LABEL_forOrder"),'G'=>GetMessage("IPOLapiship_LABEL_forGood"))),
	),
	"extendedOpt" => array(
		Array("roundDP", GetMessage("IPOLapiship_OPT_roundDeliveryPrice"), '0', Array("text")),
		Array("assessedCost", GetMessage("IPOLapiship_OPT_assessedCost"), '0', Array("text")),
		Array("courierPlus", GetMessage("IPOLapiship_OPT_courierPlus"), '0', Array("text")),
		Array("pickupPlus", GetMessage("IPOLapiship_OPT_pickupPlus"), '0', Array("text")),
	),
	"status" => Array(
		array("setDeliveryId", GetMessage("IPOLapiship_OPT_setDeliveryId"),"Y",array("checkbox")),
		array("markPayed", GetMessage("IPOLapiship_OPT_markPayed"),"N",array("checkbox")),
		
		array("uploaded", GetMessage("IPOLapiship_OPT_statusUPLOADED"),false,array("selectbox"),$orderState),
		array("uploadingError", GetMessage("IPOLapiship_OPT_statusUPLOADED_ERROR"),false,array("selectbox"),$orderState),
		array("statusSTORE", GetMessage("IPOLapiship_OPT_statusSTORE"),false,array("selectbox"),$orderState),
		array("statusTRANZT", GetMessage("IPOLapiship_OPT_statusTRANZT"),false,array("selectbox"),$orderState),
		array("statusCORIER", GetMessage("IPOLapiship_OPT_statusCORIER"),false,array("selectbox"),$orderState),
		array("statusPVZ", GetMessage("IPOLapiship_OPT_statusPVZ"),false,array("selectbox"),$orderState),
		array("statusDELIVD", GetMessage("IPOLapiship_OPT_statusDELIVD"),false,array("selectbox"),$orderState),
		array("statusOTKAZ", GetMessage("IPOLapiship_OPT_statusOTKAZ"),false,array("selectbox"),$orderState),
	),
	"storeProps" => Array(// адрес магазина откуда отправляется заказ
		
		Array("Storestreet", GetMessage("IPOLapiship_Storestreet"), '', Array("text")),
		Array("Storehouse", GetMessage("IPOLapiship_Storehouse"), '', Array("text")),
		Array("Storeblock", GetMessage("IPOLapiship_Storeblock"), '', Array("text")),
		Array("Storeoffice", GetMessage("IPOLapiship_Storeoffice"), '', Array("text")),
		Array("StorecompanyName", GetMessage("IPOLapiship_StorecompanyName"), '', Array("text")),
		Array("StorecontactName", GetMessage("IPOLapiship_StorecontactName"), '', Array("text")),
		Array("Storephone", GetMessage("IPOLapiship_Storephone"), '', Array("text")),
		Array("Storeemail", GetMessage("IPOLapiship_Storeemail"), '', Array("text")),
		
		// договор на курьерскую доставку
		Array("DogovorNum", GetMessage("IPOLapiship_DogovorNum"), '', Array("text")),
		Array("DogovorDate", GetMessage("IPOLapiship_DogovorDate"), '', Array("text"))
	),
	"orderProps" => Array(//свойства заказа откуда брать
		Array("location", GetMessage("IPOLapiship_JS_SOD_location"), 'LOCATION', Array("text")),
		Array("zip", GetMessage("IPOLapiship_JS_SOD_postIndex"), 'ZIP', Array("text")),
		Array("name", GetMessage("IPOLapiship_JS_SOD_name"), 'FIO', Array("text")),
		Array("email", GetMessage("IPOLapiship_JS_SOD_email"), 'EMAIL', Array("text")),
		Array("phone", GetMessage("IPOLapiship_JS_SOD_phone"), 'PHONE', Array("text")),
		Array("address", GetMessage("IPOLapiship_JS_SOD_line"), 'ADDRESS', Array("text")),
		Array("street", GetMessage("IPOLapiship_JS_SOD_street"), 'STREET', Array("text")),
		Array("house", GetMessage("IPOLapiship_JS_SOD_house"), 'HOUSE', Array("text")),
		Array("block", GetMessage("IPOLapiship_JS_SOD_block"), 'BLOCK', Array("text")),
		Array("flat", GetMessage("IPOLapiship_JS_SOD_office"), 'FLAT', Array("text")),
	),
	"basket" => array(
		array("hideNal",GetMessage("IPOLapiship_OPT_hideNal"),"Y",array("checkbox")),
		// array("pvzID",GetMessage("IPOLapiship_OPT_pvzID"),"",array("text")),
		array("pvzPicker",GetMessage("IPOLapiship_OPT_pvzPicker"),"ADDRESS",array("text")),
		// array("autoSelOne",GetMessage("IPOLapiship_OPT_autoSelOne"),"",array("checkbox")),
		// array("cntExpress",GetMessage("IPOLapiship_OPT_cntExpress"),"500",array("text")),
		// array("showAddress",GetMessage("IPOLapiship_OPT_showAddress"),"N",array("checkbox")),
	),
	// "addingService" => array(
		// array("addingService",GetMessage("IPOLapiship_OPT_addingService"),"",array("text")),
		// array("tarifs",GetMessage("IPOLapiship_OPT_tarifs"),"",array("text")),
	// ),
	"paySystems" => array(
		array("paySystems",GetMessage("IPOLapiship_OPT_paySystems"),"",array("text")),
	),
/* 	"termsDeliv" => array(
		array("timeSend",GetMessage("IPOLapiship_OPT_timeSend"),"",array("text")),
		array("addHold",GetMessage("IPOLapiship_OPT_addHold"),"",array("text")),
	), */
	"service"=>array(
		array("last",GetMessage("IPOLapiship_JS_SOD_last"),false,array("text")),//последня заявка
		array("schet",GetMessage("IPOLapiship_JS_SOD_schet"),'0',array("text")),//количество заявок
		array("statCync",GetMessage("IPOLapiship_OPT_statCync"),'0',array("text")),//дата последнего опроса статусов заказов
		array("dostTimeout",GetMessage("IPOLapiship_OPT_dostTimeout"),'6',array("text")),//таймаут запроса доставки
	),
	"hiddenServices" => array(
		array("fillDelivPVZID", "", "N",array("checkbox"))
	),
);

if($isLogged)
	$aTabs = array(
		array("DIV" => "edit1", "TAB" => GetMessage("IPOLapiship_TAB_FAQ"), "TITLE" => GetMessage("IPOLapiship_TAB_TITLE_FAQ")),
		array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_SET"), "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
		array("DIV" => "edit3", "TAB" => GetMessage("IPOLapiship_HDR_deliverys"), "TITLE" => GetMessage("IPOLapiship_HDR_deliverys")),
		array("DIV" => "edit4", "TAB" => GetMessage("IPOLapiship_TAB_LIST"), "TITLE" => GetMessage("IPOLapiship_TAB_TITLE_LIST")),
		// array("DIV" => "edit4", "TAB" => GetMessage("IPOLapiship_TAB_CITIES"), "TITLE" => GetMessage("IPOLapiship_TAB_CITIES_LOGIN")),
	);
else
	$aTabs = array(array("DIV" => "edit1", "TAB" => GetMessage("IPOLapiship_TAB_LOGIN"), "TITLE" => GetMessage("IPOLapiship_TAB_TITLE_LOGIN")));

//Restore defaults
if ($USER->IsAdmin() && $_SERVER["REQUEST_METHOD"]=="GET" && strlen($RestoreDefaults)>0 && check_bitrix_sessid())
    COption::RemoveOption($module_id);

//Save options
if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && check_bitrix_sessid())
{
	if(strlen($RestoreDefaults)>0)
		COption::RemoveOption($module_id);
	else{
		$_REQUEST['paySystems']    = ($_REQUEST['paySystems'])    ? serialize($_REQUEST['paySystems'])    : 'a:0:{}';
		$_REQUEST['addingService'] = ($_REQUEST['addingService']) ? serialize($_REQUEST['addingService']) : 'a:0:{}';
		$_REQUEST['tarifs']        = ($_REQUEST['tarifs'])        ? serialize($_REQUEST['tarifs'])        : 'a:0:{}';
		$_REQUEST['dostTimeout']   = (floatval($_REQUEST['dostTimeout']) > 0) ? $_REQUEST['dostTimeout']  : 6;
		$_REQUEST['cntExpress']   = (floatval($_REQUEST['cntExpress']) > 0) ? $_REQUEST['cntExpress']  : 0;
		
		$arNumReq = array('numberOfPrints','termInc','lengthD','widthD','heightD','weightD');
		foreach($arNumReq as $key){
			$_REQUEST[$key] = intval($_REQUEST[$key]);
			if($_REQUEST[$key] <= 0)
				unset($_REQUEST[$key]);
		}
/* 		$holdCity = array();
		foreach($_REQUEST['addHoldCity'] as $ind => $val){
			if(!$val) continue;
			$term = intval($_REQUEST['addHoldTerm'][$ind]);
			if($term)
				$holdCity[$val]=$term;
		}
		$_REQUEST['addHold']=($holdCity)?serialize($holdCity):'a:0:{}'; */
		foreach($arAllOptions as $aOptGroup){
			foreach($aOptGroup as $option){
				__AdmSettingsSaveOption($module_id, $option);
			}
		}
		
		if(COption::GetOptionString($module_id,'delReqOrdr','')=='Y')
			RegisterModuleDependences("sale","OnOrderDelete",$module_id,"imldriver","delReqOD");
		else
			UnRegisterModuleDependences("sale","OnOrderDelete",$module_id,"imldriver","delReqOD");
	}

	if($_REQUEST["back_url_settings"] <> "" && $_REQUEST["Apply"] == "")
		 echo '<script type="text/javascript">window.location="'.CUtil::addslashes($_REQUEST["back_url_settings"]).'";</script>';				
}

function ShowParamsHTMLByArray($arParams)
{
	global $module_id;
	foreach($arParams as $Option)
	{
		if($Option[3][0]!='selectbox'){
			if($Option[0] != 'departure')
				__AdmSettingsDrawRow($module_id, $Option);
			else{
				$cityDef = COption::GetOptionString('sale','location');
				if(!$cityDef){
					$arCites = array();
					$sites = CSite::GetList(
						$by = "sort",
						$order = "asc"
                    );
					$similar = true;
					$oldOp = 'none';
					while($site=$sites->Fetch()){
						$op = COption::GetOptionString('sale','location',false,$site['LID']);
						if($op)
							$arCites[$site['LID']] = $op;
						if($similar && $oldOp != 'none' && $oldOp != $op)
							$similar = false;
						$oldOp = $op;
					}
					if(!count($arCites))
						echo "<tr><td colspan='2'>".GetMessage('IPOLapiship_LABEL_NOCITY')."</td><tr>";
					elseif($similar)
						printapishipcity(array_pop($arCites));
					else{
						$strSel = "<select name='departure'>";
						$seltd = COption::GetOptionString($module_id,'departure','');
						$first = true;
						foreach($arCites as $cite => $city){
							$apishipcity = apishipHelper::getNormalCityByLocationID($city);
							
							$strSel .= "<option ". ($first?"selected":"") ." value='".$apishipcity["BITRIX_ID"]."'>".$apishipcity['NAME']." [$cite]</option>";
							if ($first)
								$first = false;
						}
						echo "<tr><td>".GetMessage('IPOLapiship_OPT_depature')."</td><td>".$strSel."</select></td><tr>";
					}
				}else
					printapishipcity($cityDef);
			}
		}
		else
		{
			$optVal=COption::GetOptionString($module_id,$Option['0'],$Option['2']);
			$str='';
			foreach($Option[4] as $key => $val)
			{
				$chkd='';
				if($optVal==$key)
					$chkd='selected';
				$str.='<option '.$chkd.' value="'.$key.'">'.$val.'</option>';
			}
			echo '<tr>
					<td width="50%" class="adm-detail-content-cell-l">'.$Option[1].'</td>  
					<td width="50%" class="adm-detail-content-cell-r"><select name="'.$Option['0'].'">'.$str.'</select></td>
				</tr>';
		}
	}
}
function showOrderOptions(){//должна вызываться после получения плательщиков
	global $module_id;
	global $arPayers;
	$arNomatterProps=array('street'=>true,'house'=>true,'flat'=>true);
	foreach($GLOBALS['arAllOptions']['orderProps'] as $orderProp){
		$value=COption::getOptionString($module_id,$orderProp[0],$orderProp[2]);
		if(!trim($value)){
			$showErr=true;
			if($orderProp[0]=='address'&&COption::getOptionString($module_id,'street',$orderProp[2])){
				unset($arNomatterProps['street']);
				$showErr=false;
			}
		}
		else
			$showErr=false;

		$arError=array(
			'noPr'=>false,
			'unAct'=>false,
			'str'=>false,
		);

		if(!array_key_exists($orderProp[0],$arNomatterProps)&&$value){
			foreach($arPayers as $payId =>$payerInfo)
				if($payerInfo['sel']){
					if($curProp=CSaleOrderProps::GetList(array(),array('PERSON_TYPE_ID'=>$payId,'CODE'=>$value))->Fetch()){
						if($curProp['ACTIVE']!='Y')
							$arError['unAct'].="<br>".$payerInfo['NAME'];
					}
					else
						$arError['noPr'].="<br>".$payerInfo['NAME'];
				}
			if($arError['noPr']){
				$arError['str']=GetMessage('IPOLapiship_LABEL_noPr')." <a href='#' class='PropHint' onclick='return ipol_popup_virt(\"pop-noPr_".$orderProp[0]."\",$(this));'></a> ";?>
				<div id="pop-noPr_<?=$orderProp[0]?>" class="b-popup" style="display: none; ">
					<div class="pop-text"><?=GetMessage('IPOLapiship_LABEL_Sign_noPr')?><br><br><?=substr($arError['noPr'],4)?></div>
					<div class="close" onclick="$(this).closest('.b-popup').hide();"></div>
				</div>
			<?}
			if($arError['unAct']){
				$arError['str'].=GetMessage('IPOLapiship_LABEL_unAct')." <a href='#' class='PropHint' onclick='return ipol_popup_virt(\"pop-unAct_".$orderProp[0]."\",$(this));'></a>";?>
				<div id="pop-unAct_<?=$orderProp[0]?>" class="b-popup" style="display: none; ">
					<div class="pop-text"><?=GetMessage('IPOLapiship_LABEL_Sign_unAct')?><br><br><?=substr($arError['unAct'],4)?></div>
					<div class="close" onclick="$(this).closest('.b-popup').hide();"></div>
				</div>
			<?}
			
			if($arError['str'])
				$showErr=true;
		}
		elseif(array_key_exists($orderProp[0],$arNomatterProps))
			$showErr=false;
		
		$styleTdStr = ($orderProp[0] == 'street')?'style="border-top: 1px solid #BCC2C4;"':'';
	?>
		<tr>
			<td width="50%" <?=$styleTdStr?> class="adm-detail-content-cell-l"><?=$orderProp[1]?><?=($orderProp[0]=='address')?" <a href='#' class='PropHint' onclick='return ipol_popup_virt(\"pop-address\",$(this));'></a>":''?></td>
			<td width="50%" <?=$styleTdStr?> class="adm-detail-content-cell-r">
				<?if($orderProp[0] != 'location'){?>
					<input type="text" size="" maxlength="255" value="<?=$value?>" name="<?=$orderProp[0]?>">
				<?}else{
					global $locProps;
					if($showErr && !$arError['str']) // не выводить "выберите свойство"
						$showErr = false;
					// Местоположение выбирается автоматически из свойств типа "Местоположение"
					if(count($locProps)==0){
						$showErr = true;
						$arError['str'] = GetMessage('IPOLapiship_LABEL_noLoc');
					}elseif(count($locProps)==1){
						$key = array_pop(array_keys($locProps));
					?>
						<input type='hidden' value="<?=$key?>" name="<?=$orderProp[0]?>">
						<?=array_pop($locProps)?> [<?=$key?>]
					<?}else{?>
						<select name="<?=$orderProp[0]?>">
							<?foreach($locProps as $code => $name){?>
								<option value='<?=$code?>' <?=($value==$code)?"selected":""?>><?=$name." [".$code."]"?></option>
							<?}?>
						</select>
					<?}
				}?>
				&nbsp;&nbsp;<span class='errorText' <?if(!$showErr){?>style='display:none'<?}?>><?=($arError['str'])?$arError['str']:GetMessage('IPOLapiship_LABEL_shPr')?></span>
			</td>
		</tr>
	<?}
}

function printapishipcity($city){ // Вывод города-отправителя
	global $module_id;
	$apishipcity = apishipHelper::getNormalCityByLocationID($city);
	if(!$apishipcity)
		echo "<tr><td colspan='2'>".GetMessage('IPOLapiship_LABEL_NOapishipCITY')."</td><tr>";
	else{
		COption::SetOptionString($module_id,'departure',$apishipcity['BITRIX_ID']);
		echo "<tr><td>".GetMessage('IPOLapiship_OPT_depature')."</td><td>".($apishipcity['NAME'])."</td><tr>";
	}
}


$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>

<?if($isLogged){?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialchars($mid)?>&amp;lang=<?echo LANG?>">
	<?
	$tabControl->Begin();
	$tabControl->BeginNextTab();
	include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$module_id ."/optionsInclude/faq.php");
	$tabControl->BeginNextTab();
	include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$module_id ."/optionsInclude/setups.php");
	$tabControl->BeginNextTab();
	include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$module_id ."/optionsInclude/delivery.php");
	$tabControl->BeginNextTab();
	include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$module_id ."/optionsInclude/table.php");
	$tabControl->Buttons();
	?>
	<div align="left">
		<input type="hidden" name="Update" value="Y">
		<input type="submit" <?if(!$USER->IsAdmin())echo " disabled ";?> name="Update" value="<?echo GetMessage("MAIN_SAVE")?>">
	</div>
	<?$tabControl->End();?>
	<?=bitrix_sessid_post();?>
</form>
<?}
else{
	$tabControl->Begin();
	$tabControl->BeginNextTab();
	include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$module_id ."/optionsInclude/login.php");
	$tabControl->End();
}