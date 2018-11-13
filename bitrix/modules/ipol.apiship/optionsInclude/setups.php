<?
//платежные системы
$PayDefault = COption::GetOptionString($module_id,'paySystems','Y');
if($PayDefault != 'Y')
	$tmpPaySys=unserialize($PayDefault);

$paySysS=CSalePaySystem::GetList(array(),array('ACTIVE'=>'Y'));
$paySysHtml='<select name="paySystems[]" multiple size="5">';
while($paySys=$paySysS->Fetch()){
	$paySysHtml.='<option value="'.$paySys['ID'].'" ';
	if($PayDefault == 'Y') {
		$name = strtolower($paySys['NAME']);
		if( strpos($name, GetMessage('IPOLapiship_cashe')) === false && 
			strpos($name, GetMessage('IPOLapiship_cashe2')) === false && 
			strpos($name, GetMessage('IPOLapiship_cashe3')) === false)
			$paySysHtml.='selected';
	}
	else {
		if(in_array($paySys['ID'],$tmpPaySys))
			$paySysHtml.='selected';
	}
	$paySysHtml.='>'.$paySys['NAME'].'</option>';
}
$paySysHtml.="</select>";
?>
<style>
	.PropHint { 
		background: url("/bitrix/images/ipol.apiship/hint.gif") no-repeat transparent;
		display: inline-block;
		height: 12px;
		position: relative;
		width: 12px;
	}
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
		z-index: 50;
	}
	.b-popup .pop-text { 
		margin-bottom: 10px;
		color:#000;
	}
	.pop-text i {color:#AC12B1;}
	.b-popup .close { 
		background: url("/bitrix/images/ipol.apiship/popup_close.gif") no-repeat transparent;
		cursor: pointer;
		height: 10px;
		position: absolute;
		right: 4px;
		top: 4px;
		width: 10px;
	}
	.IPOLapiship_clz{
		background:url(/bitrix/panel/main/images/bx-admin-sprite-small.png) 0px -2989px no-repeat; 
		width:18px; 
		height:18px;
		cursor: pointer;
		margin-left:100%;
	}
	.IPOLapiship_clz:hover{
		background-position: -0px -3016px;
	}
	.errorText{
		color:red;
		font-size:11px;
	}
	#IPOLapiship_deliverys_block{display: none; width: 80%;}
	
	#IPOLapiship_deliverys_block .delivery_block{
		background: linear-gradient(#e3e3e3, #f8f8f8);
		padding: 3px 10px;
		border-radius: 5px 5px 0 0;
		width: 40%;
		margin-bottom: 5px;
		text-align: left;
		border: 1px solid #DCDADA;
		cursor: pointer;
	}
	
	#IPOLapiship_deliverys_block .delivery_block > div
	{
		float: left;
	}
	
	#IPOLapiship_deliverys_block .delivery_block .img_block
	{
		width: 40%;
		float: left;
	}
	
	#IPOLapiship_deliverys_block .delivery_block img{
		height: 20px;
		border: none;
	}
	
	#IPOLapiship_deliverys_block .delivery_block .name_block
	{
		padding: 3px 0 0 0;
	}
	
	#IPOLapiship_deliverys_block .delivery_block .checked
	{
		margin-top: 2px;
	}
	
	.IPOLapiship_delivery_out{
		display: none;
		background: #fefefe;
		padding: 3px 10px;
		width: 40%;
		margin-top: -6px;
		margin-bottom: 5px;
		border: 1px solid #DCDADA;
		
	}
