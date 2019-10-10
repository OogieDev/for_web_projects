<?php

function convertString($a, $b) {
    if (substr_count($a, $b < 2))
        return $a;

    $result = "";
    $strParts = explode($b, $a);

    for ($i = 0; $i < count($strParts) - 1; $i++) {
        if ($i == 1) {
            $result .= $strParts[$i] . strrev($b);
            continue;
        }
        $result .= $strParts[$i] . $b;
    }

    return $result;
}
