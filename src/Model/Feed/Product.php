<?php declare(strict_types = 1);

namespace PatrikValenta\HeurekaFeedPersonal\Model\Feed;

class Product
{

	public string $code;

	public string $name;

	public float $priceVat;

	public string $description;

	public string $ean;

	public string $materialGroup;

	public string $defaultCategoryCode;

	public string $url;

	public string $imgUrl;

	public string $manufacturer;

	public string $heurekaProductCategory;

	public function __construct(string $code, string $name, float $priceVat, string $description, string $ean, string $materialGroup, string $defaultCategoryCode, string $url, string $imgUrl, string $manufacturer, string $heurekaProductCategory)
	{
		$this->code = $code;
		$this->name = $name;
		$this->priceVat = $priceVat;
		$this->description = $description;
		$this->ean = $ean;
		$this->materialGroup = $materialGroup;
		$this->defaultCategoryCode = $defaultCategoryCode;
		$this->url = $url;
		$this->imgUrl = $imgUrl;
		$this->manufacturer = $manufacturer;
		$this->heurekaProductCategory = $heurekaProductCategory;
	}

	public static function createFromProductImport(ProductImportEntity $productImport): self
	{
		return new self(
			$productImport->code,
			$productImport->getName(),
			$productImport->priceVat,
			$productImport->getDescription(),
			$productImport->ean,
			$productImport->getMaterialGroup(),
			$productImport->defaultCategoryCode,
			$productImport->getUrl(),
			$productImport->getImgUrl(),
			$productImport->getManufacturer(),
			$productImport->getHeurekaProductCategory($productImport->getMaterialGroup())
		);
	}

	public static function createFromProductImportVariant(ProductImportEntity $childProductImport, self $parentProductImport): self
	{
		return new self(
			$childProductImport->code,
			$childProductImport->getName(),
			$childProductImport->priceVat,
			$childProductImport->getDescription(),
			$childProductImport->ean,
			$childProductImport->getMaterialGroup(),
			$childProductImport->defaultCategoryCode,
			$childProductImport->getUrl(),
			$childProductImport->getImgUrl(),
			$parentProductImport->manufacturer,
			$parentProductImport->heurekaProductCategory
		);
	}

}
