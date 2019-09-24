<?php
    use ActiveRecord\ActiveRecordException;
    header("Access-Control-Allow-Origin: *"); 
    header("Access-Control-Max-Age: 1000");
    header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Authorization, Accept, Client-Security-Token, Accept-Encoding");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Credentials: true");

    require_once '../../config/ar_config.php';
    require_once '../../config/connect.php';
    require_once '../../config/auth.php';

    $control = new Connect;
    $auth_obj = new Auth;

    $control->respondToPreflightReqs();

    $user = file_get_contents("php://input");

    if (isset($user) && !empty($user)) {
        $content = json_decode($user);

        if (!$control->requiredFieldsNotEmpty(
            array(
                $content->data->email,
                $content->data->password
            )
        )) {
            $control->jsonResponse("You are missing required fields");
        }

        $email = trim($content->data->email);
        $password = trim($content->data->password);   
        
        try {
            $user_exists = User::find_by_email($email);

            if ($user_exists) {
                if (password_verify($password, $user_exists->pash)) {
                    $token =  $control->generateToken();
                    
                    $user_exists->logged_in = 1;  
                    
                    try {
                        $user_exists->save();
                        $user_exists->pash = null;
                        $control->jsonResponse("Sign in successful", true, $token);
                    } catch (ActiveRecordException $e) {
                        $control->jsonResponse("An error occurred");
                    }
                } else {
                    $control->jsonResponse("Invalid email or password");
                }
            } else {
                $control->jsonResponse("Invalid email or password");
            }
        } catch (ActiveRecordException $e) {
            $control->jsonResponse("An error occurred");
        }
    } else {
        $control->jsonResponse("No credentials supplied");
    }        
?>