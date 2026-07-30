<?php

$tests = array(1,"tariq",1.5,true,7,'s',false);

echo "<h3>Using For</h3>";

for ($i = 0; $i < count($tests); $i++) {

    if (is_bool($tests[$i])) {

        echo ($tests[$i]) ? "Yes" : "No";
    } else {

        echo $tests[$i];
    }

    echo "<br>";
}

echo "<h3>Using While</h3>";

$i = 0;

while ($i < count($tests)) {

    if (is_bool($tests[$i])) {

        echo ($tests[$i]) ? "Yes" : "No";
    } else {

        echo $tests[$i];
    }

    echo "<br>";

    $i++;
}

?>