</style>
<script>	
	function ipol_popup_virt(code, info){
		var offset = $(info).position().top;
		var LEFT = $(info).offset().left;		
		
		var obj;
		if(code == 'next') 	obj = $(info).next();
		else  				obj = $('#'+code);
		
		LEFT -= parseInt( parseInt(obj.css('width'))/2 );
		
		obj.css({
			top: (offset+15)+'px',
			left: LEFT,
			display: 'block'
		});	
		return false;
	}
	
	function IPOLapiship_serverShow(){
		$('.IPOLapiship_service').each(function(){
			$(this).css('display','table-row');
		});
		$('[onclick^="IPOLapiship_serverShow("]').css('cursor','auto');
		$('[onclick^="IPOLapiship_serverShow("]').css('textDecoration','none');
	}
	
	function IPOLapiship_sbrosSchet(){
		if(confirm('<?=GetMessage('IPOLapiship_OTHR_schet_ALERT')?>'))
			$.ajax({
				url:'/bitrix/js/<?=$module_id?>/ajax.php',
				type:'POST',
				data: 'action=killSchet',
				success: function(data){
					if(data=='1')
					{
						alert('<?=GetMessage("IPOLapiship_OTHR_schet_DONE")?>');
						$("[onclick^='IPOLapiship_sbrosSchet(']").parent().html('0');
					}
					else
						alert('<?=GetMessage("IPOLapiship_OTHR_schet_NONE")?>'+data);
				}
			});
	}
	
	function IPOLapiship_clrUpdt(){
		if(confirm('<?=GetMessage('IPOLapiship_OPT_clrUpdt_ALERT')?>'))
		{
			$('.IPOLapiship_clz').css('display','none');
			$.ajax({
				url:'/bitrix/js/<?=$module_id?>/ajax.php',
				type:'POST',
				data: 'action=killUpdt',
				success: function(data){
					if(data=='done')
						$("#IPOLapiship_updtPlc").replaceWith('');
					else
					{
						$('.IPOLapiship_clz').css('display','');
						alert('<?=GetMessage("IPOLapiship_OPT_clrUpdt_ERR")?>');
					}
				}
			});
		}
	}
	
	function IPOLapiship_syncList()
	{
		$("[onclick='IPOLapiship_syncList()']").css('display','none');
		$.post(
			"/bitrix/js/<?=$module_id?>/ajax.php",
			{'action':'callUpdateList'},
			function(data){
				if(data.indexOf('bad')===0)
					alert(data.substr(3));
				else
				{
					$('#IPOLapiship_updtTime').html(data.substr(data.indexOf('-')+1));
					alert(data);
					window.location.reload();
				}
			}
		);
	}
	
	function IPOLapiship_syncOrdrs()
	{
		$('[onclick="IPOLapiship_syncOrdrs()"]').css('display','none');
		$.post(
			"/bitrix/js/<?=$module_id?>/ajax.php",
			{'action':'callOrderStates'},
			function(data){
				$('#IPOLapiship_SO').parent().html(data+"&nbsp;<input type='button' value='<?=GetMessage('IPOLapiship_OTHR_getOutLst_BUTTON')?>' id='IPOLapiship_SO' onclick='IPOLapiship_syncOrdrs()'/>");
				IPOLapiship_getTable();
			}
		);
	}
	
	function IPOLapiship_logoff(){
		$("[onclick='IPOLapiship_logoff()']").attr('disabled','disabled');
		if(confirm('<?=GetMessage("IPOLapiship_LBL_ISLOGOFF")?>'))
			$.post(
				"/bitrix/js/<?=$module_id?>/ajax.php",
				{'action':'logoff'},
				function(data){
					window.location.reload();
				}
			);
		else
			$("[onclick='IPOLapiship_logoff()']").removeAttr('disabled');
	}
	
	function IPOLapiship_addCityHold(){
		var maxCityCnt = parseInt('<?=count($IPOLapiship_list['Region'])?>');
		var ttlCity    = $('[name="addHoldTerm[]"]').length;
		if(ttlCity>=maxCityCnt)
			return;
		
		$('[name="addHoldTerm[]"]:last').closest('tr').after('<tr><td class="adm-detail-content-cell-l"><?=$addHold?></td><td class="adm-detail-content-cell-r"><input type="text" name="addHoldTerm[]"></td></tr>');
		
		if(ttlCity+1>=maxCityCnt)
			$("[onclick='IPOLapiship_addCityHold()']").css('display','none');
	}
	
	function IPOLapiship_onTermChange(){
		var day = parseInt($('[name="termInc"]').val());
		if(isNaN(day))
			day = '';			
		$('[name="termInc"]').val(day);
	}
	
	function IPOLapiship_clearCache(){
		$.post(
			"/bitrix/js/<?=$module_id?>/ajax.php",
			{'action':'clearCache'},
			function(data){
				alert("<?=GetMessage('IPOLapiship_LBL_CACHEKILLED')?>")
			}
		);
	}
	function IPOLapiship_rewriteCities(){
		if(confirm("<?=GetMessage('IPOLapiship_LBL_SURETOREWRITE')?>")){
			$('#IPOLapiship_REWRITECITIES').attr('disabled','disabled');
			$.post(
				"/bitrix/js/<?=$module_id?>/ajax.php",
				{'action':'goSlaughterCities'},
				function(data){
					if(data.indexOf('done')===-1)
						alert(data);
					else{
						alert('<?=GetMessage("IPOLapiship_UPDT_DONE").date("d.m.Y H:i")?>');
						window.location.reload();
					}
				}
			);
		}
	}
	
	//function IPOLapiship_addConvertedDelivery()
	//{
	//	var params = {
	//		"action": "AddProfilesList",
	//	};
	//
	//	$.ajax({
	//		url:'/bitrix/js/<?//=$module_id?>///ajax.php',
	//		data:params,
	//		type:"POST",
	//		dataType: "json",
	//		error: function(XMLHttpRequest, textStatus){
	//			console.log(XMLHttpRequest);
	//			console.log(textStatus);
	//		},
	//		success: function(data){
	//			if (typeof data == "undefined")
	//			{
	//				alert('<?//=GetMessage("IPOLapiship_NOT_CRTD_UNKNOWN_ERROR")?>//');
	//				return;
	//			}
	//
	//			if (data.is_error)
	//				alert(data.error);
	//			else
	//			{
	//				alert('<?//=GetMessage("IPOLapiship_NOT_CRTD_SUCCESS")?>//');
	//				window.location.reload();
	//			}
	//			return;
	//		}
	//	});
	//}
	
	$(document).ready(function(){
		$('[name="termInc"]').on('keyup',IPOLapiship_onTermChange);
	});
