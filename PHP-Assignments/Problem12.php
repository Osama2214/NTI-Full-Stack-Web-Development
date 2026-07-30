<!DOCTYPE html>
<html>
<head>
    <title>Discount Calculator</title>
</head>
<body>

<form method="post">

    Product Price:
    <input type="text" name="price"><br><br>

    Quantity:
    <input type="text" name="quantity"><br><br>

    <input type="submit" value="Calculate">

</form>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $price = $_POST["price"];
    $quantity = $_POST["quantity"];

    if (!is_numeric($price) || !is_numeric($quantity)) {

        echo "Please enter numbers only.";

    } elseif ($price < 0 || $quantity < 0) {

        echo "Negative numbers are not allowed.";

    } else {

        $total = $price * $quantity;

        if ($total < 1000) {
            $discount = $total * 0.10;
        } else {
            $discount = $total * 0.15;
        }

        $final = $total - $discount;

        echo "<br>Total Before Discount = $total";
        echo "<br>Discount = $discount";
        echo "<br>Total After Discount = $final";
    }
}

?>

</body>
</html>