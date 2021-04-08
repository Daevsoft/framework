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
			font-family: "Roboto", Arial, Helvetica, sans-serif;
			padding: 0px;
			margin: 0px;
			color:#707070;
			transition:330ms ease-in-out;
			text-decoration: none;
		}
		.bg-top{
			display: block;
			height: 15rem;
			width: 100%;
			background-color: #5840FF;
			z-index: 0;
		}
		.bg{
			position: absolute;
			width: 100%;
			top: 4rem;
		}
		#box{
			background-color: white;
			box-shadow: 5px 5px 5px rgb(2 2 2 / 16%);
			padding: 6rem 0;
			border-radius: 8px;
			margin: 2rem;
			position: relative;
		}
		.title{
			font-size: 42px;
			margin-bottom: 2rem;
			font-weight: 100;
		}
		.title-desc{
			font-size: 20px;
			font-weight: 100;
		}
		.btn-get{
			margin-top: 3rem;
			padding: .7rem 1rem;
			background-color: #5840ff;
			display: inline-block;
			border-radius: 4px;
			color: white;
		}
		.controls{
			position: absolute;
    		bottom: 1rem;
		}
		.btn-get:hover{
			opacity: .8;
		}
		.box-desc{
			text-align: center;
		}
	</style>
</head>
<body>
	<div class="bg-top"></div>
	<div class="bg">
		<div id="box">
			<div class="box-desc">
				<h2 class="title">_(( $title ))</h2>
				<div class="title-desc">_(( $welcomeText ))</div>
				<a class="btn-get" href="https://github.com/Daevsoft/dsframework/">_(( $buttonText ))</a>
			</div>
		</div>
	</div>
</body>
</html>
