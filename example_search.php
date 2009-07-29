#!/usr/bin/php
<?php

require_once 'config.php';
require_once 'AmazonSearchAPI.php';
require_once 'AmazonResponse.php';

function usage() {
    if(empty($argv[1])) {
        echo $argv[0]. "[book keyword]\n";
        exit;
    }
}

function search($keywords) { 
    try {
        $amazon = new AmazonSearchAPI(AMAZON_ACCESS_KEY, AMAZON_ASSOCIATE_TAG);
        $xml = $amazon->ItemSearch(array('SearchIndex' => 'Books', 'Keywords' => $keywords));
    } catch(AmazonSearchAPIError $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        exit(1);
    }

    $resp = new AmazonResponse($xml);
    return $resp->getItems();
}


function main($argc, $argv) {
    if($argc != 2) {
        usage();
        exit();
    }
    $keywords = $argv[1];
    $matches = search($keywords);
    echo "Found ". count($matches) . " matches\n";
    foreach($matches as $id => $match) {
        $attributes = $match["ItemAttributes"];
        echo "{$attributes["Title"]} by {$attributes["Author"]}\n";
        echo "--- {$match["DetailPageURL"]}\n";
    }
}

main($argc, $argv);