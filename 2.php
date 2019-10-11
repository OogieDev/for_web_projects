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

function exportXml($a, $b) {
    $xml = new DOMDocument();
    $xml->encoding = 'windows-1251';

    $db = getConnection();

    $productsXml = $xml->appendChild($xml->createElement('Продукты'));

    $categoryDb = $db->query("SELECT id FROM a_category WHERE title = '{$b}' LIMIT 1");
    if (!$categoryDb) {
        return;
    }
    $categoriesIds = [];
    $categoryDb = $categoryDb->fetch();
    $categoriesIds[] = $categoryDb['id'];

    $categoryListDb = $db->query("SELECT id from a_category WHERE parent_id = {$categoryDb['id']}");
    if ($categoryListDb) {
        foreach ($categoryListDb->fetchAll() as $category) {
            $categoriesIds[] = $category['id'];
        }
    }

    $categoriesIdsStr = implode(',', $categoriesIds);

    $productCategoryBindingsDb = $db->query("SELECT product_code FROM a_product_category WHERE category_id IN ($categoriesIdsStr)");
    if (!$productCategoryBindingsDb) {
        return;
    }

    $productsIds = [];
    foreach ($productCategoryBindingsDb->fetchAll() as $binding) {
        $productsIds[] = $binding['product_code'];
    }
    $productsIdsStr = implode(',', $productsIds);

    $productsDb = $db->query("SELECT * FROM a_product WHERE code IN ($productsIdsStr)");
    if (!$productsDb) {
        return;
    }

    foreach ($productsDb->fetchAll() as $product) {
        $singleProductXml = $productsXml->appendChild($xml->createElement('Продукт'));

        /* price */
        $productPricesDb = $db->query("SELECT type, price FROM a_price WHERE product_code = {$product['code']}");

        if ($productPricesDb) {
            foreach ($productPricesDb->fetchAll() as $price) {
                $productPrice = $singleProductXml->appendChild($xml->createElement('Цена'));
                $productPrice->setAttribute('Тип', $price['type']);
                $productPrice->nodeValue = $price['price'];
            }
        }

        /* property */
        $productPropertiesDb = $db->query("SELECT id, title, property from a_property WHERE product_code = {$product['code']}");
        $productProperties = $singleProductXml->appendChild($xml->createElement('Свойства'));

        if ($productPropertiesDb) {
            foreach ($productPropertiesDb->fetchAll() as $property) {
                $productProperty = $productProperties->appendChild($xml->createElement($property['title']));
                $productProperty->nodeValue = $property['property'];

                $subPropertyDb = $db->query("SELECT title, value FROM a_subproperty WHERE property_id = {$property['id']}");
                if ($subPropertyDb) {
                    foreach ($subPropertyDb as $subProperty) {
                        $productProperty->setAttribute($subProperty['title'], $subProperty['value']);
                    }
                }
            }
        }

        /* product properties */
        $productCategoriesXml = $singleProductXml->appendChild($xml->createElement("Разделы"));
        $bindingCategoriesDb = $db->query("SELECT category_id FROM a_product_category WHERE product_code = {$product['code']}");
        if ($bindingCategoriesDb) {
            foreach ($bindingCategoriesDb->fetchAll() as $category) {

                $productCategoryDb = $db->query("SELECT title FROM a_category WHERE id = {$category['category_id']}");
                if ($productCategoryDb) {
                    $productCategoryXml = $productCategoriesXml->appendChild($xml->createElement('Раздел'));
                    $productCategoryXml->nodeValue = $productCategoryDb->fetch()['title'];
                }
            }
        }
    }

    $xml->save($a);
}