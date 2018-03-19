<?php
    require("Config.php");

    class StockR {

        public static function getQuotes() {
            $return = array("result"=>"success","errors"=>array(), "data"=>array());          // Return object
            $config = new Config();     // Create the config object
            $symbols = array();         // Stock symbols to retrieve quotes for
            $remote_fields = array();   // Quote field map for remote datasource.  Combined with other field list creates map.
            $local_fields = array();    // Quote field map for local datasource.  Combined with other field list creates map.
            $fieldMap = array();        // Associative array where a reference to the known local field will return the remote field name.

            if(!$config->mysqli) {
                $return['result']=="error";
                array_push($return['errors'],"Unable to connect to database: ".$config->mysqli->connect_error);
            } else {
                // Populate the quote field map
                $sql = "SELECT remote, local
                        FROM quoteFieldMap";
                $map_result = $config->mysqli->query($sql);
                if($config->mysqli->error) {
                    $return['result']=="error";
                    array_push($return['errors'],"Unable to get the quote field map: ".$config->mysqli->error . " SQL: $sql");
                } else {
                    while($map_result && $row=$map_result->fetch_assoc()) {
                        array_push($remote_fields,$row['remote']);
                        array_push($local_fields, $row['local']);
                        $fieldMap[$row['local']]=$row['remote'];
                    }
                    if($map_result) {
                        $map_result->close();
                    }

                    //Get the list of quotes to retrieve
                    $sql = "SELECT DISTINCT w.symbol, w.latestSource, w.latestTime, w.high, w.highDate, w.low, w.lowDate
                            FROM watches w
                            WHERE active='yes'";
                    $symbol_result = $config->mysqli->query($sql);
                    if($config->mysqli->error) {
                        $return['result']=="error";
                        array_push($return['errors'],"Error retrieving symbols: ".$config->mysqli->error." SQL: $sql");
                    } else {
                        while($symbol_result && $row=$symbol_result->fetch_assoc()) {
                            array_push($symbols,$row);
                        }
                        if($symbol_result) {
                            $symbol_result->close();
                        }
                        // If we have symbols to quote, let's quote them
                        if(count($symbols)>0) {
                            // Call the webservice to get each symbol's information
                            foreach ($symbols as $sym) {
                                // URL of webservice
                                $quote_url = $config->get_option("quote_url");
                                $url = substr_replace($quote_url, $sym['symbol'], strpos($quote_url, "@symbol"), 7);
                                $quote = StockR::curl_call($url, "GET");
                                if($quote['result']=='success' && $quote['response']==200) {
                                    // Save the information to the database
                                    $sql_field_string = "";
                                    $sql_value_string = "";
                                    $src_fields = array_keys($quote['data']);
                                    foreach($src_fields as $field) {
                                        $map_index = array_search($field, $remote_fields);
                                        if($map_index!==false) {
                                            if(strlen($sql_field_string)>0) {
                                                $sql_field_string.=",";
                                            }
                                            $sql_field_string .= $local_fields[$map_index];

                                            if(strlen($sql_value_string)>0) {
                                                $sql_value_string.=",";
                                            }
                                            if(strlen($quote['data'][$field])==0 || $quote['data'][$field]=="null") {
                                                $sql_value_string .="NULL";
                                            } else {
                                                $sql_value_string .= "\"".$config->mysqli->escape_string($quote['data'][$field])."\"";
                                            }
                                        }
                                    }

                                    // Only record the quote if the previous latestSource and current latestSource do not equal "Close" and the last quote was over 20 hours ago
                                    if(!($sym['latestSource']=='Close' && $quote['data'][$fieldMap['latestSource']]=='Close' && ($sym['latestTime'] + 72000000) >= (time() * 1000))) {
                                        $sql_string = "INSERT INTO quotes ($sql_field_string) VALUES ($sql_value_string)";
                                        $config->mysqli->query($sql_string);
                                        if($config->mysqli->error) {
                                            $return['result']="error";
                                            array_push($return['errors'], "Unable to save quote: ".$config->mysqli->error." SQL: $sql_string");
                                        }
                                        // Record the latestSource value on the watch record
                                        $sql = "UPDATE watches SET latestSource='".$quote['data'][$fieldMap['latestSource']]."', latestTime=".$quote['data'][$fieldMap['latestUpdate']]."
                                                WHERE symbol='".$sym['symbol']."'";
                                        $config->mysqli->query($sql);
                                        if($config->mysqli->error) {
                                            $return['result']="error";
                                            array_push($return['errors'], "Unable to update watch latest source: ".$config->mysqli->error." SQL: $sql");
                                        }

                                        // Update watch high and low stats
                                        if((float)$quote['data'][$fieldMap['latestPrice']] > (float)$sym['high']) {
                                            // We have a new high price.  Update watch
                                            $sql = "UPDATE watches SET high=".$quote['data'][$fieldMap['latestPrice']].", highDate=".$quote['data'][$fieldMap['latestUpdate']]."
                                                    WHERE symbol='".$sym['symbol']."'";
                                            $config->mysqli->query($sql);
                                            if($config->mysqli->error) {
                                                $return['result']="error";
                                                array_push($return['errors'],"Unable to update watch high: ".$config->mysqli->error." SQL: $sql");
                                            }
                                        }
                                        if((float)$quote['data'][$fieldMap['latestPrice']] < (float)$sym['low'] || (float)$sym['low']==0) {
                                            // We have a new low price.  Update watch
                                            $sql = "UPDATE watches SET low=".$quote['data'][$fieldMap['latestPrice']].", lowDate=".$quote['data'][$fieldMap['latestUpdate']]."
                                                    WHERE symbol='".$sym['symbol']."'";
                                            $config->mysqli->query($sql);
                                            if($config->mysqli->error) {
                                                $return['result']="error";
                                                array_push($return['errors'],"Unable to update watch low: ".$config->mysqli->error." SQL: $sql");
                                            }
                                        }

                                        // Update the low and high for any triggers
                                        $sql = "UPDATE triggers SET low=".$quote['data'][$fieldMap['latestPrice']].", lowDate=".$quote['data'][$fieldMap['latestUpdate']]."
                                                WHERE watchID IN (SELECT id FROM watches WHERE symbol='".$sym['symbol']."') AND ".$quote['data'][$fieldMap['latestPrice']]."<low AND active = 'yes'";
                                        $config->mysqli->query($sql);
                                        if($config->mysqli->error) {
                                            $return['result']="error";
                                            array_push($return['errors'],"Unable to update trigger low: ".$config->mysqli->error." SQL: $sql");
                                        }

                                        $sql = "UPDATE triggers SET high=".$quote['data'][$fieldMap['latestPrice']].", highDate=".$quote['data'][$fieldMap['latestUpdate']]."
                                                WHERE watchID IN (SELECT id FROM watches WHERE symbol='".$sym['symbol']."') AND ".$quote['data'][$fieldMap['latestPrice']].">high AND active = 'yes'";
                                        $config->mysqli->query($sql);
                                        if($config->mysqli->error) {
                                            $return['result']="error";
                                            array_push($return['errors'],"Unable to update trigger high: ".$config->mysqli->error." SQL: $sql");
                                        }
                                    }


                                } else if($quote['result']=='success' && $quote['response']==404) {
                                    $return['result']="error";
                                    array_push($return['errors'], "Symbol not found: ".$sym['symbol']);
                                } else {
                                    $return['result']="error";
                                    array_push($return['errors'], "Error retrieving quote from $url: ".$quote['result'].", Call Response: ".$quote['response'].", Returned Data: ".$quote['raw_result'].", Error: ".print_r($quote['error'],true));
                                }
                            }
                        }
                    }
                }
            }

            return $return;
        }

        public static function processTriggers() {
            $return = array("result"=>"success","errors"=>array(), "data"=>array());          // Return object
            $config = new Config;     // Create the config object

            // Retrieve the new quotes with any associated watches, triggers, and calculations.
            // Include only those triggers whose quoteInterval has elapsed.
            $sql = "SELECT q.*, t.id as triggerID, t.watchID, t.calcID, t.threshold, t.prompt, t.customPrompt, t.triggered, t.acknowledged, t.lastEval, w.userID, w.costBasis, w.qty, w.high as watchHigh, w.low as watchLow, c.calculation,
                        t.low as triggerLow, t.high as triggerHigh, comp.words, comp.operator
                    FROM quotes q LEFT JOIN watches w ON q.symbol=w.symbol LEFT JOIN triggers t ON w.id = t.watchID LEFT JOIN calcs c ON c.id = t.calcID LEFT JOIN comparisons comp ON c.comparisonID=comp.id
                    WHERE q.status='new' AND (t.active='yes' OR t.active IS NULL) AND (w.active='yes' OR w.active IS NULL)";
            $trigger_result = $config->mysqli->query($sql);
            if($config->mysqli->error) {
                $return['result']="error";
                array_push($return['errors'],"Unable to get trigger information: ".$config->mysqli->error.", SQL: $sql");
            } else {
                while($trigger_result && $row=$trigger_result->fetch_assoc()) {
                    // Replaces keys in the calculation string (surrounded by []) with actual field values
                    $calc = $row['calculation'];
                    $calc_result = "";
                    $strpos = 0;
                    $key = array("start"=>-1,"key"=>"");
                    while ($strpos < strlen($calc)) {
                        $cur_char = substr($calc,$strpos,1);
                        if($cur_char=="[") {
                            // We located a new key.  Begin recording in the key array
                            $key['start']=$strpos;
                        } else if($cur_char=="]") {
                            // We located the end of a key
                            // Locate the field referenced by the key and add it to the calc_result string
                            $calc_result .= $row[$key['key']];
                            // Reset the key
                            $key = array("start"=>-1,"key"=>"");
                        } else if($key['start']>-1) {
                            // We are inside a key.  Record this character as part of the current key
                            $key['key'].=$cur_char;
                        } else {
                            // We are not inside a key.  Add this character to the calc_result
                            $calc_result.=$cur_char;
                        }
                        $strpos++;
                    }
                    if(strlen($calc_result)>0) {
                        // We have a calc_result.  Evaluate the calculation
                        // This method is dangerous if processing user input.
                        // We will sterilize it to remove any characters we don't expect.
                        // Keep digits, parenthesis, operators
                        $calc_result = preg_replace("/[^0-9\.\-\*\/\+\>\<\=\(\) ]/i","",$calc_result);
                        // convert '=' to '=='
                        //$calc_result = preg_replace("/[\=]/i","==",$calc_result);
                        $eval_str = "return ($calc_result);";
                        // Evaluate the calculation to a value.  This will be compared to the trigger value in the next instruction
                        $calculation = eval($eval_str);
                        // Build the final expression
                        $eval_str = "return (".$calculation . " " . $row['operator'] . " " . $row['threshold'] . ") ? 'true' : 'false';";
                        // Evaluate the full expression to true or false including the calculation operator and the trigger value
                        $result = eval($eval_str);

                        // Record the trigger as having been evaluated
                        $sql = "UPDATE triggers SET lastEval=". (time() * 1000) ."
                                WHERE id=".$row['triggerID'];
                        $config->mysqli->query($sql);
                        if($config->mysqli->error) {
                            $return['result']='error';
                            array_push($return['errors'],"Unable to update trigger: ".$config->mysqli->error." SQL: $sql");
                        }

                        // Check if the trigger has changed states. If so, update the trigger and create a notification
                        if(($result == 'true' && $row['triggered']=='no') || ($result == 'false' && $row['triggered']=='yes')) {
                            // This trigger is changing state
                            if($result=='true') {
                                $triggered='yes';
                            } else {
                                $triggered='no';
                            }
                            $sql = "UPDATE triggers SET triggered='$triggered', triggerDate=".(time() * 1000)."
                                    WHERE id=".$row['triggerID'];
                            $config->mysqli->query($sql);
                            if($config->mysqli->error) {
                                $return['result']='error';
                                array_push($return['errors'],"Error updating trigger: ".$config->mysqli->error." SQL: $sql");
                            }
                            // Create a notification for this trigger event
                            $sql = "INSERT INTO notifications (triggerID, quoteID, notificationDate, triggerThreshold, triggerValue, state, status)
                                    VALUES (".$row['triggerID'].",".$row['id'].",". (time() * 1000) .",".$row['threshold'].",$calculation, '$triggered','new')";
                            $config->mysqli->query($sql);
                            if($config->mysqli->error) {
                                $return['result']='error';
                                array_push($return['errors'],"Error creating notification: ".$config->mysqli->error." SQL: $sql");
                            }
                        }
                        else if($result != 'true' && $result != 'false'){
                            // There was an error evaluating the calculation (it didn't return true or false)
                            // Put the trigger into an error state
                            $sql = "UPDATE triggers SET error='yes', error_message='Error evaluating trigger calculation: $calc_result did not result in true or false'
                                    WHERE id=".$row['triggerID'];
                            $config->mysqli->query($sql);
                            if($config->mysqli->error) {
                                $return['result']='error';
                                array_push($return['errors'],"Error updating trigger error: ".$config->mysqli->error." SQL: $sql");
                            }
                        }
                        array_push($return['data'],array("db"=>$row,"calc_result"=>$calc_result, "calculation"=>$calculation,"eval_str"=>$eval_str,"calc_result"=>$result));
                    }
                    // Update the quote as having been processed
                    $sql = "UPDATE quotes SET status='processed'
                            WHERE id=".$row['id'];
                    $config->mysqli->query($sql);
                    if($config->mysqli->error) {
                        $return['result']='error';
                        array_push($return['errors'],"Unable to update quote status: ".$config->mysqli->error." SQL: $sql");
                    }
                }
            }
            return $return;
        }

        public static function sendNotifications() {
            $return = array("result"=>"success","errors"=>array(),"data"=>array());          // Return object
            $config = new Config();     // Create the config object


            // Get a list of new notifications and supporting information
            $sql = "SELECT n.id, n.triggerID, n.quoteID, CONVERT_TZ(FROM_UNIXTIME(n.notificationDate/1000),'UTC',tz.timezone) as notificationDate, n.triggerThreshold, n.triggerValue, n.state, n.status, w.symbol, t.prompt, t.customPrompt, q.latestPrice, CONVERT_TZ(FROM_UNIXTIME(q.latestUpdate / 1000),'UTC',tz.timezone) as latestUpdate, c.name as calcName, u.firstName, u.lastName, ue.email, ue.text, comp.words
                    FROM notifications n INNER JOIN quotes q ON n.quoteID=q.id INNER JOIN triggers t ON n.triggerID=t.id INNER JOIN watches w ON t.watchID=w.id INNER JOIN calcs c ON t.calcID=c.id INNER JOIN comparisons comp ON c.comparisonID=comp.id INNER JOIN users u ON w.userID=u.id INNER JOIN userEmails ue ON ue.userID=u.id INNER JOIN timezones tz ON u.timezoneID=tz.id
                    WHERE n.status='new' AND w.active='yes' AND t.active='yes' AND u.active='yes' AND ue.active='yes'";
            $sql_result = $config->mysqli->query($sql);
            if($config->mysqli->error) {
                $return['result']='error';
                array_push($return['errors'],"Unable to retrieve notifications: ".$config->mysqli->error." SQL: $sql");
            } else {
                while($sql_result && $row=$sql_result->fetch_assoc()) {
                    if($row['text']=='yes') {
                        // Compose a text message
                        $sub = "";
                        $msg = "Stock'R Alert";
                        if($row['state']=='no') {
                            $msg .= " CANCELED";
                        }
                        $msg.="!";
                        $msg .= "\nSymbol: ".$row['symbol'];
                        $msg .= "\nAction: ";
                        if($row['state']=='no') {
                            $msg .= "DO NOT ";
                        }
                        $msg .= ($row['prompt']=='custom') ? $row['customPrompt'] : $row['prompt'];
                        $msg .= "\nCalculation: ".$row['calcName']." (".$row['triggerValue'].") ".$row['words']." ".$row['triggerThreshold'];
                        $msg .= "\nPrice: ".$row['latestPrice'];
                        $msg .= "\nPrice Date: ".$row['latestUpdate'];
                        $msg .= "\nGenerated: ".$row['notificationDate'];

                        $e_result = json_decode($config->send_mail($row['email'],"Stock'R","",$msg,false),true);
                        if($e_result['result']=='error') {
                            $return['result']='error';
                            array_push($return['errors'],"Unable to send notification to ".$row['email'].": ".$e_result['message']);
                        } else {
                            array_push($return['data'],array("row"=>$row,"result"=>$e_result));
                            // Update the notification as having been sent
                            $sql = "UPDATE notifications SET status='sent', sentDate=" . (time() * 1000) . "
                                    WHERE id=".$row['id'];
                            $config->mysqli->query($sql);
                            if($config->mysqli->error) {
                                $return['result']='error';
                                array_push($return['errors'],"Unable to update notification: ".$config->mysqli->error." SQL: $sql");
                            }
                        }
                    } else {
                        // Compose an email
                        $sub = "Stock'R Alert: ".$row['symbol']." ";
                        if($row['state']=='no') {
                            $sub .= "- DO NOT ";
                        }
                        $sub .= ($row['prompt']=='custom') ? $row['customPrompt'] : $row['prompt'];
                        $msg = "<h3>Stock'R Alert";
                        if($row['state']=='no') {
                             $msg .= " - CANCELED";
                        }
                        $msg .= "</h3>";
                        $msg .= "\n<p>Symbol: ".$row['symbol'];
                        $msg .= "\n<br/>Action: ";
                        if($row['state']=='no') {
                            $msg .= "DO NOT ";
                        }
                        $msg .= ($row['prompt']=='custom') ? $row['customPrompt'] : $row['prompt'];
                        $msg .= "\n<br/>Calculation: ".$row['calcName']." (".$row['triggerValue'].") ".$row['words']." ".$row['triggerThreshold'];
                        $msg .= "\n<br/>Price: ".$row['latestPrice'];
                        $msg .= "\n<br/>Price Date: ".$row['latestUpdate'];
                        $msg .= "\n<br/>Generated: ".$row['notificationDate']."</p>";

                        $e_result = json_decode($config->send_mail($row['email'],"Stock'R",$sub,$msg,true),true);

                        if($e_result['result']=='error') {
                            $return['result']='error';
                            array_push($return['errors'],"Unable to send notification to ".$row['email'].": ".$e_result['message']);
                        } else {
                            array_push($return['data'],array("row"=>$row,"result"=>$e_result));
                            // Update the notification as having been sent
                            $sql = "UPDATE notifications SET status='sent', sentDate=" . (time() * 1000) . "
                                    WHERE id=".$row['id'];
                            $config->mysqli->query($sql);
                            if($config->mysqli->error) {
                                $return['result']='error';
                                array_push($return['errors'],"Unable to update notification: ".$config->mysqli->error." SQL: $sql");
                            }
                        }
                    }

                }
            }
            return $return;
        }

        private static function curl_call($url, $method, $return_type="json", $return_array=true, $body="") {
            // error status 0 if no error, 1 if error
            $error = array("code"=>"","message"=>"","req"=>"");

            $response = 0;
            $result = 'success';

            $ch = curl_init();
            //curl_setopt($ch, CURLOPT_USERPWD, "$mm_username:$mm_password");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if($method == "GET") {
                curl_setopt($ch, CURLOPT_HTTPGET,1);
            } else if ($method == "POST") {
                curl_setopt($ch, CURLOPT_POST,1);
            } else if ($method == "PUT") {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            } else if ($method == "DELETE") {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            } else {
                $result = 'error';
                $error['code'] = "req.method.invalid";
                $error['message'] = "Method not allowed: " . $method;
                return array("response"=>405, "data"=>"", "error"=>$error);
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            if(strlen($body)>0) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            if($return_type=="json") {
                $raw_result=curl_exec($ch);
                $data=json_decode($raw_result, $return_array);
            }
            $response=curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if($response!=200) {
                $result = 'error';
                $error['message'] = $data['error']['message']['_value'];
                $error['code'] = $data['error']['_code'];
            }
            $curl_error = curl_error($ch);
            if(strlen($curl_error)>0) {
                $result = 'error';
                $error['message'] = "Error with API call: " . $curl_error;
                $error['code']="req.fault";
                $response=500;
            }
            $error['req'] = curl_getinfo($ch);
            curl_close($ch);
            return array("result"=>$result, "response"=>$response, "data"=>$data, "error"=>$error, "raw_result"=>$raw_result);
        }

    }
?>
