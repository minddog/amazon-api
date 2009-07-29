#!/usr/bin/php
<?php

require_once 'config.php';
require_once 'AmazonSearchAPI.php';
require_once 'AmazonResponse.php';

function usage($argv) {
    echo $argv[0]. " isbn[,isbn]\n";
}

function lookup_item($sku) {
    try {
        $amazon = new AmazonSearchAPI(AMAZON_ACCESS_KEY, AMAZON_ASSOCIATE_TAG);
        $result = $amazon->ItemLookup(array('ItemId' => $sku, 'ResponseGroup' => 'ItemAttributes'));
    } catch(AmazonSearchAPIError $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        exit(1);
    }

    $response = new AmazonResponse($result);
    $items = $response->getItems();

    return $items;
}

function main($argc, $argv) {
    if($argc != 2) {
        usage($argv);
        exit;
    }

    $sku = trim($argv[1]);
    
    $items = lookup_item($sku);

    foreach($items as $item) {
        /* ISBN10, ISBN13, Weight, Dimensions, and Current Price */
        $attributes = $item["ItemAttributes"];
        $dimensions = $item["ItemAttributes"]["PackageDimensions"];
        echo "ISBN10: ".$attributes["ISBN"]."\n";
        echo "ISBN13: ".$attributes["EAN"]."\n";
        echo "Weight: ".$dimensions["Weight"]."\n";
        echo "Width: ".$dimensions["Width"]."\n";
        echo "Height: ".$dimensions["Height"]."\n";
        echo "Length: ".$dimensions["Length"]."\n";
        echo "Current Price: ".$attributes["ListPrice"]["FormattedPrice"]."\n";
        echo "---------------------------\n";
    }
}


main($argc, $argv);