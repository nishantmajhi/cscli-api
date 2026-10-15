<?php
    $num = (int)$_GET["n"];

    if($num < 0 ) {
        echo "Factorial of negative number can't be calculated!";
        exit;
    }
    else {
        $fact = factorial($num);
        echo "Factorial of " . $num . " = " . $fact;
    }

    function factorial($n) {
        $product = 1;
        for($i = $n; $i > 1; $i--) {
            $product *= $i;
        }

        return $product;
    }
?>
