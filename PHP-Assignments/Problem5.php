<?php

function RouteBubble($arr)
{
    $size = count($arr);

    for ($i = 0; $i < $size - 1; $i++) {

        for ($j = 0; $j < $size - $i - 1; $j++) {

            if ($arr[$j] > $arr[$j + 1]) {

                $temp = $arr[$j];
                $arr[$j] = $arr[$j + 1];
                $arr[$j + 1] = $temp;
            }
        }
    }

    return $arr;
}

$numbers = [8,4,2,9,1,7];

$result = RouteBubble($numbers);

foreach ($result as $num) {
    echo $num . " ";
}

?>