</script>

<?
foreach(array("depature","prntActOrdr","numberOfPrints","showInOrders","orderProps","address","pvzID","pvzPicker","hideNal","autoSelOne","cntExpress","AS","statusSTORE","statusTRANZT","statusCORIER","tarifs","dostTimeout","TURNOFF","TARSHOW") as $code){?>
<div id="pop-<?=$code?>" class="b-popup" style="display: none; ">
	<div class="pop-text"><?=GetMessage("IPOLapiship_HELPER_".$code)?></div>
	<div class="close" onclick="$(this).closest('.b-popup').hide();"></div>
</div>
<?}
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$module_id."/errorLog.txt")){
	$errorStr=file_get_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$module_id."/errorLog.txt");
	if(strlen($errorStr)>0){?>
		<tr id='IPOLapiship_updtPlc'><td colspan='2'>
			<div class="adm-info-message-wrap adm-info-message-red">
			  <div class="adm-info-message">
				<div class="adm-info-message-title"><?=GetMessage('IPOLapiship_FNDD_ERR_HEADER')?></div>
					<?=GetMessage('IPOLapiship_FNDD_ERR_TITLE')?>
				<div class="adm-info-message-icon"></div>
			  </div>
			</div>
		</td></tr>
	<?}
}
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$module_id."/hint.txt")){
	$updateStr=file_get_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$module_id."/hint.txt");
	if(strlen($updateStr)>0){?>
		<tr id='IPOLapiship_updtPlc'><td colspan='2'>
			<div class="adm-info-message-wrap">
				<div class="adm-info-message" style='color: #000000'>
					<div class='IPOLapiship_clz' onclick='IPOLapiship_clrUpdt()'></div>
					<?=$updateStr?>
				</div>
			</div>
		</td></tr>
	<?}
}

