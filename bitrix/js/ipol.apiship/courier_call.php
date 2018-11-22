<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/include/prolog_admin_after.php");

$APPLICATION->SetTitle('Заявки на вызов курьера');

CModule::IncludeModule('ipol.apiship');


if($isAjax = ($_REQUEST['bxsender']=='core_window_cdialog' || $_REQUEST['is_ajax'])) {
	$APPLICATION->RestartBuffer();
}

CModule::IncludeModule('sale');
$dbNotSendOrders = $DB->Query("select * from ipol_apiship where STATUS LIKE 'uploaded'");
$arOrderIDS = [];

while ($arOrder = $dbNotSendOrders->Fetch()) {
	$arOrder['PARAMS'] = unserialize($arOrder['PARAMS']);
	$arOrderIDS[$arOrder['PARAMS']['pvzInfo']['providerKey']][$arOrder['ORDER_ID']] = $arOrder['apiship_ID'];
}

$arSendProviders = array_keys($arOrderIDS);

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
		'DEFAULT' => ''
	),
	'house'       => array(
		'NAME'    => 'Дом',
		'SAVE'    => true,
		'DEFAULT' => ''
	),
	'block'       => array(
		'NAME'    => 'Строение',
		'SAVE'    => true,
		'DEFAULT' => ''
	),
	'companyName' => array(
		'NAME'    => 'Название компании',
		'SAVE'    => true,
		'DEFAULT' => ''
	),
	'contactName' => array(
		'NAME'    => 'ФИО',
		'SAVE'    => true,
		'DEFAULT' => ''
	),
	'phone'       => array(
		'NAME'    => 'Контактный телефон',
		'SAVE'    => true,
		'DEFAULT' => ''
	),
	'email'       => array(
		'NAME'    => 'Емеил',
		'SAVE'    => true,
		'DEFAULT' => ''
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

	<form id='courier_call_form' method="post" enctype="multipart/form-data">
		<h3 style="color:red;"><?= implode('<br/>', $arMessages) ?></h3>
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
		<input type="button" onclick="sendCourierCallReq();" value="Отправить"/>
	</form>

<script>
	sendCourierCallReq = function () {
		var wait = BX.showWait('courier_call_form');

		BX.ajax(
			{
				url: "/bitrix/js/<?=apishipdriver::$MODULE_ID?>/courier_call.php?is_ajax=Y",
				method: 'POST',
				data: BX.ajax.prepareData(BX.ajax.prepareForm(document.querySelector('#courier_call_form')).data),
				dataType: 'html',
				processData: true,
				onsuccess: function (result) {
					console.log('result', result);
					BX.html(BX('courier_call_form'),result);
					BX.closeWait('courier_call_form', wait);
				}
			}
		);
	}
</script>

<?

if($isAjax) {
	die();
}
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>