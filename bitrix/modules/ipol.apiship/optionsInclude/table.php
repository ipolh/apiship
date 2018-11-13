<?
// получаем возможные статусы заказов для фильтра
$arStatuses = apishipdriver::GetVarStatuses();

?>
<style>
	.sortTr
	{
		cursor:pointer;
	}
	.sortTr:hover{opacity:0.7;}
	.mdTbl{overflow:hidden;}
	.IPOLapiship_TblStOk td{
		background-color:#E2FCE2!important;
	}
	.IPOLapiship_TblStErr td{
		background-color:#FFEDED!important;
	}
	.IPOLapiship_TblStTzt td{
		background-color:#FCFCBF!important;
	}	
	.IPOLapiship_TblStDel td{
		background-color:#E9E9E9!important;
	}

	.IPOLapiship_TblStStr td{
		background-color:#FCFFCE!important;
	}
	.IPOLapiship_TblStCor td{
		background-color:#D9FFCE!important;
	}	
	.IPOLapiship_TblStPVZ td{
		background-color:#D9FFCE!important;
	}	
	.IPOLapiship_TblStOtk td{
		background-color:#FFCECE!important;
	}	
	.IPOLapiship_TblStDvd td{
		background-color:#ABFFAB!important;
	}

	.IPOLapiship_TblStOk:hover td,.IPOLapiship_TblStErr:hover td, IPOLapiship_TblStTzt:hover td, IPOLapiship_TblStStr:hover td, IPOLapiship_TblStCor:hover td, IPOLapiship_TblStPVZ:hover td, IPOLapiship_TblStOtk:hover td, IPOLapiship_TblStDvd:hover td{
		background-color:#E0E9EC!important;
	}
	.IPOLapiship_crsPnt{
		cursor:pointer;
	}
	.mdTbl{
		border-bottom: 1px solid #DCE7ED;
		border-left: 1px solid #DCE7ED;
		border-right: 1px solid #DCE7ED;
		border-top: 1px solid #C4CED2;
	}
	#IPOLapiship_flrtTbl{
		background: url("/bitrix/panel/main/images/filter-bg.gif") transparent;
		border-bottom: 1px solid #A0ABB0;
		border-radius: 5px 5px 5px;
		text-overflow: ellipsis;
		text-shadow: 0px 1px rgba(255, 255, 255, 0.702);
	}
	.IPOLapiship_mrPd td{
		padding: 5px;
	}
