<?php declare(strict_types = 1);

namespace PatrikValenta\HeurekaFeedPersonal\Model\Feed;

use PatrikValenta\HeurekaFeedPersonal\FeedService;

class ProductImportEntity
{

	private const NAME_SUFFIX_HEAT_DEVICE_SK = ' (zariadenia pre zahrievaný tabák)';

	private const NAME_SUFFIX_HEAT_DEVICE_CZ = ' (zařízení pro zahřívaný tabák)';

	private const NAME_SUFFIX_E_CIGARETTE_SK = ' (e-cigareta)';

	private const NAME_SUFFIX_E_CIGARETTE_CZ = ' (e-cigareta)';
	private const SK_PRODUCT_WARNING = ' IQOS nie je bez rizika. Dodáva nikotín, ktorý je návykový. Výhradne pre dospelých, ktorí by inak pokračovali vo fajčení alebo v užívaní iných nikotínových produktov. Nakupovať na iqos.com môžu len plne registrovaní zákazníci s podpísanou rámcovou zmluvou o budúcich dodávkach z IQOS.com.';
	private const CZ_PRODUCT_WARNING = ' IQOS není bez rizika. Obsahuje nikotin, který je návykový. Je určen výhradně pro dospělé, kteří by pokračovali v kouření anebo užívání jiných nikotinových produktů.';

	public string $code;

	public string $name;

	public float $priceVat;

	public string $description;

	public string $uri;

	public string $ean;

	public string $materialGroup;

	public string $defaultCategoryCode;

	public string $heurekaProductCategory;

	/** @var self[] */
	public array $variants;

	public string $country;

	public string $lang;

	/** @param self[] $products */
	public function __construct(string $code, string $name, float $priceVat, string $description, string $uri, string $ean, string $materialGroup, string $defaultCategoryCode, array $products, string $country, string $lang)
	{
		$this->code = $code;
		$this->name = $name;
		$this->priceVat = $priceVat;
		$this->description = $description;
		$this->uri = $uri;
		$this->ean = $ean;
		$this->materialGroup = $materialGroup;
		$this->defaultCategoryCode = $defaultCategoryCode;
		$this->variants = $products;
		$this->country = $country;
		$this->lang = $lang;
	}

	/** @param mixed[] $response */
	public static function createFromResponse(array $response, string $country, string $lang): self
	{
		return new self(
			strval($response['code'] ?? ''),
			strval($response['name'] ?? ''),
			floatval($response['price']['value'] ?? 0),
			strval($response['description'] ?? ''),
			strval($response['productUrls'][0]['url'] ?? ''),
			strval($response['ean'] ?? ''),
			strval($response['pmiProductCategory'] ?? ''),
			strval($response['defaultCategory']['code'] ?? ''),
			array_map(fn (array $variant) => self::createProductFromVariant($variant, strval($response['description']), strval($response['pmiProductCategory']), $country, $lang), $response['variantOptions'] ?? []), // nelze předat manufacturer – lze volat až po zbuildění zbytku, protože manufacturer se získává z názvu parenta
			$country,
			$lang
		);
	}

	/** @param mixed[] $variant */
	public static function createProductFromVariant(array $variant, string $description, string $materialGroup, string $country, string $lang): self
	{
		return new self(
			strval($variant['code'] ?? ''),
			strval($variant['name'] ?? ''),
			floatval($variant['priceData']['value'] ?? 0),
			$description,
			strval($variant['productUrls'][0]['url'] ?? ''),
			strval($variant['ean'] ?? ''),
			$materialGroup,
			strval($variant['defaultCategory']['code'] ?? ''),
			[],
			$country,
			$lang
		);
	}

	public function getUrl(): string
	{
		return 'https://www.iqos.com/' . $this->country . '/' . $this->lang . '/shop/' . $this->uri . '.html';
	}

	public function getImgUrl(): string
	{
		return 'https://www.iqos.com/vanity/content/pmisite/' . $this->country . '/' . $this->lang . '/.rrp.' . $this->code . '.00.800x600.jpg/' . $this->uri . '.jpg';
	}

	public function getManufacturer(): string
	{
		$manufacturer = 'IQOS';

		if (str_contains($this->name, 'IQOS')) {
			$manufacturer = 'IQOS';
		} elseif (str_contains($this->name, 'HEETS')) {
			$manufacturer = 'HEETS';
		} elseif (str_contains($this->name, 'lil Solid')) {
			$manufacturer = 'lil Solid';
		} elseif (str_contains($this->name, 'VEEV N')) {
			$manufacturer = 'VEEV';
		} elseif (str_contains($this->name, 'IQOS VEEV')) {
			$manufacturer = 'IQOS VEEV';
		} elseif (str_contains($this->name, 'Fiit')) {
			$manufacturer = 'fiit';
		} elseif (str_contains($this->name, 'ZYN')) {
			$manufacturer = 'ZYN';
		}

		return $manufacturer;
	}

