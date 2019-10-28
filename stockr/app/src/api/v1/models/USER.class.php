<?php
namespace Models;
require_once "CONFIG.class.php";
class USER {
    private $config;
    public $id, $email, $firstname, $lastname, $timezoneid, $active;

    public function __construct($user_id) {
        $config = new CONFIG();
        if($user_id > 0) {
            // Retrieve the user from the database
            $sql = "SELECT id, email, firstName, lastName, timezoneid, active
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
            $stmt->bind_result($id, $email, $firstname, $lastname, $timezoneid, $active);
            $stmt->fetch();
            $this->id = $id;
            $this->email = $email;
            $this->firstname = $firstname;
            $this->lastname = $lastname;
            $this->timezoneid = $timezoneid;
            $this->active = $active;
            $stmt->close();
        } else {
            $id="";
            $email = "";
            $firstname = "";
            $lastname = "";
            $timezoneid = "";
            $active = "";
        }

    }

    public function to_json() {
        $user = array("id"=>$this->id, "email"=>$this->email, "firstname"=>$this->firstname,"lastname"=>$this->lastname, "timezoneid"=>$this->timezoneid, "active"=>$this->active);
        return json_encode($user);
    }

    public static function get_user($args) {
        return new USER($args[0]);
    }
}
?>
