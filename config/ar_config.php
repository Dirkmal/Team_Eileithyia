<?php
    /**
     * Initializes the ActiveRecord library with the database information and models location
     */
    require_once 'php-activerecord/ActiveRecord.php';
    
    ActiveRecord\Config::initialize(function($cfg) {
        $user = "root";
        $password = "";
        $server = "localhost";
        $database = "mini_classroom";

        $cfg->set_model_directory('../../models');
        // models directory is relative to the path of the file this script is being included in
        
        $cfg->set_connections(array(
            'development' => "mysql://$user:$password@$server/$database")
        );
    });
?>

