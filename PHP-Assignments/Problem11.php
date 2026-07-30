<?php

$arr1 = array('a','b','c','d');
$arr2 = array('c','d','e','f');

foreach ($arr1 as $value1) {

    foreach ($arr2 as $value2) {

        if ($value1 == $value2) {

            echo $value1 . " ";
        }
    }
}

?>