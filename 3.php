<?php

namespace Test3;

class NewBase
{
    static private $count = 0;
    static private $arSetName = [];
    protected $name;
    protected $value;

    /**
     * @param int $name
     */
    function __construct(int $name = null)
    {
        if ($name == null) {
            while (array_search(self::$count, self::$arSetName) !== false) {
                ++self::$count;
            }
            $name = self::$count;
        } elseif (array_search($name, self::$arSetName) !== false) {
            $counter = 1;
            while (array_search($name + $counter, self::$arSetName) !== false) {
                $counter += 1;
            }
            $name = $name + $counter;
        }
        $this->name = $name;
        self::$arSetName[] = $this->name;
    }

    /**
     * @param string $value
     * @return NewBase
     */
    static public function load(string $value): NewBase
    {
        $arValue = explode(':', $value);
        $tmpObj = new NewBase($arValue[1]);
        $tmpObj->setValue(unserialize(substr($value, strlen($arValue[0]) + 1 + strlen($arValue[1]) + 1, (int)$arValue[1])));
        return $tmpObj;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getName(): int
    {
        return $this->name;
    }

    // Назначение этого метода - выбрать поля объекта, которые мы хотим сериализовать, но так как мы сами формируем что и как должно сериализоваться, то, в нашем случае, этот метод ничего не делает
//    public function __sleep()
//    {
//        return ['value'];
//    }

    /**
     * @return string
     */
    public function getSave(): string
    {
        $value = serialize($this->value);
        return $this->name . ':' . $this->getSize() . ':' . $value;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        $size = strlen(serialize($this->value));
        return $size;
    }
}

class NewView extends NewBase
{
    private $type;
    private $size = 0;
    private $property;

    /**
     * @param string $value
     * @return NewBase
     */
    static public function load(string $value): NewBase
    {
        $arValue = explode(':', $value);
        $tmpObj = new NewView($arValue[0]);
        $tmpObj->setValue($value, strlen($arValue[0]) + 1 + strlen($arValue[1]) + 1, (int)$arValue[1]);
        $tmpObj->setProperty($arValue[count($arValue) - 1]);
        return $tmpObj;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        parent::setValue($value);
        $this->setType();
        $this->setSize();
    }

    private function setType()
    {
        $this->type = \Test3\gettype($this->value);
    }

    private function setSize()
    {
        if ($this->value instanceof NewView) {
            $this->size = (int)parent::getSize() + 1 + strlen($this->property);
        } elseif ($this->type == 'test') {
            $this->size = parent::getSize();
        } else {
            $this->size = strlen($this->value);
        }
    }

    public function setProperty($value)
    {
        $this->property = $value;
    }

//    /**
//     * @return array
//     */
//    public function __sleep()
//    {
//        return ['property'];
//    }

    public function getInfo()
    {
        try {
            echo "
                ============= <br>
                | name: $this->name <br>
                | property: $this->property <br>
                | type: $this->type <br>
                ============= <br>
            ";
        } catch (\Exception $exc) {
            echo 'Error: ' . $exc->getMessage();
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getName(): int
    {
        if (empty($this->name)) {
            throw new \Exception('The object doesn\'t have name');
        }
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getSave(): string
    {
        return parent::getSave() . ':' . $this->property;
    }
}

function gettype($value): string
{
    if (is_object($value)) {
        $type = get_class($value);
        do {
            if ($type === "Test3\NewBase") {
                return 'test';
            }
        } while ($type = get_parent_class($type));
    }
    return \gettype($value);
}


$obj = new NewBase('12345');
$obj->setValue('text');

$obj2 = new \Test3\NewView('09876');
$obj2->setValue($obj);
$obj2->setProperty('field');
$obj2->getInfo();

$save = $obj2->getSave();
$obj3 = newView::load($save);
var_dump($obj2->getSave() == $obj3->getSave());