</style>
<script type='text/javascript'>
	function IPOLapiship_getTable(params)
	{
		if(typeof params == 'undefined')
			params={};
		
		var fltObj=IPOLapiship_setFilter();
		
		for(var i in fltObj)
			params[i]=fltObj[i];
		
		params['pgCnt']=(typeof params['pgCnt'] == 'undefined')?$('#IPOLapiship_tblPgr').val():params['pgCnt'];
		params['page']=(typeof params['page'] == 'undefined')?$('#IPOLapiship_crPg').html():params['page'];
		params['by']=(typeof params['by'] == 'undefined')?'ID':params['by'];
		params['sort']=(typeof params['sort'] == 'undefined')?'DESC':params['sort'];
		params['action']='tableHandler';

		$('#IPOLapiship_tblPls').find('td').css('opacity','0.4');
		// console.log(params);
		$.ajax({
			url:"/bitrix/js/<?=$module_id?>/ajax.php",
			data:params,
			type:'POST',
			dataType: 'json',
			success:function(data){
				// console.log(data);
				if(data['ttl']==0)
					$('#IPOLapiship_flrtTbl').parent().html('<?=GetMessage('IPOLapiship_OTHR_NO_REQ')?>');
				else
				{
					$('[onclick="IPOLapiship_nxtPg(-1)"]').css('visibility','visible');
					$('[onclick="IPOLapiship_nxtPg(1)"]').css('visibility','visible');
					if(data.cP==1)
						$('[onclick="IPOLapiship_nxtPg(-1)"]').css('visibility','hidden');
					if(data.cP>=data.mP)
						$('[onclick="IPOLapiship_nxtPg(1)"]').css('visibility','hidden');
					$('#IPOLapiship_crPg').html(data.cP);
					
					$('#IPOLapiship_ttlCls').html('<?=GetMessage('IPOLapiship_TABLE_COLS')?> '+((parseInt(data.cP)-1)*data.pC+1)+' - '+Math.min(parseInt(data.ttl),parseInt(data.cP)*data.pC)+' <?=GetMessage('IPOLapiship_TABLE_FRM')?> '+data.ttl);
					$('#IPOLapiship_tblPls').html(data.html);
				}
			},
			error: function(XMLHttpRequest, textStatus){
				console.log(XMLHttpRequest.responseText);
				console.log(textStatus);
			},
		});
	}
	
	function IPOLapiship_killSign(oid){ // отзыв и удаление заявки
		if(confirm('<?=GetMessage("IPOLapiship_JSC_SOD_IFKILL")?>'))
			$.post(
				"/bitrix/js/<?=$module_id?>/ajax.php",
				{'action':'killReqOD','oid':oid},
				function(data){
					if(data.indexOf('GD:')===0){
						alert(data.substr(3));
						IPOLapiship_getTable();
						if(IPOLapiship_wndKillReq)
							IPOLapiship_wndKillReq.Close();
					}
					else
						alert(data);
				}
			);
	}
	
	function IPOLapiship_delSign(oid, api_id){ // удаление за¤вки
		if(confirm('<?=GetMessage("IPOLapiship_JSC_SOD_IFDELETE")?>'))
			$.post(
				"/bitrix/js/<?=$module_id?>/ajax.php",
				{'action':'delReqOD','oid':oid, "api_id": api_id},
				function(data){
					alert(data);
					IPOLapiship_getTable();
				}
			);
	}
	
	function IPOLSDEL_forseKillSign(oid){ // уничтожить любым способом
		if(confirm('<?=GetMessage("IPOLapiship_JSC_SOD_IFKILL")?>'))
			$.post(
				"/bitrix/js/<?=$module_id?>/ajax.php",
				{'action':'killReqOD','oid':oid},
				function(data){
					if(data.indexOf('GD:')===0){
						alert(data.substr(3));
						IPOLapiship_getTable();
						if(IPOLapiship_wndKillReq)
							IPOLapiship_wndKillReq.Close();
					}
					else{
						$.post(
							"/bitrix/js/<?=$module_id?>/ajax.php",
							{'action':'delReqOD','oid':oid},
							function(data){
								alert(data);
								IPOLapiship_getTable();
								if(IPOLapiship_wndKillReq)
									IPOLapiship_wndKillReq.Close();
							}
						);
					}
				}
			);
	}
	
	function IPOLapiship_followSign(wat){
		window.open("http://www.edostavka.ru/track.html?order_id="+wat,"_blank");
	}
	
	function IPOLapiship_printReq(wat){
		$.ajax({
			url  : "/bitrix/js/<?=$module_id?>/ajax.php",
			type : 'POST',
			data : {
				action : 'printOrderInvoice',
				oId    : wat
			},
			dataType : 'json',
			success  : function(data){
				if(data.result == 'ok')
					window.open('/upload/<?=$module_id?>/'+data.file);
				else
					alert(data.error);
			}
		});
	}
	
	IPOLapiship_wndKillReq=false;
	function IPOLapiship_callKillReq(){	
		if(!IPOLapiship_wndKillReq){
			IPOLapiship_wndKillReq = new BX.CDialog({
				title: '<?=GetMessage('IPOLapiship_OTHR_killReq_TITLE')?>',
				content: "<div><a href='javascript:void(0)' onclick='$(this).next().toggle(); return false;'>?</a><small style='display:none'><?=GetMessage('IPOLapiship_OTHR_killReq_DESCR')?></small><br><?=GetMessage('IPOLapiship_OTHR_killReq_LABEL')?> <input size='3' type='text' id='IPOLapiship_delDeqOrId'><br><?=GetMessage('IPOLapiship_OTHR_killReq_HINT')?></div>",
				icon: 'head-block',
				resizable: false,
				draggable: true,
				height: '145',
				width: '200',
				buttons: ['<input type="button" value="<?=GetMessage('IPOLapiship_OTHR_killReq_BUTTON')?>" onclick="IPOLSDEL_forseKillSign($(\'#IPOLapiship_delDeqOrId\').val())"/>']
			});
		}
		else
			$('#IPOLapiship_delDeqOrId').val('');
		IPOLapiship_wndKillReq.Show();
	}
	
	function IPOLapiship_clrCls()
	{
		$('.adm-list-table-cell-sort-up').removeClass('adm-list-table-cell-sort-up');
		$('.adm-list-table-cell-sort-down').removeClass('adm-list-table-cell-sort-down');
	}
	
	function IPOLapiship_sort(wat,handle)
	{
		if(handle.hasClass("adm-list-table-cell-sort-down"))
		{
			IPOLapiship_clrCls();
			handle.addClass("adm-list-table-cell-sort-up");
			IPOLapiship_getTable({'by':wat,'sort':'ASC'});
		}
		else
		{
			if(handle.hasClass("adm-list-table-cell-sort-up"))
			{
				IPOLapiship_clrCls();
				IPOLapiship_getTable();
			}
			else
			{
				IPOLapiship_clrCls();
				handle.addClass("adm-list-table-cell-sort-down");
				IPOLapiship_getTable({'by':wat,'sort':'DESC'});
			}
		}
	}
	
	function IPOLapiship_nxtPg(cntr)
	{
		var page=parseInt($("#IPOLapiship_crPg").html())+cntr;
		if(page<1)
			page=1;
			
		if(page!=parseInt($("#IPOLapiship_crPg").html()))
		{
			IPOLapiship_getTable({"page":page});
			$("#IPOLapiship_crPg").html(page);
		}
	}
	
	function IPOLapiship_shwPrms(handle)
	{
		handle.siblings('a').hide();
		handle.css('height','auto');
		var height=handle.height();
		handle.css('height','0px');
		handle.animate({'height':height},500);
	}
	
	function IPOLapiship_setFilter()
	{
		var params={};
		$('[id^="IPOLapiship_Fltr_"]').each(function(){
			var crVal=$(this).val();
			if(crVal)
				params['F'+$(this).attr('id').substr(17)]=crVal;
		});
		// console.log(params);
		return params;
	}

	function IPOLapiship_resFilter()
	{
		$('[id^="IPOLapiship_Fltr_"]').each(function(){
			$(this).val('');
		});
	}
	
	function IPOLapiship_syncOutb()
	{
		IPOLapiship_syncOrdrs();
		IPOLapiship_getTable();
	}

	$(document).ready(function(){
		IPOLapiship_getTable();
	});
	
