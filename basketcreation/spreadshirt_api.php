<?php

/*
 * spreadshirt API functions
 */

function create_basket($platform, $shop, $namespaces) {

    $basket = simplexml_load_string('<basket xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://api.spreadshirt.net">
            <shop id="' . $shop['id'] . '"/>
        </basket>');

    $attributes = $shop->baskets->attributes($namespaces['xlink']);

    $basketsUrl = $attributes->href;
    
    $header = array();

    $header[] = create_auth_header("POST", $basketsUrl);

    $header[] = "Content-Type: application/xml";

    $result = http_request($basketsUrl, $header, 'POST', $basket->asXML());

    $basketUrl = parse_http_headers($result, "Location");

    return $basketUrl;

}

function get_basket($basketUrl) {

    $header = array();

    $header[] = create_auth_header("GET", $basketUrl);

    $header[] = "Content-Type: application/xml";

    $result = http_request($basketUrl, $header, 'GET');

    $basket = new SimpleXMLElement($result);

    return $basket;

}

function add_basket_item($basketUrl, $namespaces, $data) {

    // 6. Create basket item

    $basketItemsUrl = $basketUrl . "/items";
       
    $basketItem = simplexml_load_string('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <basketItem xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://api.spreadshirt.net">
            <quantity>' . $data['quantity'] . '</quantity>
            <element id="' . $data['articleId'] . '" type="sprd:article" xlink:href="http://api.spreadshirt.' . $data['platform'] . '/api/v1/shops/' . $data['shopId'] . '/articles/' . $data['articleId'] . '">
                <properties>
                    <property key="appearance">' . $data['appearance'] . '</property>
                    <property key="size">' . $data['size'] . '</property>
                </properties>
            </element>
            <links>
                <link type="edit" xlink:href="http://' . $data['shopId'] . '.spreadshirt.net/-A' . $data['articleId'] . '"/>
                <link type="continueShopping" xlink:href="http://' . $data['shopId'] . '.spreadshirt.net"/>
            </links>
        </basketItem>');

    $header = array();

    $header[] = create_auth_header("POST", $basketItemsUrl);

    $header[] = "Content-Type: application/xml";

    $result = http_request($basketItemsUrl, $header, 'POST', $basketItem->asXML());
    
    /*

    $basketItemUrl = parseHttpHeaders($result, "Location");

    sprd_articles_session('basketItem_'.$data['articleId'], $basketItemUrl);

    return $basketItemUrl;

    */
   
}

function edit_basket_item($basketItem, $data) {

    $basketItem->quantity = $data['quantity'];

    $basketItem->element->properties->property[1] = $data['size'];

    $basketItemUrl = $basketItem->attributes('xlink', true);

    $result = http_request($basketItemUrl . create_auth_url('PUT', $basketItemUrl) , null, 'PUT', $basketItem->asXML());

}

function get_basket_item($basketItemUrl) {

    $header = array();

    $header[] = create_sprd_auth_header("GET", $basketItemUrl);

    $header[] = "Content-Type: application/xml";

    $result = http_request($basketItemUrl, $header, 'GET');

    try {

        $basketItem = @new SimpleXMLElement($result);

    }

    catch(Exception $e) {

        return null;

    }

    return $basketItem;

}

function checkout($basketUrl, $namespaces) {

    $basketCheckoutUrl = $basketUrl . "/checkout";

    $header = array();

    $header[] = create_auth_header("GET", $basketCheckoutUrl);

    $header[] = "Content-Type: application/xml";

    $result = http_request($basketCheckoutUrl, $header, 'GET');

    $checkoutRef = new SimpleXMLElement($result);

    $refAttributes = $checkoutRef->attributes($namespaces['xlink']);

    $checkoutUrl = (string)$refAttributes->href;

    return $checkoutUrl;

}

/*
 * functions to build headers
 */

function create_auth_header($method, $url) {

    $apiKey = API_KEY;

    $secret = API_SECRET;

    $time = time() *1000;

    $data = "$method $url $time";

    $sig = sha1("$data $secret");

    return "Authorization: SprdAuth apiKey=\"$apiKey\", data=\"$data\", sig=\"$sig\"";

}

function create_auth_url($method, $url) {

    $apiKey = API_KEY;

    $secret = API_SECRET;

    $time = time() *1000;

    $data = "$method $url $time";

    $sig = sha1("$data $secret");

    return "?apiKey=" . $apiKey . "&sig=" . $sig . "&time=" . $time;

}

function parse_http_headers($header, $headername) {

    $retVal = array();

    $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));

    foreach($fields as $field) {

        if (preg_match('/(' . $headername . '): (.+)/m', $field, $match)) {

            return $match[2];

        }

    }

    return $retVal;

}
?>