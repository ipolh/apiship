<?/*if isset($smarty.get.gear_send){?>
	<div class="alert alert-success">Спасибо, ваша заявка принята, с вами свяжется менеджер GearLogistics</div>
<?}else{*/?>

<style>
.has-error{
	border: 1px solid red !important;
}
</style>

<div id = "IPOLapiship_courierCall" style = "display: none;">

<div>
    <p><?=GetMessage("IPOLapiship_JSC_SOD_HEADING")?></p>

<form id="gear_form" action="<?="/bitrix/js/".apishipdriver::$MODULE_ID."/ajax.php"?>" method="POST">
<table style = "width: 100%;">
	<tr><td>
    <div class="form-group">
        <label class="control-label"><?=GetMessage("IPOLapiship_JSC_SOD_CONTACT_NAME")?></label>
		</td><td>
        <input type="text" class="form-control" name="data[gear_name]" value="<?=COption::GetOptionString(self::$MODULE_ID, "StorecontactName")?>" required>
    </div>
	</td></tr>
	
	<tr><td>
    <div class="form-group">
        <label class="control-label"><?=GetMessage("IPOLapiship_JSC_SOD_CONTACT_PHONE")?></label>
		</td><td>
        <input type="text" class="form-control" name="data[gear_phone]" value="<?=COption::GetOptionString(self::$MODULE_ID, "Storephone")?>" required>
    </div>
	</td></tr>
	
	<tr><td>
    <div class="form-group">
        <label class="control-label"><?=GetMessage("IPOLapiship_JSC_SOD_CONTACT_COMPANY")?></label>
		</td><td>
        <input type="text" class="form-control" name="data[gear_client]" value="<?=COption::GetOptionString(self::$MODULE_ID, "StorecompanyName")?>" required>
    </div>
	</td></tr>
	
	<tr><td>
    <div class="form-group">
        <label class="control-label"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DATE")?></label>
		</td><td>
        <select class="form-control" name="data[gear_date]" required>
		<?
		$min_day = 1;
		if (date('H') >= 18)
			$min_day = 2;
		
        for ($day=$min_day; $day <= 7; $day++)
		{
			$selTime = date('Y-m-d', mktime(0,0,0,date('m'),date('d')+$day,date('Y')));
            ?><option value="<?=$selTime?>"><?=$selTime?></option><?
		}
		?>
        </select>
    </div>
	</td></tr>
		
	<tr colspan = "2"><td>
    <div class="form-group">
        <div><label class="control-label"><?=GetMessage("IPOLapiship_JSC_SOD_VOLUME")?></label></div>
		</td><td>
	<label class="radio-inline">
		<input type="radio" name="data[gear_volume]" value="0" checked><?=GetMessage("IPOLapiship_JSC_SOD_VOLUME_BEFORE_05")?> 
	</label>
	<label class="radio-inline">
		<input type="radio" name="data[gear_volume]" value="1"><?=GetMessage("IPOLapiship_JSC_SOD_VOLUME_AFTER_05")?> 
	</label>
	</div>
	</td></tr>

	<tr><td>
    <div class="form-group">
        <label class="control-label"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_ADDRESS")?></label>
		</td><td>
		<?
		$city = COption::GetOptionString(self::$MODULE_ID,'departure','');
		$apishipcity = apishipHelper::getNormalCityByLocationID($city);
		
		$addrText = $apishipcity["NAME"].", ";
		$addrText .= COption::GetOptionString(self::$MODULE_ID, "Storestreet").", ";
		$addrText .= COption::GetOptionString(self::$MODULE_ID, "Storehouse").", ";
		$addrText .= COption::GetOptionString(self::$MODULE_ID, "Storeblock").", ";
		$addrText .= COption::GetOptionString(self::$MODULE_ID, "Storeoffice");
		?>
        <textarea class="form-control" name="data[gear_address]" required><?=$addrText?></textarea>
    </div>
	</td></tr>
	
	<tr><td>
    <div class="form-group">
        <label class="control-label"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_TIME")?></label>
		</td><td>
        <select class="form-control" name="data[gear_time]" required>
            <option value="10:00-13:00">10:00-13:00</option>
            <option value="12:00-15:00">12:00-15:00</option>
            <option value="14:00-17:00">14:00-17:00</option>
            <option value="16:00-19:00">16:00-19:00</option>
        </select>
    </div>
	</td></tr>
	
	<tr><td colspan = "2">
	<div style="font-size: 11px;"><span class="glyphicon glyphicon glyphicon-info-sign"></span><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DESC1")?></div>
	</td></tr>
	
	<tr><td colspan = "2">
    <div class="form-group">
	<label class="control-label" style="position: relative;"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY")?></label>
    <div class="checkbox">
        <label>
            <input type="checkbox" value="<?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_SDEK")?>" name="data[gear_provider][]"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_SDEK")?>
        </label>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" value="<?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_IML")?>" name="data[gear_provider][]"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_IML")?>
        </label>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" value="<?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_A1")?>" name="data[gear_provider][]"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_A1")?>
        </label>
    </div>
	<div class="checkbox">
        <label>
            <input type="checkbox" value="<?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_REWORKER")?>" name="data[gear_provider][]"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_REWORKER")?>
        </label>
    </div>
	<div class="checkbox">
        <label>
            <input type="checkbox" value="<?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_SHOP_LOGIST")?>" name="data[gear_provider][]"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_SHOP_LOGIST")?>
        </label>
    </div>
    <div class="checkbox">
        <label>
            <input type="checkbox" value="<?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_ACCORD_POST")?>" name="data[gear_provider][]"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DELIVERY_ACCORD_POST")?>
        </label>
    </div>
	<div style="font-size: 11px;"><span class="glyphicon glyphicon glyphicon-info-sign"></span><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DESC2")?></div>
    </div>
	</td></tr>
	
	<tr><td colspan = "2">
    <div class="form-group">
        <label class="control-label"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_COMMENT")?></label>
        <textarea class="form-control" name="data[gear_comment]" style = "width: 100%;"></textarea>
    </div>
	</td></tr>
	
	<tr><td colspan = "2">
	<p style="font-size: 11px;"><span class="glyphicon glyphicon glyphicon-exclamation-sign"></span><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DESC3")?></p>
	</td></tr>
	
	<tr><td colspan = "2">
    <div class="checkbox">
        <label>
            <input type="checkbox" value="1" name="data[gear_offer_accept]" required> <b><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_OFFERT_DOC")?></a></b>
        </label>
    </div>
	</td></tr>
	
	<tr><td colspan = "2">
	<p style="font-size: 11px;"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DESC4")?></p>

