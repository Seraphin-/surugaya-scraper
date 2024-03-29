from multiprocessing import get_context
from multiprocessing.pool import ThreadPool
from requests_html import HTMLSession
from requests.exceptions import ConnectionError, Timeout, TooManyRedirects
import sqlite3
from tqdm import tqdm
import logging

logging.basicConfig()
# logging.getLogger().setLevel(logging.DEBUG)
debug = logging.debug

def get_retry(session, page):
	debug("get_retry: %s", page)
	active_page = False
	attempt_count = 0
	while not active_page:
		try:
			active_page = session.get(page, timeout=5)
			attempt_count += 1
		except (ConnectionError, Timeout, TooManyRedirects):
			debug("get_retry: failed %d * %s", attempt_count, page)
			if attempt_count > 10:
				print("Gave up on page", page)
				return False
	return active_page

def getPage(page):
	global session, requested
	requested += 1
	active_page = get_retry(session, page)
	if active_page == False:
		return
	items = active_page.html.find('.item')
	dirty = False
	plist = []
	for item in items:
		itemid = int(item.find('.thum > a')[0].attrs['href'].split('/')[-1].split('?')[0])
		name = item.find('.title > a')[0].text
		circle = item.find('.brand')[0].text
		price = item.find('.item_price > .price_teika > span > strong')
		condition = 0
		if len(price) == 0:
			price = -1
		else:
			price = int(price[0].text.replace(',', '').replace('ï¿¥', ''))
			condition = item.find('.item_price > .price_teika')[0].text.split('ï¼š')[0]
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
		timesale = item.find('.timesale')
		if len(timesale) == 0:
			timesale = 0
		else:
			timesale = 1
		plist.append([itemid, name, circle, price, image, release, condition, status, timesale])
	return (plist, dirty)

def getCategory(category):
	global session, requested
	#Get first page manually and count total
	session = HTMLSession()
	BASE = "https://www.suruga-ya.jp"
	first_page = get_retry(session, BASE + category + '&inStock=On&adult_s=1')
	if first_page == False:
		return
	
	first_page_valid = first_page.html.find('.search_option > .hit')
	if len(first_page_valid) == 0:
		return
	search_res = first_page_valid[0].search('該当件数:{}件中\xa01-{}件')
	if search_res is None or len(search_res.fixed) != 2:
		return # Failed search (0)
	total, per = search_res
	pages = -(-int(total.replace(',', '')) // int(per))
	#print('Grabbing for', category, 'with total items', total)
	pagelist = (BASE + category + '&inStock=On&adult_s=1' + '&page=' + str(i) for i in range(1, pages+1))
	pool = ThreadPool(processes=8) #threads
	r = pool.imap(getPage, pagelist) #we want to preserve order so as to not bail out too early
	pool.close()
	dirty = False #skip when true, used for avoiding extra duplication on price sort
	full = []
	requested = 0
	returned = 0

	for plist, dirty in r:
		full += plist
		returned += 1
		if dirty:
			pool.terminate()
			break
	pool.join()
	del session
	return full, pages, requested, returned

if __name__ == '__main__':
	session = HTMLSession()
	BASE = "https://www.suruga-ya.jp"
	BASE_YEAR_S = "/search?category=11010200&search_word=&rankBy=release_date(int)%3Aascending&restrict[]=release_year=[{},{}]"

	r = session.get(BASE + '/search?category=11010200&search_word=&rankBy=release_date%28int%29%3Aascending', timeout=5)
	# categories = r.html.xpath('/html/body/div[1]/div[1]/div[2]/div[1]/div[2]/ul')[0].links #years
	categories = set()
	for year in range(1990,2021):
		categories.add(BASE_YEAR_S.format(year, year))
	
	categories.add("/search?category=11010200&search_word=&rankBy=release_date(int)%3Aascending&restrict[]=release_year=[2021,*]")
	categories.update(r.html.find('.facet-price')[0].links) #price
	categories.add('/search?category=11010200&search_word=&rankBy=release_date%28int%29%3Aascending&restrict[]=price=[0,199]')
	del session

	print("Getting all pages")
	with get_context("spawn").Pool() as pool:
		result = list(tqdm(enumerate(pool.imap_unordered(getCategory, categories)), desc='categories', total=len(categories)))
		pool.close()
		pool.join()
	print("Updating database")
	conn = sqlite3.connect('/var/www/html/db/surugaya.db3', check_same_thread=True)
	#conn.row_factory = sqlite3.Row only needed if checking keys
	conn.execute('PRAGMA encoding="UTF-8";')
	c = conn.cursor()
	new = 0
	updated = 0
	processed = 0
	bad_ids = [186148292, 186101253, 186121887, 186132574]
	
	def isValid(cat):
		if cat is None:
			return false
		if len(cat) < 2 or cat[1] is None or len(cat[1]) == 0:
			logging.warning("Invalid result entry %s", cat)
			return False
		return True
	result = filter(isValid, result)

	for cat in result:
		for props in cat[1][0]:
			if props[0] in bad_ids:
				continue
			c.execute('SELECT * FROM items WHERE `productid` = ?', [props[0]])
			row = c.fetchone()
			if row is None:
				c.execute('INSERT INTO items VALUES (?,?,?,?,?,?,?,?,?)', props)
				c.execute('INSERT INTO changes (`type`, `from`, `to`, `productid`) VALUES (?,?,?,?)', [0, None, props[1], props[0]])
				new += 1
			else:
				update = False
				for p in range(1, len(props)):
					if props[p] != row[p]: #item with dupe search
						c.execute('INSERT INTO changes (`type`, `from`, `to`, `productid`) VALUES (?,?,?,?)', [p, row[p], props[p], props[0]])
						update = True
				if update:
					c.execute('UPDATE items SET `name` = ?, `circle` = ?, `price` = ?, `image` = ?, `release` = ?, `condition` = ?, `status` = ?, `timesale` = ? WHERE `productid` = ?', props[1:] + [props[0]])
					updated += 1
			processed += 1
		conn.commit()
	conn.close()
	print("Done")
	print("Requested", sum(cat[1][2] for cat in result), "pages, returned", sum(cat[1][3] for cat in result), "pages out of a possible", sum(cat[1][1] for cat in result))
	print("Processed", processed, "items -", updated, "updated and", new, "new")
