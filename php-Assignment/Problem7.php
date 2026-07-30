<?php

$films = array("avatar","Prestige","avatar","Prestige");
$keyword = "avatar";

$count = 0;

foreach ($films as $film) {

    if ($film == $keyword) {
        $count++;
    }
}

echo $count;

?>