$dost = apishipHelper::getDelivery();
// if($dost=$dost->Fetch()){
if($dost){
	if($dost['ACTIVE'] != 'Y'){?>
	<tr><td colspan='2'>
		<div class="adm-info-message-wrap adm-info-message-red">
		  <div class="adm-info-message">
			<div class="adm-info-message-title"><?=GetMessage('IPOLapiship_NO_ADOST_HEADER')?></div>
				<?=GetMessage('IPOLapiship_NO_ADOST_TITLE')?>
			<div class="adm-info-message-icon"></div>
		  </div>
		</div>
	</td></tr>
	<?}
}else{?>
	<tr><td colspan='2'>
		<div class="adm-info-message-wrap adm-info-message-red">
		  <div class="adm-info-message">
		  <?if($converted){?>
			<div class="adm-info-message-title"><?=GetMessage('IPOLapiship_NOT_CRTD_HEADER')?></div>
					<?=GetMessage('IPOLapiship_NOT_CRTD_TITLE')?>	
		  <?}else{?>
			<div class="adm-info-message-title"><?=GetMessage('IPOLapiship_NO_DOST_HEADER')?></div>
				<?=GetMessage('IPOLapiship_NO_DOST_TITLE')?>
		  <?}?>
			<div class="adm-info-message-icon"></div>
		  </div>
		</div>
	</td></tr>
	
	<?//if($converted){?>
	<!--	<tr><td>-->
	<!--		<input type="button" onclick="IPOLapiship_addConvertedDelivery()" value="--><?//=GetMessage("IPOLapiship_NOT_CRTD_TITLE_BUTTON")?><!--">-->
	<!--	</td></tr>-->
	<?//}?>
<?}

/*
foreach(array('pickup','courier') as $profile)
	if(!apishipHelper::checkTarifAvail($profile)){?>
		<tr><td colspan='2'>
			<div class="adm-info-message-wrap adm-info-message-red">
			  <div class="adm-info-message">
				<div class="adm-info-message-title"><?=GetMessage("IPOLapiship_NO_PROFILE_HEADER_$profile")?></div>
					<?=GetMessage('IPOLapiship_NO_PROFILE_TITLE')?>
				<div class="adm-info-message-icon"></div>
			  </div>
			</div>
		</td></tr>
	<?}*/
?>

<tr>
	<td align="center"><?=GetMessage("IPOLapiship_LBL_YLOGIN")?>: <strong><?=COption::GetOptionString($module_id,'logapiship','If you see this, something really bad have happend.')?></strong></td>
	<td align="center"><input type='button' onclick='IPOLapiship_logoff()' value='<?=GetMessage('IPOLapiship_LBL_DOLOGOFF')?>'></td>
</tr>
<tr><td></td><td align="center"><input type='button' onclick='IPOLapiship_clearCache()' value='<?=GetMessage('IPOLapiship_LBL_CLRCACHE')?>'></td></tr>
<?//Общие?>
<tr class="heading"><td colspan="2" valign="top" align="center"><?=GetMessage("IPOLapiship_HDR_common")?></td></tr>
<?ShowParamsHTMLByArray($arAllOptions["common"]);?>


<?//Данные магазина?>
<tr class="heading"><td colspan="2" valign="top" align="center"><?=GetMessage("IPOLapiship_storeProps")?></td></tr>
<?ShowParamsHTMLByArray($arAllOptions["storeProps"]);?>
<?//Габариты товаров по умолчанию?>
<tr class="heading"><td colspan="2" valign="top" align="center"><?=GetMessage('IPOLapiship_HDR_MEASUREMENT_DEF')?></td></tr>
<?ShowParamsHTMLByArray($arAllOptions["dimensionsDef"]);?>	
<tr><td colspan="2" ><span><?=GetMessage("IPOLapiship_LABEL_GOODPARAMS")?></span></td></tr>
<?//Свойства заказа?>
<tr class="heading">
	<td colspan="2" valign="top" align="center"><?=GetMessage('IPOLapiship_HDR_orderProps')?></td>