</script>

<div id="pop-statuses" class="b-popup" style="display: none; ">
	<div class="pop-text"><?=GetMessage("IPOLapiship_HELPER_statuses")?></div>
	<div class="close" onclick="$(this).closest('.b-popup').hide();"></div>
</div>

<tr><td colspan='2'>
		<table id='IPOLapiship_flrtTbl'>
		  <tbody>
			<tr class='IPOLapiship_mrPd'>
			  <td><?=GetMessage('IPOLapiship_JS_SOD_number')?></td><td><input type='text' class='adm-workarea' id='IPOLapiship_Fltr_>=ORDER_ID'><span class="adm-filter-text-wrap" style='margin: 4px 12px 0px'>...</span><input type='text' class='adm-workarea' id='IPOLapiship_Fltr_<=ORDER_ID'></td>
			</tr>
			<tr class='IPOLapiship_mrPd'>
				<td><?=GetMessage('IPOLapiship_JS_SOD_STATUS')?> <a href='#' class='PropHint' onclick='return ipol_popup_virt("pop-statuses", this);'></a></td>
				<td>
					<select id='IPOLapiship_Fltr_STATUS'>
						<?/*<option value=''      ></option>
						<option value='NEW'   >NEW</option>
						<option value='ERROR' >ERROR</option>
						<option value='OK'    >OK</option>
						<option value='STORE' >STORE</option>
						<option value='TRANZT'>TRANZT</option>
						<option value='CORIER'>CORIER</option>
						<option value='PVZ'   >PVZ</option>
						<option value='OTKAZ' >OTKAZ</option>
						<option value='DELIVD'>DELIVD</option>*/?>
						<option value=''      ></option>
						<option value='NEW'   >NEW</option>
						<?
						foreach($arStatuses as $status)
						{
							?><option value='<?=$status?>'   ><?=$status?></option><?
						}
						?>
					</select>
				</td>
			</tr>
			<tr class='IPOLapiship_mrPd'>
			  <td><?=GetMessage('IPOLapiship_JS_SOD_apiship_ID')?></td><td><input type='text' class='adm-workarea' id='IPOLapiship_Fltr_>=apiship_ID'><span class="adm-filter-text-wrap" style='margin: 4px 12px 0px'>...</span><input type='text' class='adm-workarea' id='IPOLapiship_Fltr_<=apiship_ID'></td>
			</tr>
			<tr class='IPOLapiship_mrPd'>
				<td><?=GetMessage('IPOLapiship_TABLE_UPTIME')?></td>
				<td>
					<div class="adm-input-wrap adm-input-wrap-calendar">
						<input type='text' class='adm-workarea' id='IPOLapiship_Fltr_>=UPTIME' name='IPOLapishipupF' disabled>
						<span class="adm-calendar-icon" onclick="BX.calendar({node:this, field:'IPOLapishipupF', form: '', bTime: true, bHideTime: false});"></span>
					</div>
					<span class="adm-filter-text-wrap" style='margin: 4px 12px 0px'>...</span>
					<div class="adm-input-wrap adm-input-wrap-calendar">
						<input type='text' class='adm-workarea' id='IPOLapiship_Fltr_<=UPTIME' name='IPOLapishipupD' disabled>
						<span class="adm-calendar-icon" onclick="BX.calendar({node:this, field:'IPOLapishipupD', form: '', bTime: true, bHideTime: false});"></span>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan='2'><div class="adm-filter-bottom-separate" style="margin-bottom:0px;"></div></td>
			</tr>
			<tr class='IPOLapiship_mrPd'>
				<td colspan='2'><input class="adm-btn" type="button" value="<?=GetMessage('MAIN_FIND')?>" onclick="IPOLapiship_getTable()">&nbsp;&nbsp;&nbsp;<input class="adm-btn" type="button" value="<?=GetMessage('MAIN_RESET')?>" onclick="IPOLapiship_resFilter()"></td>
			</tr>
		  </tbody>
		</table>
		<br><br>
		<table class="adm-list-table mdTbl">
			<thead>
				<tr class="adm-list-table-header">
					<td class="adm-list-table-cell"><div></div></td>
					<td class="adm-list-table-cell sortTr" style='width:50px;' onclick='IPOLapiship_sort("ID",$(this))'><div class='adm-list-table-cell-inner'>ID</div></td>
					<td class="adm-list-table-cell sortTr" style='width:50px;' onclick='IPOLapiship_sort("MESS_ID",$(this))'><div class='adm-list-table-cell-inner'><?=GetMessage('IPOLapiship_JS_SOD_MESS_ID')?></div></td>
					<td class="adm-list-table-cell sortTr" style='width:77px;' onclick='IPOLapiship_sort("ORDER_ID",$(this))'><div class='adm-list-table-cell-inner'><?=GetMessage('IPOLapiship_TABLE_ORDN')?></div></td>
					<td class="adm-list-table-cell sortTr" style='width:77px;' onclick='IPOLapiship_sort("STATUS",$(this))'><div class='adm-list-table-cell-inner'><?=GetMessage('IPOLapiship_JS_SOD_STATUS')?></div></td>
					<td class="adm-list-table-cell sortTr" style='width:77px;' onclick='IPOLapiship_sort("apiship_ID",$(this))'><div class='adm-list-table-cell-inner'><?=GetMessage('IPOLapiship_JS_SOD_apiship_ID')?></div></td>
					<td class="adm-list-table-cell"><div class='adm-list-table-cell-inner'><?=GetMessage('IPOLapiship_TABLE_PARAM')?></div></td>
					<td class="adm-list-table-cell"><div class='adm-list-table-cell-inner'><?=GetMessage('IPOLapiship_TABLE_MESS')?></div></td>
					<td class="adm-list-table-cell sortTr" style='width:50px;' onclick='IPOLapiship_sort("UPTIME",$(this))'><div class='adm-list-table-cell-inner'><?=GetMessage('IPOLapiship_TABLE_UPTIME')?></div></td>
				</tr>
			</thead>
			<tbody id='IPOLapiship_tblPls'>
			</tbody>
		</table>
		<div class="adm-navigation">
			<div class="adm-nav-pages-block">
				<span class="adm-nav-page adm-nav-page-prev IPOLapiship_crsPnt" onclick='IPOLapiship_nxtPg(-1)'></span>
				<span class="adm-nav-page-active adm-nav-page" id='IPOLapiship_crPg'>1</span>
				<span class="adm-nav-page adm-nav-page-next IPOLapiship_crsPnt" onclick='IPOLapiship_nxtPg(1)'></span>
			</div>
			<div class="adm-nav-pages-total-block" id='IPOLapiship_ttlCls'><?=GetMessage('IPOLapiship_TABLE_COLS?')?> 1 Ц 5 <?=GetMessage('IPOLapiship_TABLE_FRM')?> 5</div>
			<div class="adm-nav-pages-number-block">
				<span class="adm-nav-pages-number">
					<span class="adm-nav-pages-number-text"><?=GetMessage('admin_lib_sett_rec')?></span>
					<select id='IPOLapiship_tblPgr' onchange='IPOLapiship_getTable()'>
						<option value="5">5</option>
						<option value="10">10</option>
						<option value="20" selected="selected">20</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="200">200</option>
						<option value="0"><?=GetMessage('MAIN_OPTION_CLEAR_CACHE_ALL')?></option>
					</select>
				</span>
			</div>
		</div>
		
		<input type='button' style='margin-top:20px' value='<?=GetMessage('IPOLapiship_OTHR_killReq_BUTTON')?>' onclick='IPOLapiship_callKillReq()'>&nbsp;
		<input type='button' style='margin-top:20px' value='<?=GetMessage('IPOLapiship_OTHR_getOutLst_BUTTON_OT')?>' onclick='IPOLapiship_syncOutb()'/>
	</td></tr>