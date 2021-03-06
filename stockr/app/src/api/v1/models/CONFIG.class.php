<?php
namespace Models;
require dirname(__DIR__) . "/config/stockr.cfg";
include dirname(__DIR__) . "/vendor/PHPMailer/PHPMailerAutoload.php";
class CONFIG {
    public $mysqli, $mail;
    private $options = array();

    public function __construct() {
        global $mysql_server, $mysql_username, $mysql_password, $mysql_database, $fqdn;
        $this->fqdn = $fqdn;
        $this->this_server_name=$this_server_name;
        // Connect to the database
        $this->mysqli = new \MySQLi($mysql_server, $mysql_username, $mysql_password, $mysql_database);
        // If connection to the database succeeded, pull the config from the database
        if($this->mysqli) {
            $sql = "SELECT id, item, value FROM config";
            $sql_result = $this->mysqli->query($sql);
            while($sql_result && $row=$sql_result->fetch_assoc()) {
                $this->option[$row["item"]] = $row["value"];
            }
            // Add the server name and fqdn to the option array
            $this->option["fqdn"]=$fqdn;
            $this->option["this_server_name"]=$this_server_name;
            if($sql_result) {
                $sql_result->close();
            }
        }
        // Create the mail object
        $this->mail = new \PHPMailer;

        $this->mail->isSMTP();                                      // Set mailer to use SMTP
        $this->mail->Host = $this->option["email_host"];            // Specify main and backup SMTP servers
        $this->mail->SMTPAuth = true;                               // Enable SMTP authentication
        $this->mail->Username = $this->option['email_username'];    // SMTP username
        $this->mail->Password = $this->option['email_password'];    // SMTP password
        $this->mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $this->mail->Port = $this->option['email_port'];            // TCP port to connect to
        $this->mail->SMTPDebug = $this->option['email_smtp_debug']; // Enable verbose debug output
    }

    public function __destruct() {
        $this->mysqli->close();
    }

    // Return the value of the specified config option
    public function get_option($op_name) {
        return $this->option[$op_name];
    }

    // Helper function for sending an email
    public function send_mail($to, $from_title, $subject, $body, $html=false) {
        $result=array("result"=>"success","message"=>"");

        $this->mail->setFrom($this->option['email_username'], $from_title);
        $this->mail->addAddress($to);     // Add a recipient
        $this->mail->isHTML($html);

        $this->mail->Subject = $subject;
        $this->mail->Body    = $body;
        if(!$this->mail->send()) {
            $result['result']="error";
            $result['message'] = "Message could not be sent: " . $this->mail->ErrorInfo;
        }
        return json_encode($result);
    }
}

?>