</tr>
<?showOrderOptions();?>
<tr><td style="color:#555; " colspan="2" >
	<a class="moduleHeader" onclick="$(this).next().toggle(); return false;"><?=GetMessage('MLSP_ADDPROPS_TITLE')?></a>
	<div class="moduleInst" ><?=GetMessage('MLSP_ADDPROPS_DESCR')?></div>					
</td></tr>

<?//Статусы заказа?>
<tr class="heading"><td colspan="2" valign="top" align="center"><?=GetMessage("IPOLapiship_HDR_status")?></td></tr>
<?ShowParamsHTMLByArray($arAllOptions["status"]);?>	
<?//Оформление заказа?>
<tr class="heading"><td colspan="2" valign="top" align="center"><?=GetMessage("IPOLapiship_HDR_basket")?></td></tr>
<?ShowParamsHTMLByArray($arAllOptions["basket"]);?>

<?//Расширенные настройки?>
<tr class="heading"><td colspan="2" valign="top" align="center"><?=GetMessage("IPOLapiship_HDR_extended")?></td></tr>
<?ShowParamsHTMLByArray($arAllOptions["extendedOpt"]);?>

<?/*
<tr class="heading"><td colspan="2" valign="top" align="center"><?=GetMessage("IPOLapiship_HDR_delivery")?></td></tr>*/?>
<tr><td colspan="2"><?=GetMessage("IPOLapiship_FAQ_DELIVERY")?></td></tr>
<?//Платежные системы?>
<tr class="heading"><td colspan="2" valign="top" align="center"><?=GetMessage("IPOLapiship_OPT_paySystems")?></td></tr>
<tr><td colspan="2" style='text-align:center'><?=$paySysHtml?></td></tr>

<?/*
<tr class="heading"><td colspan="2" valign="top" align="center"><?=GetMessage("IPOLapiship_LBL_addingService")?></td></tr>
<tr><td colspan="2" valign="top" align="center"><table>
	<?//Тарифы?>
	<tr><td colspan="4" valign="top" align="center"><strong><?=GetMessage("IPOLapiship_OPT_tarifs")?></strong> <a href='#' class='PropHint' onclick='return ipol_popup_virt("pop-tarifs", this);'></a></td></tr>
	<?$arTarifs = apishipdriver::getExtraTarifs();?>
	<tr><th style="width:20px"></th><th><?=GetMessage("IPOLapiship_TARIF_TABLE_NAME")?></th><th><?=GetMessage("IPOLapiship_TARIF_TABLE_SHOW")?></th><th><?=GetMessage("IPOLapiship_TARIF_TABLE_TURNOFF")?></th><th></th></tr>
	<?
	foreach($arTarifs as $tarifId => $tarifOption){?>
		<tr>
			<td style='text-align:center'><?if($tarifOption['DESC']){?><a href='#' class='PropHint' onclick='return ipol_popup_virt("pop-AS<?=$tarifId?>",this);'></a><?}?></td>
			<td><?=$tarifOption['NAME']?></td>
			<td align='center'><input type='checkbox' name='tarifs[<?=$tarifId?>][SHOW]' value='Y' <?=($tarifOption['SHOW']=='Y')?"checked":""?> /></td>
			<td align='center'><input type='checkbox' name='tarifs[<?=$tarifId?>][BLOCK]' value='Y' <?=($tarifOption['BLOCK']=='Y')?"checked":""?> /></td>
			<td>
				<? if($tarifOption['DESC']) {?>
				<div id="pop-AS<?=$tarifId?>" class="b-popup" style="display: none; ">
					<div class="pop-text"><?=$tarifOption['DESC']?></div>
					<div class="close" onclick="$(this).closest('.b-popup').hide();"></div>
				</div>
				<?}?>
			</td>
		</tr>
	<?}?>
	<tr><td colspan='2'><br></td></tr>
</table></td></tr>
	<?//Дополнительные услуги?>
<tr><td colspan="2" valign="top" align="center"><table>
	<tr><td colspan="2" valign="top" align="center"><strong><?=GetMessage("IPOLapiship_OPT_addingService")?></strong> <a href='#' class='PropHint' onclick='return ipol_popup_virt("pop-AS", this);'></a></td></tr>
	<?$arAddService = apishipdriver::getExtraOptions();?>
	<tr><th></th><th><?=GetMessage("IPOLapiship_AS_TABLE_NAME")?></th><th><?=GetMessage("IPOLapiship_AS_TABLE_SHOW")?></th><th><?=GetMessage("IPOLapiship_AS_TABLE_DEF")?></th><th></th></tr>
	<?foreach($arAddService as $asId => $adOption){?>
		<tr>
			<td><a href='#' class='PropHint' onclick='return ipol_popup_virt("pop-AS<?=$asId?>",this);'></a></td>
			<td><?=$adOption['NAME']?></td>
			<td align='center'><input type='checkbox' name='addingService[<?=$asId?>][SHOW]' value='Y' <?=($adOption['SHOW']=='Y')?"checked":""?> /></td>
			<td align='center'><input type='checkbox' name='addingService[<?=$asId?>][DEF]' value='Y' <?=($adOption['DEF']=='Y')?"checked":""?> /></td>
			<td>
				<div id="pop-AS<?=$asId?>" class="b-popup" style="display: none; ">
					<div class="pop-text"><?=$adOption['DESC']?></div>
					<div class="close" onclick="$(this).closest('.b-popup').hide();"></div>
				</div>
			</td>
		</tr>
	<?}?>
</table></td></tr>*/?>

