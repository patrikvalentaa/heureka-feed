<?php declare(strict_types = 1);

namespace PatrikValenta\HeurekaFeedPersonal;

use GuzzleHttp\Client;
use PatrikValenta\HeurekaFeedPersonal\Model\Feed\Feed;
use PatrikValenta\HeurekaFeedPersonal\Model\Feed\Product;
use PatrikValenta\HeurekaFeedPersonal\Model\Feed\ProductImportEntity;
use XMLWriter;

class FeedService
{

	public const LANG_SK = 'sk';

	public const LANG_CS = 'cs';

	public const COUNTRY_SK = 'sk';

	public const COUNTRY_CZ = 'cz';

	private const ALLOWED_COUNTRIES = [
		self::COUNTRY_SK,
		self::COUNTRY_CZ,
	];

	protected string $country;

	protected string $lang;

	protected string $productType;

	public function __construct(string $country, string $productType)
	{
		if (!in_array(strtolower($country), self::ALLOWED_COUNTRIES, true)) {
			throw new \InvalidArgumentException('Country ' . $country . ' is not allowed.');
		}

		$this->country = $country;
		$this->lang = $country === self::COUNTRY_SK ? self::LANG_SK : self::LANG_CS;
		$this->productType = $productType;
	}

	/** @return Product[] */
	public function downloadProductsFromSource(): array
	{
		$client = new Client();
		$response = $client->request('GET', $this->getTargetUrl(), [
			'headers' => [
				'Accept' => 'application/json',
			],
		]);

		/** @var mixed[] $data */
		$data = json_decode($response->getBody()->getContents(), true);
		$feed = Feed::createFromResponse($data, $this->country, $this->lang);

		$products = [];
		for ($i = 0; $i <= $feed->totalPages; $i++) {
			$response = $client->request('GET', $this->getTargetUrl($i), [
				'headers' => [
					'Accept' => 'application/json',
				],
			]);

			/** @var mixed[] $data */
			$data = json_decode($response->getBody()->getContents(), true);
			$feed = Feed::createFromResponse($data, $this->country, $this->lang);
			$products = array_merge($products, $feed->products);
		}

		return $this->convertProductsImportToProducts($products);
	}

	/**
	 * @param Product[] $products
	 */
	public function parseProductInHeurekaXmlFeed(array $products): string
	{
		$writer = new XMLWriter();
		$writer->openMemory();

		$writer->startDocument('1.0', 'UTF-8');

		$writer->startElement('SHOP');

		foreach ($products as $product) {
			$writer->startElement('SHOPITEM');
			$writer->writeElement('ITEM_ID', $product->code);
			$writer->writeElement('PRODUCTNAME', $product->name);
			$writer->writeElement('PRODUCT', $product->name);
			$writer->writeElement('DESCRIPTION', $product->description);
			$writer->writeElement('URL', $product->url);
			$writer->writeElement('EAN', $product->ean);
			$writer->writeElement('IMGURL', $product->imgUrl);
			$writer->writeElement('PRICE_VAT', strval($product->priceVat));
			$writer->writeElement('MANUFACTURER', $product->manufacturer);
			$writer->writeElement('CATEGORYTEXT', $product->heurekaProductCategory);
			$writer->writeElement('PRODUCTNO', $product->code);
			$this->writeDelivery($writer, $product->priceVat);

			$writer->endElement();

		}

		$writer->endElement();

		$writer->endDocument();

		return $writer->outputMemory();
	}

	private function getTargetUrl(int $currentPage = 0): string
	{
		return 'https://occ.iqos.com/rest/v2/' . $this->productType . '-' . $this->country . '-B2C-web@' . $this->productType . '-' . $this->country . '-B2C-web/products/search?currentPage=' . $currentPage . '&pageSize=1000000&sort=relevance&fields=FULL&query=:relevance:category:all';
	}

	/**
	 * @param ProductImportEntity[] $productsImport
	 * @return Product[]
	 */
	private function convertProductsImportToProducts(array $productsImport): array
	{
		$products = [];

		foreach ($productsImport as $productImport) {
			$product = Product::createFromProductImport($productImport);

			if ($productImport->variants === []) {
				$products[] = $product;
			} else {
				foreach ($productImport->variants as $variant) {
					$products[] = Product::createFromProductImportVariant($variant, $product);
				}
			}
		}

		return $products;
	}

	private function writeDelivery(XMLWriter $writer, float $productPrice): void
	{
		$deliveries = [
			[
				'id' => 'DPD',
				'price' => $this->country === self::COUNTRY_CZ ? 99 : 3.9,
				'freeShippingThreshold' => $this->country === self::COUNTRY_CZ ? 1490 : 50,
			],
			[
				'id' => 'ZASILKOVNA',
				'price' => $this->country === self::COUNTRY_CZ ? 49 : 1.9,
				'freeShippingThreshold' => $this->country === self::COUNTRY_CZ ? 1490 : 50,
			],
		];

		/** @var array{id: string, price: float, freeShippingThreshold: int} $delivery */
		foreach ($deliveries as $delivery) {
			$realPrice = $productPrice >= $delivery['freeShippingThreshold'] ? 0 : $delivery['price'];

			$writer->startElement('DELIVERY');
			$writer->writeElement('DELIVERY_ID', $delivery['id']);
			$writer->writeElement('DELIVERY_PRICE', strval($realPrice));
			$writer->writeElement('DELIVERY_PRICE_COD', strval($realPrice));
			$writer->endElement();
		}
	}

}
