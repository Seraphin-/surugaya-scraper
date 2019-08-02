from requests_html import HTMLSession
import sqlite3
from datetime import datetime
from tqdm import tqdm, tqdm_gui

logfile = open(datetime.now().strftime("%Y-%m-%d-%H.log"), "a")
def log(base, name, itemid):
	logfile.write(base + " " + name + " " + str(itemid) + "\n")

conn = sqlite3.connect('surugaya.db3')
conn.row_factory = sqlite3.Row
conn.execute('PRAGMA encoding="UTF-8";')
c = conn.cursor()

session = HTMLSession()
BASE = "https://www.suruga-ya.jp"

r = session.get(BASE + '/search?category=11010200&search_word=&rankBy=release_date%28int%29%3Aascending')
categories = r.html.xpath('/html/body/div[1]/div[2]/div[2]/ul')[0].links #years
categories.update(r.html.xpath('/html/body/div[1]/div[2]/div[4]/ul')[0].links) #price
categories.add('/search?category=11010200&search_word=&rankBy=release_date%28int%29%3Aascending&restrict[]=price=[0,199]')
for category in categories:
	#Check if recently done (a hour)
	c.execute('SELECT * FROM log WHERE category = ? AND timestamp > ?', [category, datetime.now().timestamp() - 60 * 60 * 1])
	if c.fetchone() is not None:
		print('Skipping recently done', category)
		continue
	dirty = False #skip when true, used for avoiding extra duplication on price sort
	#Get first page manually and count total
	first_page = session.get(BASE + category + '&inStock=On&adult_s=1')
	total, per = first_page.html.xpath('/html/body/div[1]/div[1]/div[2]/div[1]/div[1]')[0].search('該当件数:{}件中\xa01-{}件')
	pages = -(-int(total.replace(',', '')) // int(per))
	print('Grabbing for', category, 'with total items', total)
	for i in tqdm(range(1, pages+1)): #just reget the first one to make it easier
		active_page = session.get(BASE + category + '&inStock=On&adult_s=1' + '&page=' + str(i))
		items = active_page.html.find('.item')
		for item in items:
			itemid = int(item.find('.thum > a')[0].attrs['href'].split('/')[-1])
			name = item.find('.title > a')[0].text
			circle = item.find('.brand')[0].text
			price = item.find('.item_price > .price_teika > span > strong')
			condition = 0
			if len(price) == 0:
				price = -1
			else:
				price = int(price[0].text.replace(',', '').replace('￥', ''))
				condition = item.find('.item_price > .price_teika')[0].text.split(':')[0]
				if condition == "中古":
					condition = 1 #used
				else:
					condition = 2 #new
			image = item.find('.thum > a > img')[0].attrs['src']
			release = item.find('.release_date')
			if len(release) == 0:
				release = "<Unknown>"
			else:
				release = release[0].text
			if release == '発売日：/':
				release = "<Unknown>" #another odd condition
			status = item.find('.condition > span')
			if len(status) == 0:
				status = "none"
			else:
				status = status[0].attrs['class'][0] #hit, sale, new_arrival

			if "price" in category and release != "<Unknown>" and circle != "GREen": #bugged with that one
				dirty = True #no more no date releases to get for this price range

			c.execute('SELECT * FROM items WHERE productid = ?', [itemid])
			row = c.fetchone()
			props = [itemid, name, circle, price, image, release, condition, status]
			if row is None:
				c.execute('INSERT INTO items VALUES (?,?,?,?,?,?,?,?)', props)
				log("New items:", name, itemid)
			else:
				update = False
				for p in range(1, len(props)):
					if props[p] != row[p] and row[0] != '186148292': #item with dupe search
						log('Updated ' + row.keys()[p] + ':', props[1], props[0])
						update = True
				if update:
					c.execute('UPDATE items SET name = ?, circle = ?, price = ?, image = ?, release = ?, condition = ?, status = ? WHERE productid = ?', props[1:] + [props[0]])
		conn.commit()
		if dirty:
			print("\nExited out of price set early, no more unknown date releases")
			break
	c.execute('INSERT INTO log VALUES (?,?)', [category, datetime.now().timestamp()])