<p style="font-size: 11px;"><?=GetMessage("IPOLapiship_JSC_SOD_COURIER_DESC5")?></p>

	
    <button type="submit" class="btn btn-default" style = "display: none;"/>
	<input type="hidden" name="order_num" value="<?=($orderinfo['ACCOUNT_NUMBER'])?$orderinfo['ACCOUNT_NUMBER']:$orderId?>">
	<input type="hidden" name="action" value="sendCourierCall">
</td></tr>
<?/*<input type="hidden" name="relocation" value="{$smarty.server.REQUEST_URI}?insales_id={$smarty.get.insales_id}&token={$smarty.get.token}">*/?>
</table>
</form>

</div>
</div>



<script>
IPOLapiship_Sender.sendCourierRequest = function(){
		$('#gear_form .has-error').removeClass('has-error');

		var errors = 0;
		$('#gear_form input[required]').each(function(){ //console.log($(this));
			if ($(this).val().length == 0) {
				console.log($(this));
				$(this).addClass('has-error');
				if (errors == 0)
					$(this).focus();
				errors++;
			}
		});
		
		if ($('input[name="data[gear_offer_accept]"]:checked').length == 0) {
			$('input[name="data[gear_offer_accept]"]').parents('.checkbox').addClass('has-error');
			errors++;
		}
		
		if ($('input[name="data[gear_provider][]"]:checked').length == 0) {
			$('input[name="data[gear_provider][]"]:first').parents('.form-group').addClass('has-error');
			errors++;
		}

		// console.log(errors);
		// return;
		
		if (errors == 0)
		{
			$.ajax({
				url: "/bitrix/js/<?=apishipdriver::$MODULE_ID?>/ajax.php",
				data: $('#gear_form').serialize(),
				type:"POST",
				dataType: "json",
				error: function(XMLHttpRequest, textStatus){
					console.log(XMLHttpRequest.responseText);
					console.log(textStatus);
				},
				success: function(data)
				{
					console.log(data);
					if (data.success)
					{
						if (confirm("<?=GetMessage("IPOLapiship_JSC_SOD_COURIER_REQ_SUCCESS")?>"))
							IPOLapiship_Sender.courierForm.Close();
					}
					else
					{
						var errStr = "";
						console.log(data.data);
						for (var i in data.data)
							errStr += data.data[i]+"\n";
						console.log(errStr);
						confirm(errStr);
					}
				},
			});
		}
			// $('#gear_form').trigger('submit');
};


IPOLapiship_Sender.courierForm = false;
IPOLapiship_Sender.showCourierForm = function(){
	if (!IPOLapiship_Sender.courierForm)
	{
		
		var html = $('#IPOLapiship_courierCall').html();
		$('#IPOLapiship_courierCall').remove();
		
		IPOLapiship_Sender.courierForm = new BX.CDialog({
			title: "<?=GetMessage('IPOLapiship_JSC_SOD_COURTITLE')?>",
			content: html,
			icon: 'head-block',
			resizable: true,
			draggable: true,
			height: '500',
			width: '475',
			buttons: [
				// отправить  заявку на курьера
				'<input type=\"button\" value=\"<?=GetMessage('IPOLapiship_JSC_SOD_COURCALL')?>\"  onclick=\"IPOLapiship_Sender.sendCourierRequest();\"/>',
			]
		});
	}
	
	IPOLapiship_Sender.courierForm.Show();
};
</script>
<?//}?>