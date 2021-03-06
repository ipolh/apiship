<?/*if isset($smarty.get.gear_send){?>
	<div class="alert alert-success">Спасибо, ваша заявка принята, с вами свяжется менеджер GearLogistics</div>
<?}else{*/

$dbNotSendOrders = $DB->Query("select * from ipol_apiship where STATUS LIKE 'uploaded'");
$arOrderIDS = [];

while ($arOrder = $dbNotSendOrders->Fetch()) {
	$arOrder['PARAMS'] = unserialize($arOrder['PARAMS']);
	$arOrderIDS[$arOrder['PARAMS']['pvzInfo']['providerKey']][$arOrder['ORDER_ID']] = $arOrder['apiship_ID'];
}

$arParameters = array(
	'providerKey' => array(
		'NAME'     => 'Служба доставки',
		'SAVE'     => false,
		'DEFAULT'  => '',
		'VARIANTS' => $arSendProviders
	),
	'date'        => array(
		'NAME'    => 'Дата доставки',
		'SAVE'    => false,
		//	"INPUT_TYPE" => 'date',
		'DEFAULT' => date('Y-m-d', time() + 86400)  //  "date": "2015-08-27",
	),
	'timeStart'   => array(
		'NAME'    => 'Начальное время доставки',
		'SAVE'    => true,
		'DEFAULT' => '10:00'
	),
	'timeEnd'     => array(
		'NAME'    => 'Конечное время доставки',
		'SAVE'    => true,
		'DEFAULT' => '12:00'
	),
	'weight'      => array(
		'NAME'    => 'Вес заказа, г.',
		'SAVE'    => true,
		'DEFAULT' => '1000'
	),
	'width'       => array(
		'NAME'    => 'Ширина коробки, см.',
		'SAVE'    => true,
		'DEFAULT' => '20'
	),
	'height'      => array(
		'NAME'    => 'Высота коробки, см.',
		'SAVE'    => true,
		'DEFAULT' => '20'
	),
	'length'      => array(
		'NAME'    => 'Длина коробки, см.',
		'SAVE'    => true,
		'DEFAULT' => '20'
	),
	'orderIds'    => array(
		'NAME'    => 'Номера заказов (через запятую)',
		'SAVE'    => false,
		'DEFAULT' => ''
	),
	'region'      => array(
		'NAME'    => 'Регион',
		'SAVE'    => true,
		'DEFAULT' => 'Москва'
	),
	'area'        => array(
		'NAME'    => 'Область',
		'SAVE'    => true,
		'DEFAULT' => 'Москва'
	),
	'city'        => array(
		'NAME'    => 'Город',
		'SAVE'    => true,
		'DEFAULT' => 'Москва'
	),
	'cityGuid'    => array(
		'NAME'    => 'Город - код ФИАС',
		'SAVE'    => true,
		'DEFAULT' => '0c5b2444-70a0-4932-980c-b4dc0d3f02b5'
	),
	'street'      => array(
		'NAME'    => 'Улица',
		'SAVE'    => true,
		'DEFAULT' => 'Березовая аллея'
	),
	'house'       => array(
		'NAME'    => 'Дом',
		'SAVE'    => true,
		'DEFAULT' => '5а'
	),
	'block'       => array(
		'NAME'    => 'Строение',
		'SAVE'    => true,
		'DEFAULT' => 'с1-3'
	),
	'companyName' => array(
		'NAME'    => 'Название компании',
		'SAVE'    => true,
		'DEFAULT' => 'ИП Лазуткин Андрей Валерьевич'
	),
	'contactName' => array(
		'NAME'    => 'ФИО',
		'SAVE'    => true,
		'DEFAULT' => 'Лазуткин Андрей'
	),
	'phone'       => array(
		'NAME'    => 'Контактный телефон',
		'SAVE'    => true,
		'DEFAULT' => '+7 (916) 597-85-40'
	),
	'email'       => array(
		'NAME'    => 'Емеил',
		'SAVE'    => true,
		'DEFAULT' => 'info@audio-drive.ru'
	)
);
$arMessages = [];

$arJsonOrders = [];

