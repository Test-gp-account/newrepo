<?php
include("config/config.inc.php");
include("config/config_ip_access.php");
if(isset($_SESSION['user_id']))
{
	if ($_SESSION['user_id']!='') 
	{
		?>
		<script type="text/javascript">
			window.location="index.php";
		</script>
		<?php
	}
}
if(isset($_REQUEST['login']))
{
	$security_code=$_REQUEST['security_code'];
	if (is_null($security_code) || $security_code =='') {
	$error = 'CAPTCHA not entered';
	} 

	elseif ($security_code != $_SESSION['security_code']) {
		$error = 'Invalid CAPTCHA entered';
	}
	else
	{ 
		$ip_address= $_SERVER['REMOTE_ADDR'];
		$id=session_id();

		$user_id = trim($_POST['user_name']);
		$password = trim($_POST['password']);

		//get salt for user
		$exe_salt = $dbcon->execute_query("select * FROM ".ADMIN_USER." WHERE user_code = '".$user_id."'");
		$res_salt = $dbcon->fetch_one_record();
		$salt = $res_salt['salt_key'];

		//encrypt password
		$encrypted_password = hash('sha512', $salt.$password); 
//echo $encrypted_password;exit; 
		$query = "select * from ".ADMIN_USER." where user_code = '".$user_id."' and password = '".$encrypted_password."'";
		$execute = $dbcon->execute_query($query);
		$num = $dbcon->count_records();
		$result = $dbcon->fetch_one_record();
		$flag = 0;
		if($num > 0) //login credentials success
		{
			$auth_arr = array(5,33); // array for direct access  
			if(!in_array($result['id'],$auth_arr))
			{
				if(!in_array($_SERVER['REMOTE_ADDR'], $ip_array))
				{
					$flag++;  //remove comment when you want to use IP check
					$error = 'Access not authorised';
				}
			}
			if($flag==0)	
			{
				if($result['status'] == 'Active')
				{
				
					$_SESSION['user_name'] = $result['user_fullname']; // ASSIGNING THE USER NAME IN SESSION
					$_SESSION['user_code'] = $user_id; // ASSIGINING THE USER CODE IN SESSION
					$_SESSION['user_id'] = $result['id'];
					$_SESSION['user_type_id'] = $result['user_type_id'];
					$_SESSION['user_news_display_name'] = $result['news_display_name'];

					//create log
					$login_track_q="INSERT into ".LOGIN_TRACK." (user_id,login_time,ip_address,session_id,system_comments,authentication_status) VALUES ('".$result['id']."', now(),'".$ip_address."','".$id."','Login Authenticated','Success')";

					mysql_query($login_track_q)or die(mysql_error());

					//update last_successful_login timestamp for user
					//code to be written

					//send to admin home page
					header("Location: index.php");
				}
				elseif($result['status'] == 'Deactivated')
				{
					$login_track_q="INSERT into ".LOGIN_TRACK." (user_id,login_time,ip_address,session_id,system_comments,authentication_status) VALUES ('".$result['id']."', now(),'".$ip_address."','".$id."','Login Attempt on Deactivated ID','Fail')";

					mysql_query($login_track_q)or die(mysql_error());

					//increment no_of_failed_login_attempts for user
					//code to be written

					//if no_of_failed_login_attempts > 3 then 
					//change user status to 'blocked' and 'system_comments' = 'User Blocked due to repeated login attempt on deactivated id'

				}
				elseif($result['status'] == 'Blocked')
				{
					$login_track_q="INSERT into ".LOGIN_TRACK." (user_id,login_time,ip_address,session_id,system_comments,authentication_status) VALUES ('".$result['id']."', now(),'".$ip_address."','".$id."','Login Attempt on Blocked ID','Fail')";

					mysql_query($login_track_q)or die(mysql_error());

					//increment no_of_failed_login_attempts for user
					//code to be written

				}
			}  
		}
		elseif(empty($user_id)) //empty fields
		{
			echo "Please Enter User Id !!";
			exit;
		}
		else //login credentials failure
		{
			//check if attempts are being made on a valid user id 
			$query = "select * from ".ADMIN_USER." where user_code = '".$user_id."'";
			$execute = $dbcon->execute_query($query);
			$num = $dbcon->count_records();
			$result = $dbcon->fetch_one_record();

			if($num > 0) //valid user id
			{
				$system_comments = 'Hacking attempt on existing User id: '.$user_id.' with Password: '.$password;
				$login_track_q="INSERT into ".LOGIN_TRACK." (user_id,login_time,ip_address,session_id,system_comments,authentication_status) VALUES ('".$result['id']."', now(),'".$ip_address."','".$id."','".$system_comments."','Fail')";
				mysql_query($login_track_q)or die(mysql_error());

				//increment no_of_failed_login_attempts for user
				//code to be written

				//if no_of_failed_login_attempts > 3 then 
				//change user status to 'blocked' and 'system_comments' = 'User Blocked due to repeated login attempt with wrong password'
			}
			else //invalid user id
			{
				$system_comments = 'Automated hacking attempt - User id: '.$user_id.', Password: '.$password;
				$login_track_q="INSERT into ".LOGIN_TRACK." (user_id,login_time,ip_address,session_id,system_comments,authentication_status) VALUES (0, now(),'".$ip_address."','".$id."','".$system_comments."','Fail')";
				
				mysql_query($login_track_q)or die(mysql_error());
			}

			//Set mark_for_blacklisting flag to 'Yes'
			//code to be written

			$error="Wrong User Id or Password !!";
		}
	}

}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="./css/admin_style.css" rel="stylesheet" type="text/css">
	<link href="./css/style.css" rel="stylesheet" type="text/css">
</head>
<body onLoad="document.getElementById('loginid').focus();">

	<div id="container">
		<?php require_once("adheader.php");?>
		<!-- new panel by aditya -->	
		<section class="login">
			<div class="col-100">
				<div class="panel" style="max-width: 365px;">
					<div class="panel-body">
						<div class="login-heading">
							<h3>Login</h3>
						</div>
						<form name="form2" method="post" action="login.php">
							<div class="form-panel">
								<div class="error-panel">
									<?php
									if (isset($error)) 
									{
										?>
										<b><?php echo $error;?></b>
										<?php
									}
									?>
									<input name="back" type="hidden" id="back" value="<?php echo $_REQUEST['back']?>">
								</div>
							</div>
							<div class="form-panel">
								<input name="user_name" type="text" id="loginid" autocomplete="off" size="32" class="form-text " placeholder="Username">
							</div>
							<div class="form-panel">
								<input name="password" type="password" class="form-text" id="password" placeholder="Password">
							</div>

							<div class="form-panel">
								<img src="captcha/GenerateCaptcha.php">
							</div>
							<div class="form-panel">
								<input type="text" class="form-text" name="security_code" id="security_code" autocomplete="off" placeholder="Enter Captcha" />
							</div>
							<div class="form-panel">
								<input name="login" type="submit" class="button-submit" value="Login">
							</div>
						</form>
					</div>
				</div>
			</div>
		</section>
		<?php require_once("include/footer.php");?>
	</div> <!--end of container div -->
</body>
</html>
