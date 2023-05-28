# surugaya-scraper
Scrape surugaya's categories for items and changes

## Requirements
* requests_html
* tqdm

## Database
Create a sqlite3 database with these create table statements:

```sql
CREATE TABLE 'items' ('productid' INTEGER PRIMARY KEY NOT NULL, 'name' TEXT, 'circle' TEXT, 'price' INTEGER, 'image' TEXT, 'release' TEXT,'condition' INTEGER, 'status' TEXT, 'timesale' INTEGER DEFAULT 0)
CREATE TABLE 'changes' ('type' INTEGER,'from' TEXT, 'to' TEXT, 'productid' INTEGER NOT NULL , 'found' BOOLEAN DEFAULT CURRENT_TIMESTAMP )
```

# website
Website to display information about changes.

## Requirements
* php
* random_bytes polyfill https://github.com/paragonie/random_compat in /in/lib
* bootstrap in /in/lib (you probably will need to change the integrity hash)
* jquery 3.4.1 in /in/lib
* cron

## Changes to make before running
* Replace reCAPTCHA secret token in index.php
* Replace database paths in index.php, changes.php, ajax/import_list.php, items.php, search.php, logout.php, cronlists.php.
* Replace email from address and admin address in cronlists.php.
* Replace domain name in lists.php script template.
* Replace directory names if neccessary in all files.
* Replace default invite code.
* Set up a cronjob to run cronlists.php.
* Redirect all directory traffic to index.php like follows (apache):

```
<Directory "/website/">
	RewriteCond %{REQUEST_URI} !^/website/in/*
	RewriteRule ^ /website/index.php [L,END]
</Directory>
```

## Database
Create a sqlite3 database with this schema:

```sql
CREATE TABLE 'change_types' ('id' INTEGER PRIMARY KEY NOT NULL,'name' INTEGER)
CREATE TABLE 'list_filters' ('list' INTEGER NOT NULL, 'from' INTEGER NOT NULL , 'to' INTEGER NOT NULL , 'operator' INTEGER NOT NULL , 'text' TEXT DEFAULT NULL)
CREATE TABLE 'list_items' ('list' INTEGER NOT NULL, 'productid' INTEGER NOT NULL)
CREATE TABLE 'list_triggers' ('list' INTEGER NOT NULL,'change_type' INTEGER NOT NULL)
CREATE TABLE 'lists' ('user' INTEGER NOT NULL, 'name' TEXT NOT NULL, 'default' INTEGER NOT NULL DEFAULT 0 , 'enabled' INTEGER NOT NULL DEFAULT 1, 'mode' INTEGER NOT NULL DEFAULT 0, 'key' TEXT)
CREATE TABLE 'users' ('name' TEXT NOT NULL, 'password' TEXT NOT NULL, 'session' TEXT, 'session_ip' TEXT, 'email' TEXT, 'admin' INTEGER NOT NULL DEFAULT 0)

INSERT INTO "change_types" ("id","name") VALUES ('0','New Item');
INSERT INTO "change_types" ("id","name") VALUES ('1','Item Name');
INSERT INTO "change_types" ("id","name") VALUES ('2','Circle');
INSERT INTO "change_types" ("id","name") VALUES ('3','Price');
INSERT INTO "change_types" ("id","name") VALUES ('4','Image');
INSERT INTO "change_types" ("id","name") VALUES ('5','Release');
INSERT INTO "change_types" ("id","name") VALUES ('6','Condition');
INSERT INTO "change_types" ("id","name") VALUES ('7','Status');
INSERT INTO "change_types" ("id","name") VALUES ('8','Timesale');
```

# License
GPL 3.0