foreach ($arOrderIDS as $providerKey => $arOrders) {
	$arJsonOrders[$providerKey] = implode(',', array_values($arOrders));
	echo '<br/>Заказы в ' . $providerKey . ':';
	foreach ($arOrders as $bitrixID => $apishipID) {
		echo '  <b>' . $bitrixID . '</b>(' . $apishipID . '),';
	}
}

echo '<script>window.orders=' . json_encode($arJsonOrders) . ';</script>';
CJSCore::Init('jquery');
?>

    <script>
		$(document).ready(function () {
			$(document).on('click', 'input.input-providerKey', function (e) {
				$('input.input-orderIds').val(window.orders[$(this).attr('value')]);
			});
		});
    </script>

<?

if ($_REQUEST['SEND']) {

	$arDataToSend = [];

	foreach ($arParameters as $strParamCode => $arParamOptions) {

		$arParameters[$strParamCode]['VALUE'] = trim((string)$_REQUEST['SEND'][$strParamCode]);
		if ($arParamOptions['SAVE']) {
			\COption::SetOptionString('ipol.apiship', 'default_' . $strParamCode, $arParameters[$strParamCode]['VALUE']);
		}
		if ($strParamCode == 'orderIds' && $arParameters[$strParamCode]['VALUE']) {
			$arParameters[$strParamCode]['~VALUE'] = explode(',', $arParameters[$strParamCode]['VALUE']);
		} else if ($strParamCode == 'phone' && $arParameters[$strParamCode]['VALUE']) {
			$arParameters[$strParamCode]['~VALUE'] = preg_replace('/[^0-9]+/', '', $arParameters[$strParamCode]['VALUE']);
		}
		$arDataToSend[$strParamCode] = $arParameters[$strParamCode]['~VALUE'] ?: $arParameters[$strParamCode]['VALUE'];
	}

	$arDataToSend = array_filter($arDataToSend);
	$arDataToSend['flat'] = '-';
	$arDataToSend['office'] = '-';

	$arSend = [
		"WHERE"  => "courierCall",
		"METHOD" => "POST",
		"DATA"   => $arDataToSend
	];

	$req_res = \apishipdriver::MakeRequest($arSend);

	$arMessages[] = $req_res['result']['message'];
	$arMessages[] = $req_res['result']['description'];

	foreach ($req_res['errors'] as $arError) {
		$arMessages[] = $arError['field'] . ": " . $arError['message'];
	}

	if ($req_res['result']['providerNumber']) {
		$arMessages[] = 'Успешно создана заявка ' . $req_res['result']['providerNumber'];
	}
}

foreach ($arParameters as $strParamCode => $arParamOptions) {

	$arParamOptions['DEFAULT'] = \COption::GetOptionString('ipol.apiship', 'default_' . $strParamCode, $arParamOptions['DEFAULT']);

}

?>

<style>
.has-error{
	border: 1px solid red !important;
}
</style>

<div id = "IPOLapiship_courierCall" style = "display: none;">

<div>
    <p><?=GetMessage("IPOLapiship_JSC_SOD_HEADING")?></p>

    <h3 style="color:red;"><?= implode('<br/>', $arMessages) ?></h3>
    <form method="post" enctype="multipart/form-data">
        <table style="width: 100%;">
			<?
			foreach ($arParameters as $strParamCode => $arParamOptions) { ?>
                <tr>
                <td width="20%"><label><?= $arParamOptions['NAME'] ?></label></td>
                <td>
					<? if ($arParamOptions['VARIANTS']) {
						?>
						<? foreach ($arParamOptions['VARIANTS'] as $providerCode) { ?>
                            <label><input type="radio" class="input-<?= $strParamCode ?>"
                                          name="SEND[<?= $strParamCode ?>]"
                                          value="<?= $providerCode ?>"
							              <? if ($providerCode == $arParamOptions['VALUE']) { ?>checked<? } ?>/><?= $providerCode ?>
                            </label>
                            <br/>
							<?
						}
					} else {
						?>
                        <input class="input-<?= $strParamCode ?>" style="width:80%;"
                               type="<?= $arParamOptions['INPUT_TYPE'] ?: 'text' ?>"
                               name="SEND[<?= $strParamCode ?>]"
                               value="<?= $arParamOptions['VALUE'] ?: $arParamOptions['DEFAULT'] ?>"/>
						<?
					}
					?></td></tr><?
			}
			?>
        </table>
        <input type="submit" value="Отправить"/>
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