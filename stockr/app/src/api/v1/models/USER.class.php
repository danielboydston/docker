<?php
namespace Models;
require_once "CONFIG.class.php";
class USER {
    private $config;
    public $email, $firstname, $lastname, $timezoneID, $active;

    public function __construct($user_id) {
        $config = new CONFIG();
        if($user_id > 0) {
            // Retrieve the user from the database
            $sql = "SELECT id, email, firstName, lastName, timezoneID, active
                    FROM users
                    WHERE id=?";
            if(!($stmt = $config->mysqli->prepare($sql))) {
                throw new Exception("Unable to retrieve user.  Prepare failed: " . $config->mysqli->error);
            }
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            if(!$success) {
                throw new Exception("Unable to retrieve user.  Execute failed: " . $config->mysqli->error);
            }
            $stmt->bind_result($id, $email, $firstname, $lastname, $timezoneID, $active);
            $stmt->fetch();
            $this->email = $email;
            $this->firstname = $firstname;
            $this->lastname = $lastname;
            $this->timezoneID = $timezoneID;
            $this->active = $active;
            $stmt->close();
        } else {
            $email = "";
            $firstname = "";
            $lastname = "";
            $timezoneID = "";
            $active = "";
        }

    }

    public static function get_user($args) {
        return new USER($args[0]);
    }
}
?>
