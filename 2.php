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

function mySortForKey($a, $b) {
    for ($i = 0; $i < count($a); $i++) {
        if (false === key_exists($b, $a[$i])) {
            throw new Exception("Key $b does not exist in array $i");
        }
    }

    usort($a, function ($prev, $next) use ($b) {
        return $prev[$b] > $next[$b];
    });

    return $a;
}

function getConnection() {
    try {
        $db = new PDO('mysql:host=localhost;dbname=test_samson', 'root', '');
        return $db;
    } catch (Exception $e) {
        echo $e->getMessage();
        die;
    }
}

function importXml($a) {
    $xml = new DOMDocument();
    $xml->load($a);
    $db = getConnection();

    $products = $xml->getElementsByTagName("Товар");

    foreach ($products as $product) {
        $code = $product->getAttribute('Код');
        $title = $product->getAttribute('Название');
        $productPreparedStatement = $db->prepare("INSERT INTO a_product (code, title) VALUES (?, ?)");
        $productPreparedStatement->bindParam(1, $code);
        $productPreparedStatement->bindParam(2, $title);
        $productPreparedStatement->execute();

        $xmlPrice = $product->getElementsByTagName('Цена');
        foreach ($xmlPrice as $price) {
            $pricePreparedStatement = $db->prepare("INSERT INTO a_price (product_code, type, price) VALUES (?, ?, ?)");
            $pricePreparedStatement->bindParam(1, $code);
            $pricePreparedStatement->bindParam(2, $price->getAttribute('Тип'));
            $pricePreparedStatement->bindParam(3, $price->nodeValue);
            $pricePreparedStatement->execute();
        }

        $xmlProperty = $product->getElementsByTagName('Свойства');
        foreach ($xmlProperty as $child) {
            foreach ($child->childNodes as $node) {
                $propertyPreparedStatement = $db->prepare("INSERT INTO a_property (product_code, title, property) VALUES (?, ?, ?)");
                $propertyPreparedStatement->bindParam(1, $code);
                $propertyPreparedStatement->bindParam(2, $node->tagName);
                $propertyPreparedStatement->bindParam(3, $node->nodeValue);
                $propertyPreparedStatement->execute();
                $lastPropertyId = $db->lastInsertId();

                if ($node->attributes) {
                    foreach ($node->attributes as $attribute) {
                        $subPropertyPreparedStatement = $db->prepare("INSERT INTO a_subproperty (property_id, title, value) VALUES (?, ?, ?)");
                        $subPropertyPreparedStatement->bindParam(1, $lastPropertyId);
                        $subPropertyPreparedStatement->bindParam(2, $attribute->name);
                        $subPropertyPreparedStatement->bindParam(3, $attribute->value);
                        $subPropertyPreparedStatement->execute();
                    }
                }
            }
        }

        $xmlCategories = $product->getElementsByTagName('Раздел');
        foreach ($xmlCategories as $category) {
            $categoryFromDb = $db->query("SELECT id from a_category WHERE title = '{$category->nodeValue}' LIMIT 1");
            $categoryId = null;
            if (!$categoryFromDb) {
                $categoryPreparedStatement = $db->prepare("INSERT INTO a_category (title) VALUES (?)");
                $categoryPreparedStatement->bindParam(1, $category->nodeValue);
                $categoryId = $db->lastInsertId();
            } else {
                $categoryId = $categoryFromDb->fetch()['id'];
            }

            $productCategoryBinding = $db->query("SELECT * FROM a_product_category WHERE product_code = $code AND category_id = $categoryId LIMIT 1");
            if (!$productCategoryBinding->fetch()) {
                $productCategoryPreparedStatement = $db->prepare("INSERT INTO a_product_category (product_code, category_id) VALUES (?, ?)");
                $productCategoryPreparedStatement->bindParam(1, $code);
                $productCategoryPreparedStatement->bindParam(2, $categoryId);
                $productCategoryPreparedStatement->execute();
            }
        }
    }
}