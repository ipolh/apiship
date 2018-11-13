<style>
	.ipol_header {
		font-size: 16px;
		cursor: pointer;
		display:block;
		color:#2E569C;
	}

	.ipol_inst {
		display:none; 
		margin-left:10px;
		margin-top:10px;
	}
	img{border: 1px dotted black;}
</style>
<script>
	function IPOLapiship_auth(){
		$("[onclick='IPOLapiship_auth()']").attr('disabled','disabled');
		var login    = $('#IPOLapiship_login').val();
		var password = $('#IPOLapiship_pass').val();
		
		if(!login){
			alert('<?=GetMessage("IPOLapiship_ALRT_NOLOGIN")?>');
			$("[onclick='IPOLapiship_auth()']").removeAttr('disabled');
			return;
		}
		if(!password){
			alert('<?=GetMessage("IPOLapiship_ALRT_NOPASS")?>');
			$("[onclick='IPOLapiship_auth()']").removeAttr('disabled');
			return;
		}
		$.post(
			"/bitrix/js/<?=$module_id?>/ajax.php",
			{
				'action'     : 'auth',
				'login'      : login,
				'password'   : password,
			},
			function(data){
			console.log(data);
				if(data.trim().indexOf('G')===0){
					alert(data.trim().substr(1));
					window.location.reload();
				}
				else{
					alert(data);
					$("[onclick='IPOLapiship_auth()']").removeAttr('disabled');
					$('.ipol_inst').css('display','block');
					$('#ipol_mistakes').css('display','block');
				}
			}
		);
	}
	function IPOLapiship_doSbmt(e){
		if(e.keyCode==13)
			IPOLapiship_auth();
	}
	
	var IPOL_apishipDialog;
	function IPOLapiship_register_win()
	{
		IPOL_apishipDialog = new BX.CDialog({
			title: "<?=GetMessage("IPOLapiship_REGWIN_TITLE")?>",
			head: "<?=GetMessage("IPOLapiship_REGWIN_HEAD")?>",
			content: "<?=GetMessage("IPOLapiship_REGWIN_CONTENT")?>",
			height: 200,
			width: 400,
			resizable: false,
			buttons: [{
				title: "<?=GetMessage("IPOLapiship_LBL_REGISTRATION")?>",
				id: "IPOLapiship_WIN_REGISTER_BUTTON",
				// id: id кнопки,
				action: function () {
					IPOLapiship_register();
				},
				// onclick: "BX.WindowManager.Get().Close()"
			}]
		});
		
		IPOL_apishipDialog.Show();
	}
	
	function IPOLapiship_empty(val)
	{
		if (typeof val == "undefined")
			return true;
		else
			if (val.length <= 0)
				return true;
		
		return false;
	}
	
	function IPOLapiship_register()
	{
		var login = $("#IPOLapiship_REGISTER_LOGIN").val(),
			pass = $("#IPOLapiship_REGISTER_PASSWORD").val(),
			err = "";
			
		if (IPOLapiship_empty(login))
			err += "<?=GetMessage("IPOLapiship_REGISTER_EMPTY_LOGIN")?>";
		
		if (err != "")
		{
			$("#IPOLapiship_register_win_result").html(err);
			return;
		}
		
		$("#IPOLapiship_WIN_REGISTER_BUTTON").prop("disabled", true);
		
		$.ajax({
			url:"/bitrix/js/<?=$module_id?>/ajax.php",
			
			data:{
				'action'     : 'register',
				'login'      : login,
				'password'   : pass,
			},
			type:"POST",
			dataType: "json",
			error: function(XMLHttpRequest, textStatus){
				console.log(XMLHttpRequest);
				console.log(textStatus);
			},
			success:function(data){
				$("#IPOLapiship_WIN_REGISTER_BUTTON").prop("disabled", false);
				console.log(data);
				err = "";
				if (data.code != 200)
					err += "<?=GetMessage("IPOLapiship_REGISTER_SERVER_UNAVAIL")?>";
				
				if (err != "")
				{
					$("#IPOLapiship_register_win_result").html(err);
					return;
				}
				
				if (!IPOLapiship_empty(data.res.errors))
					for (var k in data.res.errors)
						if (typeof data.res.errors[k].message != "undefined")
							err += data.res.errors[k].message;
						
				if (data.res.code != 200)
					if (typeof data.res.description != "undefined")
						err += data.res.description;
				
				if (err != "")
				{
					$("#IPOLapiship_register_win_result").html(err);
					return;
				}
				
				$("#IPOLapiship_register_win_result").html("<?=GetMessage("IPOLapiship_REGISTER_SUCCESS")?>" + "<br>" + data.res.login + "<br>" + data.res.password);
			}
		});
	}
	
	$(document).ready(function(){
		$('#IPOLapiship_login').on('keyup',IPOLapiship_doSbmt);
		$('#IPOLapiship_pass').on('keyup',IPOLapiship_doSbmt);
	});
</script>
<tr><td>Account</td><td><input type='text' id='IPOLapiship_login'></td></tr>
<tr><td>Secure_password</td><td><input type='password' id='IPOLapiship_pass'></td></tr>

<tr><td></td><td><input type='button' value='<?=GetMessage('IPOLapiship_LBL_AUTHORIZE')?>' onclick='IPOLapiship_auth()'></td></tr>

<tr><td style="color:#555;" colspan="2">
	<a class="ipol_header" onclick="$(this).next().toggle(); return false;"><?=GetMessage('IPOLapiship_FAQ_API_TITLE')?></a>
	<div class="ipol_inst"><?=GetMessage('IPOLapiship_FAQ_API_DESCR')?>
	<input type='button' value='<?=GetMessage('IPOLapiship_LBL_REGISTRATION')?>' onclick='IPOLapiship_register_win()'>
	</div>
	
</td></tr>