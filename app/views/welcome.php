<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=7">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>DS Framework</title>
	<link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Lato" />
	<style type="text/css">
		*{
			font-family: 'Lato','Roboto script=all rev=1',Helvetica;
			padding: 0px;
			margin: 0px;
			color:white;
			transition:330ms ease-in-out;
		}
		body{
			background-color: rgb(2, 2, 66);
		}
		a {
			text-decoration: none;
		}
		#box{
			position:relative;
			margin: auto;
			margin-top: 5%;
			width: 70%;
			height: 400px;
			padding:50px;
			border-radius: 30px;
			background-color: rgb(44, 49, 169);
			overflow: hidden;
		}
		.title{
			font:inherit;
			font-size: 40px;
			font-style: normal;
			font-weight: 100;
		}
		.btn-get{
			padding:15px;
			background-color: darkorange;
			margin-top:15px;
			display: block;
			position: absolute;
		}
		.btn-get:hover{
			background-color: orange;
			margin-top: 20px;
		}
		.box-rotate{
			position: absolute;
			background-color: white;
			width:800px;
			height:800px;
			right:-50px;
			bottom: -140%;
			overflow: hidden;
			transform: rotateZ(30deg);
		}
	</style>
</head>
<body>
	<div id="box">
		<span class="title"><span>D</span>S Framework<span></span></span>
			<br>
			<br>
		<p><?php echo $welcomeText ?></p>
		<br>
		<a class="btn-get" href="https://github.com/Daevsoft/ds/">Get Started</a>
		<div class="box-rotate"></div>
	</div>
</body>
</html>
