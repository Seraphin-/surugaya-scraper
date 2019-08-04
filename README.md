# surugaya-scraper
Scrape surugaya's categories for items and changes

## Requirements
requests_html
tqdm

## Database
	CREATE TABLE 'items' ('productid' INTEGER PRIMARY KEY NOT NULL, 'name' TEXT, 'circle' TEXT, 'price' INTEGER, 'image' TEXT, 'release' TEXT,'condition' INTEGER, 'status' TEXT, 'timesale' INTEGER DEFAULT 0)
	CREATE TABLE 'changes' ('type' INTEGER,'from' TEXT, 'to' TEXT, 'productid' INTEGER NOT NULL , 'found' BOOLEAN DEFAULT CURRENT_TIMESTAMP )