<?php

function http_request($url, $header = null, $method = 'GET', $data = null, $len = null) {

    switch ($method) {

        case 'GET':

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($ch, CURLOPT_HEADER, false);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            break;

        case 'POST':

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($ch, CURLOPT_HEADER, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            curl_setopt($ch, CURLOPT_POST, true); //not createBasket but addBasketItem

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

            break;

        case 'PUT':

            // Prepare the data for HTTP PUT

            $putData = tmpfile();

            fwrite($putData, $data);

            fseek($putData, 0);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch, CURLOPT_PUT, true);

            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            curl_setopt($ch, CURLOPT_INFILE, $putData);

            curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));

            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

            fclose($putData);

            break;

        case 'DELETE':

            break;

    }

    $result = curl_exec($ch);

    curl_close($ch);

    return $result;

}

?>