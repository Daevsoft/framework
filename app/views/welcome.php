<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=7">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Ds Framework</title>
	<style type="text/css">
		*{
			font-family: roboto,helvetica,sans-serif;
			padding: 0px;
			margin: 0px;
		}
		a {
			text-decoration: none;
		}
		#box{
			margin: auto;
			margin-top: 10%;
			width: 70%;
			text-align:center;
		}
		h1{
			font-weight: 100;
		}
		span{
			font-weight: 300;
		}
	</style>
</head>
<body>
	<div id="box">
		<h1>Welcome to <span>D</span>s Framework ! <span>PHP</span></h1>
			<br>
			<hr>
		<p><?php echo $welcomeText ?></p>
		<br>

		<a href="https://github.com/Daevsoft/ds/">Get Started! (On Development)</a>
		<p style="margin-top:100px">
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="686JCCY9BXTRY">
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
			</form>
		</p>
	</div>
</body>
</html>
