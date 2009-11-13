// $Id: register.js 424 2007-11-19 04:58:38Z develop_tong $
function agree_terms()
{
	if (document.terms.agree_to_terms.value === '')
	{
		alert ( mustagreeterms );
		return false;
	}
}

function validate()
{
	if (document.REG.username.value == "" || document.REG.password.value == "" || document.REG.passwordconfirm.value == "" || document.REG.email.value == "")
	{
		alert ( inputallform );
		return false;
	}
}

function check_user_account()
{
	var username = $('user_name').value;
	xajax.setDo('user');
	mxajax_check_user_account(username);
}
function check_user_email()
{
	xajax.setDo('user');
	var email = $('email').value;
	mxajax_check_user_email(email);
}

function confirm_user_email()
{
	var email = $('email').value;
	$("mail_img_ok").style.display = 'none';
	$("mail_img_err").style.display = 'none';
	$("mail_ver").innerHTML = '';
	if (email == $('emailconfirm').value)
	{
		if (!email)
		{
			return;
		}
		else
		{
			$("mail_img_ok").style.display = 'inline';
			$("submit_registerinfo").disabled = false;
		}
	}
	else
	{
		$("mail_img_err").style.display = 'inline';
		$("submit_registerinfo").disabled = true;
		$("mail_ver").innerHTML = lang_g['g_check_email'];
	}
}

function check_user_password()
{
	var password = $('pass_word').value;
	$("pass_img_ok").style.display = 'none';
	$("pass_img_err").style.display = 'none';
	$("pass_ver").innerHTML = '';
	if (password == $('passwordconfirm').value)
	{
		if (!password)
		{
			return;
		}
		else
		{
			$("pass_img_ok").style.display = 'inline';
			$("submit_registerinfo").disabled = false;
		}
	}
	else
	{
		$("pass_img_err").style.display = 'inline';
		$("submit_registerinfo").disabled = true;
		$("pass_ver").innerHTML = lang_g['g_check_password'];
	}
}