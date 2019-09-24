<?php
	require_once 'controller.php';

	$obj = new Ctrl;
	$_SESSION['log_att'] = 0;
	if(isset($_POST['login'])){
		$username = $_POST['username'];
		$pass = $_POST['password'];

		$obj->login($username, $pass);
	}

	if(isset($_POST['logout'])){
		$obj->logout();
	}

	if(isset($_POST['change'])){
        $obj->setTable($obj->db_a_table);
        $obj->updatePart($_POST['user_id'], "password", md5($_POST['new_pass']));
        $obj->logout();        
        $obj->alert($obj->messages['PSWDCHNG']);
    }
?>

<!DOCTYPE HTML>
<html lang="en" ng-app="mamife">
	<head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/container.min.2.css">
        <title>Login</title>

        <style type="text/css">
        	.form-control, input {
			    border-radius: 0;
			    padding: 15px 10px;
			    height: auto;
			}

			.login_heading{
				text-align: center;
    			margin-bottom: 50px;
    		}

    		.login_heading h2{
    			font-size: 48px;
    			font-weight: 700;
			}

			.btn{
				border-radius: 0;
    			padding: 15px 25px;	
			}
        </style>
	</head>

    <body ng-controller="control" class="custom_blue">
        <div align="center">
        	<h2>OBAFEMI AWOLOWO UNIVERSITY TEACHING HOSPITALS COMPLEX<br> Department of Morbid Anatomy <br>&amp;<br> FORENSIC MEDICINE</h2>

            <h2>
                <b>{{timer}}</b>
                <br>
                <b><?php echo $obj->getDate(); ?></b>
            </h2>
        </div>

        <div class="container">
            <div class="row">
				<div class="col-md-4 col-md-offset-4">
					<div class="contact-form">
						<form action="login.php" class="form" method="post">  
							<div class="col-md-12">
								<input type="text" class="form-control" placeholder="Username" name="username"required autofocus>
								<p class="help-block"></p>
							</div> 	

							<br>
							
							<div class="col-md-12">
								<input type="password" class="form-control" placeholder="Password" name="password" required>
								<p class="help-block"></p>
							</div> 
							 
							<button type="submit" class="btn btn-hard btn-default btn-lg col-sm-12 col-xs-12 col-md-12 pull-right" name="login">
								<b>Login</b>
							</button>
						</form>
					</div>
		        </div>
			</div>
		</div>
    </body>

    <script type="text/javascript" src="js/angular.min.js"></script>
    <script type="text/javascript" src="js/controls.js"></script>
</html>
