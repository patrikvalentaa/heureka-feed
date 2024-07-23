<?php declare(strict_types = 1);

require __DIR__ . '/vendor/autoload.php';

$country = $_GET['country'] ?? 'cz';
$productType = $_GET['productType'] ?? 'iqos';

$feedService = new PatrikValenta\HeurekaFeedPersonal\FeedService($country, $productType);
$products = $feedService->downloadProductsFromSource();

header('Content-Type: application/xml');
echo $feedService->parseProductInHeurekaXmlFeed($products);