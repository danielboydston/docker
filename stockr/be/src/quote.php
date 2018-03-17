<?php
    require_once "StockR.php";
    require_once "Config.php";

    $config = new Config();     // Create the config object
    // Use UTC for all date and time functions
    date_default_timezone_set("UTC");

    while(1) {
        $interval = $config->get_option("quote_interval");
        if(is_numeric($interval)==false || $interval <= 0) {
            $interval=300;
        }
        print("getting quotes...\n");
        $result = StockR::getQuotes();
        if($result['result']!='success') {
            print_r($result);
        }
        print("processing triggers...\n");
        $result = StockR::processTriggers();
        if($result['result']!='success') {
            print_r($result);
        }
        print("sending notifications...\n");
        $result = StockR::sendNotifications();
        if($result['result']!='success') {
            print_r($result);
        }

        print("sleeping $interval seconds...");
        sleep ($interval);
    }
?>
