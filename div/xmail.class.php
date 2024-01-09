<?php

function xmail($to, $subj, $text, $head){
    $file="/var/log/apache-send.log";
    error_log(date("Y-m-d H:i:s")." "."$to # $subj # $text # $head"."\n", 3, $file);
    mail($to, $subj, $text, $head);
}


?>