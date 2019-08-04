from multiprocessing import get_context
from multiprocessing.pool import ThreadPool
from requests_html import HTMLSession
from requests.exceptions import ConnectionError
import sqlite3
from datetime import datetime
from tqdm import tqdm, tqdm_gui

def log(base, name, itemid):
	logfile = open(datetime.now().strftime("%Y-%m-%d-%H.log"), "a", encoding='utf-8')
	logfile.write(base + " " + name + " " + str(itemid) + "\n")
	logfile.close()

def get_retry(session, page):
	active_page = False
	attempt_count = 0
	while not active_page:
		try:
			active_page = session.get(page)
			attempt_count += 1
		except ConnectionError:
			if attempt_count > 10:
				print("Gave up on page", page)
				return False
	return active_page

def getPage(page):
	global session
	active_page = get_retry(session, page)
	if active_page == False:
		return
	items = active_page.html.find('.item')
	dirty = False
	plist = []
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
		if "price" in page and release != "<Unknown>" and circle != "GREen": #bugged with that one
			dirty = True #no more no date releases to get for this price range
		plist.append([itemid, name, circle, price, image, release, condition, status])
	return (plist, dirty)

def getCategory(category):
	global session
	#Get first page manually and count total
	session = HTMLSession()
	BASE = "https://www.suruga-ya.jp"
	first_page = get_retry(session, BASE + category + '&inStock=On&adult_s=1')
	if first_page == False:
		return
	total, per = first_page.html.xpath('/html/body/div[1]/div[1]/div[2]/div[1]/div[1]')[0].search('該当件数:{}件中\xa01-{}件')
	pages = -(-int(total.replace(',', '')) // int(per))
	#print('Grabbing for', category, 'with total items', total)
	pagelist = (BASE + category + '&inStock=On&adult_s=1' + '&page=' + str(i) for i in range(1, pages+1))
	pool = ThreadPool(processes=8) #threads
	r = pool.imap_unordered(getPage, pagelist)
	pool.close()
	dirty = False #skip when true, used for avoiding extra duplication on price sort
	full = []

	for plist, dirty in r:
		full += plist
		if dirty:
			pool.terminate()
			break
	pool.join()
	return full

if __name__ == '__main__':
	logfile = open(datetime.now().strftime("%Y-%m-%d-%H.log"), "a", encoding='utf-8')

	session = HTMLSession()
	BASE = "https://www.suruga-ya.jp"

	r = session.get(BASE + '/search?category=11010200&search_word=&rankBy=release_date%28int%29%3Aascending')
	categories = r.html.xpath('/html/body/div[1]/div[2]/div[2]/ul')[0].links #years
	categories.update(r.html.xpath('/html/body/div[1]/div[2]/div[4]/ul')[0].links) #price
	categories.add('/search?category=11010200&search_word=&rankBy=release_date%28int%29%3Aascending&restrict[]=price=[0,199]')
	print("Getting all pages")
	with get_context("spawn").Pool() as pool:
		result = list(tqdm(enumerate(pool.imap_unordered(getCategory, categories)), desc='categories', total=len(categories)))
		pool.close()
		pool.join()
	print("Updating database")
	conn = sqlite3.connect('surugaya.db3', check_same_thread=True)
	conn.row_factory = sqlite3.Row
	conn.execute('PRAGMA encoding="UTF-8";')
	c = conn.cursor()
	for cat in result:
		for props in cat[1]:
			c.execute('SELECT * FROM items WHERE productid = ?', [props[0]])
			row = c.fetchone()
			if row is None:
				c.execute('INSERT INTO items VALUES (?,?,?,?,?,?,?,?)', props)
				log("New items:", props[1], props[0])
			else:
				update = False
				for p in range(1, len(props)):
					if props[p] != row[p] and row[0] != '186148292': #item with dupe search
						log('Updated ' + row.keys()[p] + ':', props[1], props[0])
						update = True
				if update:
					c.execute('UPDATE items SET name = ?, circle = ?, price = ?, image = ?, release = ?, condition = ?, status = ? WHERE productid = ?', props[1:] + [props[0]])
			conn.commit()
	conn.close()
	print("Done")