<?php

namespace Test3;

/*
   Как я понял, что здесь происходит:
    имеется 2 класса. Идет работа с сериализацией.
    Класс NewBase - родительский. Общие поля - name, value.
    Имеется статическая переменная $arSetName в которой хранятся уникальные имена объектов данного класса.
    Имя передается в конструктор и если такое имя уже существует, мы делаем его уникальным добавляя число в конец строки.
    Далее, после того как имя стало уникальным, присваиваем его объекту и добавляем в статический массив.
    __sleep - не зря тут имеется этот метод. Так как он срабатывает при попытке сериализовать объект класса, то было принято
    сериализовать весь объект, а не отдельное поле value, как было изначально.

    Все поля классов были перенесены вверх, так более понятно, какие поля содержит класс.

    Не сильно понял что должен возвращать метод getSize. В моей реализации этот метод возаращает длинну строки, которая
    получается в результате сериализации объекта.

    В методах getName, getValue, getType и тд. я убрал все лишние символы и привел к виду return $value;, а не return 'abcd*' . $value;

    В целом в итоге имеем классы, которые имеют методы get, set для своих полей. Умеют сериализовать свой объект, с необходимыми полями,
    а так-же десериализовать данные.

*/

class NewBase
{
    // static private $count = 0; // Не смог сообразить для чего же нужно это поле, в конструкторе создается локальный счетчик
    static private $arSetName = [];
    // Поля класса переносим вверх, так понятнее.
    protected $name; // Это поле нам понадобится в классах наследниках, по этому модификатор доступа установим в protected
    protected $value;
    protected $size = 0; // Добавим поле size, чтобы при сериализации объекта это поле так-же записывалось

    /**
     * @param string $name
     */
    function __construct(string $name = "")
    {
        /**
         * Как я понял, в конструкторе должно присваиваться имя текущему объекту
         * если данное имя уже есть в массиве имен объектов, то это имя мы делаем уникальным путем добавления
         * чисел в конец строки, тоесть name1, name2, name3, name4 и тд...
         */
        if (array_search($name, self::$arSetName) !== false) {
            $counter = 1; // вместо статического count создадим локальную переменную, которая будет заниматься своим делом
            while (array_search($name . $counter, self::$arSetName)) {
                $counter++;
            }
            $name = $name . $counter;
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
        try {
            $obj = unserialize($value);
            if ($obj instanceof NewBase)
                return $obj;
            throw new \Exception('unserialize error');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return null;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
//        return '*' . $this->name . '*'; // Тут звездочки не нужны. Нам потребуется чистое имя.
        return $this->name;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->getSize(); // После получения значения класса, узнаем какая длинна этого сериализованного значения
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
//        $size = strlen(serialize($this->value)); // Тут правим следующим образом
//        return strlen($size) + $size;
        return $this->size;
    }

    /**
     * @return void
     */
    public function setSize()
    {
        $this->size = strlen(serialize($this)); // Тут нужно получить длинну сериализованной строки? Запишем ее в созданное нами поле класса
    }

    public function __sleep()
    {
        return ['value', 'name', 'size']; // При сериализации экземпляра данного класса нам нужно имя, значение и размер
    }

    /**
     * @return string
     */
    public function getSave(): string
    {
        $value = serialize($this);
        return $value;
    }
}

class newView extends NewBase
{
    private $type = null;
    private $property = null;
// Данный метод реализован в родительском классе
//    /**
//     * @return newView
//     */
//    static public function load(string $value): newBase
//    {
//        $arValue = explode(':', $value);
//        return (new newBase($arValue[0]))
//            ->setValue(unserialize(substr($value, strlen($arValue[0]) + 1
//                + strlen($arValue[1]) + 1), $arValue[1]))
//            ->setProperty(unserialize(substr($value, strlen($arValue[0]) + 1
//                + strlen($arValue[1]) + 1 + $arValue[1])));
//    }

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
        $this->type = custom_gettype($this->value);
    }

// В родительском классе реализация этого метода возвращает длинну сериализированной строки. Не вижу смысла переопределять данный метод.
//    private function setSize()
//    {
//        if ($this->value instanceof newBase) {
//            $this->size = $this->value->getSize() + strlen($this->property);
//        } elseif ($this->type == 'test') {
//            $this->size = parent::getSize();
//        } else {
//            $this->size = strlen($this->value);
//        }
//    }

    /**
     * @param $value
     */
    public function setProperty($value)
    {
        $this->property = $value;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['name', 'value', 'size', 'property', 'type'];
    }

    /**
     * @return void
     * */
    public function getInfo()
    {
        try {
            echo "============<br>
                | name: $this->name <br>
                | type: $this->type <br>
                | property: $this->property <br>
                ============<br>
            ";

        } catch (\Exception $exc) {
            echo 'Error: ' . $exc->getMessage();
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getName(): string // Имя у нас строка, так что возвращаемый тип соответсвенно строка
    {
        if (empty($this->name)) {
            throw new \Exception('The object doesn\'t have name');
        }
        return $this->name; // Возвращаем только имя, не думаю, что какое либо "украшение" строки приветствуется в get методах
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type; // Ситуация аналогична ситуации метода выше
    }
// Переопределение метода тут не нужно, так как в родительском классе есть соответствующий метод, который сериализует текущий объект
// а дополнительные поля, которые есть у текущего класса-наследника мы перечислили в __sleep
//    /**
//     * @return string
//     */
//    public function getSave(): string
//    {
//        if ($this->type == 'test') {
//            $this->value = $this->value->getSave();
//        }
//        return parent::getSave() . serialize($this->property);
//    }

// Данный метод так-же имеется в классе-родителе
//    /**
//     * @return int
//     */
//    public function getSize(): int
//    {
//        return $this->size; // Тип размера - число. Возвращаем только число, без каких-либо дополнительных символов
//    }
}

// Переименуем функцию, т.к. данное имя зарезервировано
function custom_gettype($value): string
{
    if (is_object($value)) {
        $type = get_class($value);
        do {
            if (strpos($type, "Test3\\NewBase") !== false) {
                return 'test';
            }
        } while ($type = get_parent_class($type));
    }
    return gettype($value);
}


$obj = new NewBase('12345');
$obj->setValue('text');

$obj2 = new \Test3\newView('O9876');
$obj2->setValue($obj);
$obj2->setProperty('field');
$obj2->getInfo();

$save = $obj2->getSave();

$obj3 = newView::load($save);

var_dump($obj2->getSave() == $obj3->getSave());

