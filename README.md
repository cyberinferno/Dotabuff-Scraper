# Dotabuff Scraper
A script to scrape Dotabuff website for relevant information needed.

It is inspired from the project https://github.com/onuraslan/DotaBuffCP which is written in Perl!

# Requirements
- PHP5+
- php_curl extension
- Stable internet connection for fetching data from Dotabuff website

# Installation
It can be installed via composer with the following configuration
```
{
	"require": {
		"cyberinferno/dotabuff-scraper": "dev-master"
	},
	"config": {
		"preferred-install":"dist",
		"process-timeout": 1800
	}
}
```
