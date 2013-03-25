<?php
session_start();
//session_unset();

/*
 * include http_request function to handle GET, POST, PUT, DELETE request methods
 */
include 'http_request.php';

/*
 * spreadshirts api functions e.g. create basket, add basket item, checkout
 */
include 'spreadshirt_api.php';

/*
 * SETTINGS
 *
 * you need an API key to use basket resources
 * write an email with your partner and shopId to developer@spreadshirt.net to request an API key
 */
define('SHOP_ID', XXX); // your shopId
define('PLATFORM', 'net'); // net or com - depends on where your account is registered
define('LOCALE', 'fr_FR'); // e.g. en_EU, fr_FR, en_GB, us_US
define('API_KEY', XXX);      // add your api key here
define('API_SECRET', XXX);   // add your api secret

/*
 * add an article to the basket
 */
if (isset($_POST['size']) and isset($_POST['appearance']) and isset($_POST['quantity'])) {
    /*
     * create an new basket if not exist
     */
    if (!isset($_SESSION['basketUrl'])) {
        /*
         * get shop xml
         */
        $shop = simplexml_load_file('http://api.spreadshirt.' . PLATFORM . '/api/v1/shops/' . SHOP_ID);

        /*
         * create the basket
         */
        $namespaces = $shop->getNamespaces(true);
        $basketUrl = create_basket(PLATFORM, $shop, $namespaces);
        $_SESSION['basketUrl'] = $basketUrl;
        $_SESSION['namespaces'] = $namespaces;

        /*
         * get the checkout url
         */
        $checkoutUrl = checkout($_SESSION['basketUrl'], $_SESSION['namespaces']);
        $_SESSION['checkoutUrl'] = $checkoutUrl;
    }

    /*
     * article data to be sent to the basket resource
     */
    $data = array(
        'articleId' => $_POST['article'],
        'size' => $_POST['size'],
        'appearance' => $_POST['appearance'],
        'quantity' => (is_numeric($_POST['quantity'])) ? (int)$_POST['quantity'] : (int)1,
        'shopId' => SHOP_ID,
        'platform' => PLATFORM,
        'base_url' => 'test.de'
    );

    /*
     * ... and add to basket
     */
    add_basket_item($_SESSION['basketUrl'] , $_SESSION['namespaces'] , $data);

    /*
     * redirect to the basket location
     */
    header('Location: ' . $_SESSION['checkoutUrl']);
    exit;
}

/*
 * print article list with size and color options
 */

/* get articles xml from spreadshirts api
 * !please note! add the fullData parameter to get the productType resource
 */
$articles = simplexml_load_file(
    'http://api.spreadshirt.' . PLATFORM . '/api/v1/shops/' . SHOP_ID .
    '/articles?locale=' . LOCALE . '&fullData=true&limit=20'
);

$output = '<ul class="articles">';

foreach ($articles->article as $article) {
    /*
     * get the productType resource
     */
    $productType = simplexml_load_file($article->product->productType->attributes('xlink', true));
    $output .= '<li class="clearfix" id="article_'.$article['id'].'"><form method="post">';
    $output .= '<img src="' . (string)$article->resources->resource->attributes('xlink', true) .
        '" alt="' . $article->name . '" class="preview" />';

    /*
     * add a select with available sizes
     */
    $output .= '<select id="size-select" name="size">';

    foreach($productType->sizes->size as $val) {
        $output .= '<option value="'.$val['id'].'">'.$val->name.'</option>';
    }

    $output .= '</select>';

    /*
     * add a list with available product colors
     */
    if ($article->product->restrictions->freeColorSelection == 'true') {
        $output .= '<ul class="colors" name="color">';

        foreach($productType->appearances->appearance as $appearance) {
            $output .= '<li value="'.$appearance['id'].'">' .
                '<img src="'. $appearance->resources->resource->attributes('xlink', true) .'" alt="" /></li>';
        }

        $output .= '</ul>';
    }

    $output .= '<input type="hidden" ' .
        'value="'. $article->product->appearance['id'] .'" id="appearance_'.$article['id'].'" name="appearance" />';
    $output .= '<input type="hidden" value="1" id="quantity_'.$article['id'].'" name="quantity" />';
    $output .= '<input type="hidden" value="'. $article['id'] .'" id="article_'.$article['id'].'" name="article" />';
    $output .= '<input type="submit" name="submit" value="Add to basket" style="float:right" /></form></li>';
}

$output .= '</ul>';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>API example</title>
    <link rel="stylesheet" href="style.css" type="text/css" media="all" />
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
        google.load('jquery', '1.7.1');
    </script>
    <script type="text/javascript">
        $(document).ready(function(){
            /*
             * change article color
             */
            $('.colors li').click(function(){
                var id = '#' + $(this).closest('li.clearfix').attr('id');
                var appearance = $(this).attr('value');
                var src = $(id + ' img.preview').attr('src');
                $(id + ' img.preview').attr('src', src + ',appearanceId='+appearance);
                $(id + ' input[name="appearance"]').attr('value', appearance);
            });
        });
    </script>
</head>

<body>
<?php echo $output; ?>
</body>
</html>