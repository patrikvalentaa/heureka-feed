<?php declare(strict_types = 1);

namespace PatrikValenta\HeurekaFeedPersonal\Model\Feed;

class Feed
{

	public int $currentPage;

	public int $totalPages;

	public int $totalResults;

	/** @var ProductImportEntity[] */
	public array $products;

	/** @param ProductImportEntity[] $products */
	public function __construct(int $currentPage, int $totalPages, int $totalResults, array $products)
	{
		$this->currentPage = $currentPage;
		$this->totalPages = $totalPages;
		$this->totalResults = $totalResults;
		$this->products = $products;
	}

	/** @param mixed[] $response */
	public static function createFromResponse(array $response, string $country, string $lang): self
	{
		if (!isset($response['pagination'])) {
			throw new \InvalidArgumentException('Response is missing pagination data.');
		}

		/**
		 * @var array{currentPage: ?int, totalPages: ?int, totalResults: ?int} $pagination
		 */
		$pagination = $response['pagination'];

		return new self(
			intval($pagination['currentPage'] ?? 0),
			intval($pagination['totalPages'] ?? 0),
			intval($pagination['totalResults'] ?? 0),
			array_map(fn (array $product) => ProductImportEntity::createFromResponse($product, $country, $lang), $response['products'] ?? [])
		);
	}

}
