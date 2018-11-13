<script>
var IPOLapiship_ProvidersData;
	function IPOLapiship_deliverys_show()
	{
		
		var outBlock = "#IPOLapiship_deliverys_block";
		
		if ($(outBlock).data("load"))
		{
			$(outBlock).toggle();
			return;
		}
		
		var arImages = <?=CUtil::PHPToJSObject(apishipHelper::getProvderIcons())?>,
			image_url = "<?=apishipHelper::getProviderIconsURL()?>";
		
		
		var params = {
				"action": "showProvidersList",
			};
		
		$.ajax({
			url:'/bitrix/js/<?=$module_id?>/ajax.php',
			data:params,
			type:"POST",
			dataType: "json",
			error: function(XMLHttpRequest, textStatus){
				console.log(XMLHttpRequest);
				console.log(textStatus);
			},
			success: function(data){
				console.log(data);
				IPOLapiship_ProvidersData = data;
				
				var html = "";
				for (var i in data)
				{
					html += "<div class = 'delivery_block' data-provider = '"+ i +"' onClick = 'IPOLapiship_deliverys_get_param(\""+ i +"\");'>";
					
					html += "<div class = 'img_block'><img src = '"+ image_url + arImages[i] + "'></div>";
					html += "<div class = 'name_block'>" + i + "</div>";
					
					if (data[i] != false)
						html += "<div class = 'checked' style = 'float: right;'><input type = 'checkbox' checked></div>";
					
					html += "<div style = 'clear: both;float: none;'></div>";
					html += "</div>";
					
					html += "<div class = 'IPOLapiship_delivery_out' data-delivery_out = '"+ i +"'>";
					html += "</div>";
				}
				// html += "</table>";
					
				$(outBlock).html(html);
				$(outBlock).data("load", true);
				$(outBlock).show();
				
				for (var i in data)
					if (data[i] != false)
						IPOLapiship_deliverys_get_param(i);
			}
		});
	}
	
	function IPOLapiship_deliverys_get_param(provider_key)
	{
		
		if ($("[data-delivery_out='"+ provider_key +"']").data("params_show"))
		{
			$("[data-delivery_out='"+ provider_key +"']").toggle();
			return;
		}
		
		var	params = {
				"provider_key": provider_key,
				"action": "getProviderParams"
			};
		
		$.ajax({
			url:'/bitrix/js/<?=$module_id?>/ajax.php',
			data:params,
			type:"POST",
			dataType: "json",
			error: function(XMLHttpRequest, textStatus){
				console.log(XMLHttpRequest);
				console.log(textStatus);
			},
			success: function(data){
				console.log(data);
				var res;
				if (data.is_error)
					res = data.data;
				else
				{
					data = data.data;
					res = "<table data-table_delivery = '"+provider_key+"'>";
					for (var i in data)
						if (data[i].type == "boolean")
						{
							res += "<tr><td>"+ data[i].description +"</td><td><input type = 'checkbox' data-delivery_param='"+i+"'";
							if (IPOLapiship_ProvidersData[provider_key])
								if (IPOLapiship_ProvidersData[provider_key].connectParams[i])
									res += " checked";
							res += "></td></tr>";
						}
						else
						{
							res += "<tr><td>"+ data[i].description +"</td><td><input type = 'text' value = '";
							if (IPOLapiship_ProvidersData[provider_key])
								res += IPOLapiship_ProvidersData[provider_key].connectParams[i];
							res += "' data-delivery_param='"+i+"'><td></tr>";
						}
					res += "</table>";
					
					res += "<div><input style = 'margin-right: 10px;' type = 'button' value = '<?=GetMessage("IPOLapiship_DELIVERYS_OPTION_SAVE")?>' onClick = 'IPOLapiship_SaveDeliverySettings(\""+provider_key+"\");'>"
					
					if (IPOLapiship_ProvidersData[provider_key])
						res += "<input type = 'button' value = '<?=GetMessage("IPOLapiship_DELIVERYS_OPTION_DELETE")?>' onClick = 'IPOLapiship_DeleteDeliverySettings(\""+IPOLapiship_ProvidersData[provider_key].id+"\", \""+provider_key+"\");'></div>";
					
					res += "<div data-delivery_result = '"+provider_key+"'></div>";
				}
				
				$("[data-delivery_out='"+ provider_key +"']").html(res);
				$("[data-delivery_out='"+ provider_key +"']").data("params_show", true);
				$("[data-delivery_out='"+ provider_key +"']").toggle();
			}
		});
	}
	
	function IPOLapiship_SaveDeliverySettings(provider_key)
	{
		var	params = {
				"providerKey": provider_key,
				"action": "saveProviderParams",
				"connectParams": {}
			};
			
		$("table[data-table_delivery="+provider_key+"]").find("input").each(function(){
			var obj = $(this);
			// console.log(obj.attr("type"))
			if (obj.attr("type") == "text")
				params["connectParams"][obj.data("delivery_param")] = $(this).val();
			else
				if ($(this).attr("checked"))
					params["connectParams"][obj.data("delivery_param")] = true;
				// else
					// params["connectParams"][obj.data("delivery_param")] = false;
		});
		
		params["insuranceRate"] = 0;
		params["cashServiceRate"] = 0;
		
		
		if (IPOLapiship_ProvidersData[provider_key])
		{
			params["method"] = "update";
			params["id"] = IPOLapiship_ProvidersData[provider_key].id;
		}
		
		// console.log(params);
		
		$.ajax({
			url:'/bitrix/js/<?=$module_id?>/ajax.php',
			data:params,
			type:"POST",
			dataType: "json",
			error: function(XMLHttpRequest, textStatus){
				console.log(XMLHttpRequest);
				console.log(textStatus);
			},
			success: function(data){
				console.log(data);
				$("[data-delivery_result='"+ provider_key +"']").html(data.msg);
			}
		});
	}
	
	function IPOLapiship_DeleteDeliverySettings(id, provider_key)
	{
		var	params = {
				"id": id,
				"action": "deleteProviderParams"
			};
			
		$.ajax({
			url:'/bitrix/js/<?=$module_id?>/ajax.php',
			data:params,
			type:"POST",
			dataType: "json",
			error: function(XMLHttpRequest, textStatus){
				console.log(XMLHttpRequest);
				console.log(textStatus);
			},
			success: function(data){
				// console.log(data);
				$("[data-delivery_result='"+ provider_key +"']").html(data.msg);
			}
		});
	}
	
	$(document).ready(function(){
		$("#tab_cont_edit3").click(function(){
			$("[onclick^='IPOLapiship_deliverys_show']").click();
		});
	});
</script>

<?//Службы доставки?>
<tr class="heading"><td colspan="2" valign="top" align="center" onClick = "IPOLapiship_deliverys_show();" style="cursor:pointer;text-decoration:underline"><?=GetMessage("IPOLapiship_HDR_deliverys")?></td></tr>
<?//ShowParamsHTMLByArray($arAllOptions["common"]);?>
<tr><td colspan="2" valign="top" align="center" id = "IPOLapiship_deliverys_block" data-load = "false"></td></tr>