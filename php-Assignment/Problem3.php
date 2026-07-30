<?php

function arraySum($numbers)
{
    $sum = 0;

    foreach ($numbers as $number) {
        $sum += $number;
    }

    return $sum;
}

$arr = [5, 10, 15, 20];

echo arraySum($arr);

?>