	public function getName(): string
	{
		$deviceType = '';
		if (str_contains($this->defaultCategoryCode, 'ht-device') || str_contains($this->defaultCategoryCode, 'all-device') || str_contains($this->defaultCategoryCode, 'iluma-device')) {
			$deviceType = $this->lang === 'sk' ? self::NAME_SUFFIX_HEAT_DEVICE_SK : self::NAME_SUFFIX_HEAT_DEVICE_CZ;
		} elseif (str_contains($this->defaultCategoryCode, 'pv-device')) {
			$deviceType = $this->lang === 'sk' ? self::NAME_SUFFIX_E_CIGARETTE_SK : self::NAME_SUFFIX_E_CIGARETTE_CZ;
		}

		return $this->name . $deviceType;
	}

	public function getDescription(): string
	{
		$healtWarning = $this->lang === 'sk' ? self::SK_PRODUCT_WARNING : self::CZ_PRODUCT_WARNING;

		return $this->description . $healtWarning;
	}

	public function getMaterialGroup(): string
	{
		return $this->materialGroup === '' ? 'M0401' : $this->materialGroup;
	}

	public function getHeurekaProductCategory(string $materialGroup): string
	{
		switch ($materialGroup) {
			case 'M0101':
				return $this->lang === FeedService::LANG_CS
					? 'Heureka.cz | Hobby | Kuřácké potřeby | Elektronické cigarety | Zahřívaný tabák | Náplně pro zahřívaný tabák'
					: 'Heureka.sk | Hobby | Fajčiarske potreby| Elektronické cigarety | Zahrievaný tabák | Náplně pre zahrievaný tabák';
			case 'M0105':
				return $this->lang === FeedService::LANG_CS
					? 'Heureka.cz | Hobby | Kuřácké potřeby | Elektronické cigarety | E-cigarety | Jednorázové e-cigarety'
					: 'Heureka.sk | Hobby | Fajčiarske potreby | Elektronické cigarety | E-cigarety | Jednorazové e-cigarety';
			case 'M0109':
				return $this->lang === FeedService::LANG_CS
					? 'Heureka.cz | Hobby | Kuřácké potřeby | Elektronické cigarety | E-cigarety | Komponenty pro sestavení e-cigaret | Cartridge do e-cigaret'
					: 'Heureka.sk | Hobby | Fajčiarske potreby | Elektronické cigarety | E-cigarety | Komponenty pre zostavenie e-cigariet | Cartridge do e-cigariet';
			case 'M0118':
				return $this->lang === FeedService::LANG_CS
					? 'Heureka.cz | Hobby | Kuřácké potřeby | Elektronické cigarety | Zahřívaný tabák | Náplně pro zahřívaný tabák'
					: 'Heureka.sk | Hobby | Fajčiarske potreby| Elektronické cigarety | Zahrievaný tabák | Náplně pre zahrievaný tabák';
			case 'M0409':
				return $this->lang === FeedService::LANG_CS
					? 'Heureka.cz | Hobby | Kuřácké potřeby | Elektronické cigarety | E-cigarety | Komponenty pro sestavení e-cigaret | Cartridge do e-cigaret'
					: 'Heureka.sk | Hobby | Fajčiarske potreby | Elektronické cigarety | E-cigarety | Komponenty pre zostavenie e-cigariet | Cartridge do e-cigariet';
			case 'M0301':
			case 'M0302':
			case 'M0401':
				return $this->lang === FeedService::LANG_CS
					? 'Heureka.cz | Hobby | Kuřácké potřeby | Elektronické cigarety | Zahřívaný tabák | Zařízení pro zahřívaný tabák'
					: 'Heureka.sk | Hobby | Fajčiarske potreby| Elektronické cigarety | Zahrievaný tabák | Zariadenia pre zahrievaný tabák';
			case 'M0303':
			case 'M0304':
			case 'M0306':
			case 'M0308':
			case 'M0309':
			case 'M0320':
			case 'M0321':
			case 'M0322':
			case 'M0323':
			case 'Accessories':
				return $this->lang === FeedService::LANG_CS
					? 'Heureka.cz | Hobby | Kuřácké potřeby | Elektronické cigarety | Zahřívaný tabák | Příslušenství pro zahřívaný tabák'
					: 'Heureka.sk | Hobby | Fajčiarske potreby| Elektronické cigarety | Zahrievaný tabák | Príslušenstvo pre zahrievaný tabák';
			case 'M0327':
				return $this->lang === FeedService::LANG_CS
					? 'Heureka.cz | Hobby | Kuřácké potřeby | Elektronické cigarety | E-cigarety | Příslušenství pro e-cigarety'
					: 'Heureka.sk | Hobby | Fajčiarske potreby| Elektronické cigarety | E-cigarety | Príslušenstvo pre e-cigarety';
			case 'M0405':
				return $this->lang === FeedService::LANG_CS
					? 'Heureka.cz | Hobby | Kuřácké potřeby | Elektronické cigarety | E-cigarety | Sety e-cigaret'
					: 'Heureka.sk | Hobby | Fajčiarske potreby| Elektronické cigarety | E-cigarety | Sety e-cigaret';
			case 'M0127':
				return $this->lang === FeedService::LANG_CS
					? 'Heureka.cz | Hobby | Kuřácké potřeby | Tabákové výrobky a příslušenství | Nikotinové sáčky'
					: 'Heureka.sk | Hobby | Fajčiarske potreby| Tabakové výrobky a príslušenstvo | Nikotínové vrecká';
			default:
				throw new \InvalidArgumentException('Unknown material group ' . $materialGroup . $this->name);
		}
	}

}
