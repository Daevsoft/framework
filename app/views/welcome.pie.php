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
			font-family: 'Lato','Roboto script=all rev=1','Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			padding: 0px;
			margin: 0px;
			color:#707070;
			transition:330ms ease-in-out;
			text-decoration: none;
		}
		.bg-top{
			display: block;
			height: 30%;
			width: 100%;
			background-color: #5840FF;
			position: absolute;
			z-index: 0;
		}
		.bg{    
			display: block;
			padding: 50px;
			height: 100%;
			position: relative;
		}
		#box{
			background-color: white;
			box-shadow: 5px 5px 5px rgba(2, 2, 2, 0.16);
			padding: 30px;
			padding-top:7%;
			padding-left: 5%;
			border-radius: 8px;
			height:470px;
			position:inherit;
		}
		.title{
			font-size: 42px;
			margin-bottom: 10px;
		}
		.title-desc{
			font-size: 20px;
		}
		.btn-get{
			color: #92A8E2;
		}
		.btn-get:hover{
			color: #7178d4;
			text-shadow: 2px 2px 2px solid darkgray; 
		}
		.box-desc{
			left:0;
			float: left;
		}
		.box-devices{
			right:0px;
			float: right;
		}
		.devices-pc, .devices-tablet, .devices-phone{
			height:auto;
			position: relative;
		}
		.devices-phone{
			width: 70px;
			right:-35px;
			z-index: 5;
		}
		.devices-pc{
			width: 300px;
			z-index: 4;
		}
		.devices-tablet{
			width: 200px;
			left:-50px;
			z-index: 5;
		}
		.devices-phone:hover,.devices-pc:hover,.devices-tablet:hover{
			z-index: 10;
			transform:translateX(-20px)translateY(-5px);
		}

		@media only screen and (max-width:780px){
			.bg{
				padding:20px;
			}
			.title{
				font-size: 32px;
				margin-bottom: 10px;
			}
			.title-desc{
				font-size: 12px;
			}
			.box-devices{
				transform: scale(0.6);
				width: 100%;
			}
			.devices-phone, .devices-pc, .devices-tablet{
				left:unset;
				right:unset;
				display: inline-block;
			}
			.btn-get{
				font-size:small;
			}
		}
		@media only screen and (max-width:400px){
			.title{
				font-size: 22px;
				margin-bottom: 10px;
			}
		}
	</style>
</head>
<body>
	<div class="bg-top"></div>
	<div class="bg">
		<div id="box">
			<div class="box-desc">
				<span class="title">_(( $title ))</span>
				<hr>
				<p class="title-desc">_(( $welcomeText ))</p>
				<br>
				<br>
				<a class="btn-get" href="https://github.com/Daevsoft/ds/">_(( $buttonText ))</a>
			</div>
			<div class="box-devices">
				<img class="devices-phone" src="_(( assets_source('svg\Phone.svg') ))" alt="">
				<img class="devices-pc" src="_(( assets_source('svg\PC.svg') ))" alt="">
				<img class="devices-tablet" src="_(( assets_source('svg\Tablet.svg') ))" alt="">
			</div>
		</div>
	</div>
</body>
</html>
