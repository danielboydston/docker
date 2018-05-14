<?php
require_once 'API.class.php';
require_once 'models/USER.class.php';
class STOCKR_API extends API {
    protected $user;

    public function __construct($request, $origin) {
        parent::__construct($request);
        /*
        // Abtracted out for example
        $api_key = new Models\APIKey();
        $user = new Models\User();

        if(!array_key_exists('apiKey', $this->request)) {
            throw new Exception('No API Key Provided');
        } else if(!$api_key->verifyKey($this->request['apiKey'], $origin)) {
            throw new Exception('Invalid API Key');
        } else if(array_key_exists('token', $this->request) && !$user->get('token', $this->request['token'])) {
            throw new Exception('Invalid User Token');
        }

        $this->user = $user;
        */
    }

    protected function user($args) {
        switch ($this->method) {
            case 'GET':
                $user = Models\USER::get_user($args[0]);
                return "GET user - " . $user->firstname . " " . $user->lastname;
                break;
            case 'POST':
                return "New user";
                break;
            case 'PUT':
                return "Save user";
                break;
            case 'DELETE':
                return "Delete user";
                break;
            default:
                return "Unknown method";
        }
    }
}


?>
