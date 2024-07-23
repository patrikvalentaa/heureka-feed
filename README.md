# Heureka XML feed generator

This project focuses on downloading information from IQOS datasets and parse it to Heureka XML feed file.

## QA

The code is written under the control of phpstan and phpcs standardization. Rules for phpcs taken from [@contributte/qa](https://github.com/contributte/qa). Rules for phpstan from [@contributte/phpstan](https://github.com/contributte/phpstan). The phpstan strictness level was set to max.

## Usage

Map your document root to repository folder. Here is index.php file. Default settings are country = cz and productType = iqos. You can switch it from URL via GET parameters.
```
/index.php?country=sk&productType=zyn
```