# surugaya-scraper
Scrape surugaya's categories for items and changes

## Requirements
requests_html
tqdm

## Database
	CREATE TABLE 'items' ('productid' INTEGER PRIMARY KEY NOT NULL, 'name' TEXT, 'circle' TEXT, 'price' INTEGER, 'image' TEXT, 'release' TEXT,'condition' INTEGER, 'status' TEXT)
	CREATE TABLE 'log' ('category' TEXT, 'timestamp' INTEGER)