<?// Сервисные свойства?>
<tr class="heading" onclick='IPOLapiship_serverShow()' style='cursor:pointer;text-decoration:underline'>
	<td colspan="2" valign="top" align="center"><?=GetMessage("IPOLapiship_HDR_service")?></td>
</tr> 
<?/*
<tr style='display:none' class='IPOLapiship_service'>
	<td><?=GetMessage('IPOLapiship_OTHR_schet')?></td>
	<td>
	<?
		$tmpVal=COption::GetOptionString($module_id,'schet',0);
		echo $tmpVal;
		if($tmpVal>0){
	?> <input type='button' value='<?=GetMessage('IPOLapiship_OTHR_schet_BUTTON')?>' onclick='IPOLapiship_sbrosSchet()'/>
	<?}?>
	</td>
</tr>	*/?>
<tr style='display:none' class='IPOLapiship_service'>
	<td><?=GetMessage('IPOLapiship_OPT_statCync')?></td>
	<td>
		<?	$optVal = COption::GetOptionString($module_id,'statCync',0);
			if($optVal>0) echo date("d.m.Y H:i:s",$optVal);
			else echo GetMessage('IPOLapiship_OTHR_NOTCOMMITED');
		?>
		<input type='button' value='<?=GetMessage('IPOLapiship_OTHR_getOutLst_BUTTON')?>' id='IPOLapiship_SO' onclick='IPOLapiship_syncOrdrs()'/>
	</td>
</tr>
<tr style='display:none' class='IPOLapiship_service'>
	<td><?=GetMessage('IPOLapiship_OPT_dostTimeout')?></td>
	<td>
		<?	
			$optVal = COption::GetOptionString($module_id,'dostTimeout',6);
			if(floatval($optVal)<=0) $optVal=6;
		?>
		<input type='text' value='<?=$optVal?>' name='dostTimeout' size="1"/>
	</td>
</tr>
<?/*
<tr style='display:none' class='IPOLapiship_service'><td colspan='2' style='text-align:center'>
	<input type='button' value='<?=GetMessage('IPOLapiship_OTHR_rewriteCities_BUTTON')?>' id='IPOLapiship_REWRITECITIES' onclick='IPOLapiship_rewriteCities()'/>
</td></tr>*/?>