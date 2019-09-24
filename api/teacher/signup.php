<?php
    use ActiveRecord\ActiveRecordException;

    require_once '../../config/ar_config.php';
    require_once '../../config/controller.php';

    $control = new Controller;

    $teacher = file_get_contents("php://input");

    if (isset($teacher) && !empty($teacher)) {
        $content = json_decode($teacher);

        if (!$control->requiredFieldsNotEmpty(
            array(
                $content->data->fullname,
                $content->data->email,
                $content->data->password
            )
        )) {
            $control->jsonResponse("You are missing required fields");
        }
        
        $fullname = ucwords(trim($content->data->fullname)); 
        $email = trim($content->data->email);
        $password = trim($content->data->password);
        $pash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $user_exists = Teacher::exists(array('conditions' => array('email = ?', $email)));
    
            if ($user_exists) {
                $control->jsonResponse('Email already in use');
            } else {
                try {
                    $new_user = Teacher::create(array(
                        'email' => $email,
                        'fullname' => $fullname,
                        'pash' => $pash
                    ));
                    
                    if ($new_user) {  
                        $new_user->pash = null;
                        $control->jsonResponse('Teacher account registered successfully', true, $new_user->attributes());
                    } else {
                        $control->jsonResponse('Teacher account could not be registered');
                    }
                } catch (ActiveRecordException $e) {
                    $control->jsonResponse("An error occurred");
                }
            }            
        }  catch (ActiveRecordException $are) {
            $control->jsonResponse("An error occurred");
        }
    } else {
        $control->jsonResponse('No teacher details provided');
    }
?>