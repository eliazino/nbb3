<?php

class EmailApp{
    public function sendMail($to,$message){
        $subject = "BitX Transaction Notice";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: <webmaster@example.com>' . "\r\n";
        $headers .= 'Cc: myboss@example.com' . "\r\n";
        mail($to,$subject,$message,$headers);
        }
}
?>