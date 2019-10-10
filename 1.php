<?php

function findSimple($a, $b)
{
    $result = [];

    for($a; $a < $b; $a++) {
        if($a == 0 || $a == 1 || $a == 2) {
            $result[] = $a;
            continue;
        }

        $isSimple = true;
        for($s = 2; $s < $a; $s++) {
            if ($a % $s == 0) {
                $isSimple = false;
            }
        }
        if ($isSimple) {
            $result[] = $a;
        }
    }
    return $result;
}

function createTrapeze($a)
{
    $result = [];
    $chunkedArray = (array_chunk($a, 3));
    foreach ($chunkedArray as $key => $value) {
        $result[] = ['a' => $value[0], 'b' => $value[1], 'c' => $value[2]];
    }

    return $result;
}

function squareTrapeze($a)
{
    for ($i = 0; $i < count($a); $i++) {
        $current = &$a[$i];
        $current['s'] = (($current['a'] + $current['b']) / 2) * $current['c'];
    }

    return $a;
}

function getSizeForLimit($a, $b)
{
    echo "<pre>";
    print_r($a);
    echo "</pre>";
    $filteredByArea = array_filter($a, function ($trapeze) use ($b) {
        return $trapeze['s'] <= $b;
    });

    $maxArea = 0;
    $maxAreaId = 0;

    foreach ($filteredByArea as $key => $value) {
        if ($value['s'] > $maxArea) {
            $maxArea = $value['s'];
            $maxAreaId = $key;
        }
    }

    return $filteredByArea[$maxAreaId];
}

function getMin($a)
{
    $min = 0;
    $isInit = false;

    foreach ($a as $key => $value) {
        if (!$isInit) {
            $min = $value;
            $isInit = true;
        }
        if ($min > $value) {
            $min = $value;
        }
    }

    return $min;
}

function printTrapeze($a)
{
    $tableHead = "<tr>
        <td>Основание a</td>
        <td>Основание b</td>
        <td>Высота</td>
        <td>Площадь</td>
    </tr>";

    $tableBody = "";
    foreach ($a as $trapeze) {
        $tableBody .= $trapeze['s'] % 2 === 0 ? "<tr>" : "<tr style='background: #6DB4FF'>";
        $tableBody .= "
                    <td style='padding: 5px 10px; border: 1px solid #686868 '>{$trapeze['a']}</td>
                    <td style='padding: 5px 10px; border: 1px solid #686868'>{$trapeze['b']}</td>
                    <td style='padding: 5px 10px; border: 1px solid #686868'>{$trapeze['c']}</td>
                    <td style='padding: 5px 10px; border: 1px solid #686868'>{$trapeze['s']}</td>
                </tr>
            ";
    }

    $html = "<table style='border-collapse: collapse; margin: auto; min-width: 60vw;'>{$tableHead}{$tableBody}</table>";
    echo $html;
}

abstract class BaseMath
{
    protected $value;

    protected function exp1($a, $b, $c)
    {
        return $a * pow($b, $c);
    }

    protected function exp2($a, $b, $c)
    {
        return pow(($a / $c), $b);
    }

    public function getValue()
    {
        return $this->value;
    }
}

class F1 extends BaseMath
{

    public function __construct($a, $b, $c)
    {
        /*
         * В письме были указаны формулы для
         * exp1: a*(b^c)
         * exp2: (a/b)^c
         * я так понимаю эти методы были необходимы для дальнейшего их использования,
         * но следует заметить, что exp2 не получается применить в итоговой формуле
         * f=(a*(b^c)+(((a/c)^b)%3)^min(a,b,c)
         * возможно, в формуле либо в exp2 опечатка и что-то из них должно выглядеть иначе
         * метод exp2 я приведу к виду (a/c)^b заранее предупредив :)
         * */

        $this->value =
            ($this->exp1($a, $b, $c))
            +
            pow($this->exp2($a, $b, $c) % 3, getMin([$a, $b, $c]));
        
    }

}
