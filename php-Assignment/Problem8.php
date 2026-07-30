<?php

function RouteRandomPass($length)
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

    $password = "";

    for ($i = 0; $i < $length; $i++) {

        $password .= $chars[rand(0, strlen($chars)-1)];
    }

    return $password;
}

echo RouteRandomPass(8);

?>