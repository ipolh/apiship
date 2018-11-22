<?/*if isset($smarty.get.gear_send){?>
	<div class="alert alert-success">Спасибо, ваша заявка принята, с вами свяжется менеджер GearLogistics</div>
<?}else{*/?>

<style>
.has-error{
	border: 1px solid red !important;
}
</style>


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

		IPOLapiship_Sender.courierForm = new BX.CDialog({
			title: "<?=GetMessage('IPOLapiship_JSC_SOD_COURTITLE')?>",
			content_url: "/bitrix/js/<?=apishipdriver::$MODULE_ID?>/courier_call.php",
			icon: 'head-block',
			resizable: true,
			draggable: true,
			height: '500',
			width: '475'
		});
	}

	IPOLapiship_Sender.courierForm.Show();
};
</script>
<?//}?>