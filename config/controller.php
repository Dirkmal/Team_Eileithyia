<?php
    class Controller {
        public $conn_obj = "";
        private $SERVER = "localhost";
        private $DATABASE = "mini_classroom";
        private $USERNAME = "root";
        private $PASSWORD = "";

        function __construct() {
            try {
                $this->conn_obj = new mysqli($this->SERVER, $this->USERNAME, $this->PASSWORD, $this->DATABASE);

                if ($this->conn_obj->connect_error) {
                    die("Could not connect to database, ". $this->conn_obj->connect_error);
                }
            } catch (Exception $e) {
                die("Database connection failed! $e");
            }
        }
        
        /**
         * Creates a json encoded response with a response message, response status and response data/payload
         * kills the session with die() after echoing the response 
         * so that nothing after the function call is executed
         * @param string $message the response message
         * @param boolean $status the status of the operation, false by default
         * @param any $payload any optional data to be returned in the response
         */
        public function jsonResponse($message = 'No message returned', $status = false, $payload = null) {
            if ($message === null) $message = 'No message';
            if ($status === null) $status = false;
            
            echo json_encode(
                array(
                    'status' => $status, 
                    'message' => $message,
                    'payload' => $payload
                )
            );
            die();
        }
        
        /**
         * Check if an array of fields are set and are not empty
         * @param array $arr_fields array of fields that should exist and not be empty.
         * @return boolean TRUE if all the fields pass, FALSE if any field fails.
         * Does not specify which of the field(s) is empty or failed the test
         */
        function requiredFieldsNotEmpty($arr_fields) {
            foreach($arr_fields as $field) {
                if (empty($field)) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Generates a random 32 character alphanumeric token
         * @return string token
         */
        function generateToken() {
            $token = openssl_random_pseudo_bytes(4);
            $token = bin2hex($token);
            return $token;
        }
    }
?>