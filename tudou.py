import urllib2
import re

output = 'links.txt'
out = open(output, 'ab')

def get_html(url, data=None, headers=None):
	print '[get_html] url: %s, data: %r, headers: %r' % (url, data, headers)
	if headers is None:
		headers = {
			'User-Agent': 'Mymedia Get'
		}

	req = urllib2.Request(url, data, headers)
	handle = urllib2.urlopen(req)
	html = handle.read()
	return html

def get_info(url, data=None, headers=None):
	print '[get_info] url: %s, data: %r, headers: %r' % (url, data, headers)
	if headers is None:
		headers = {
			'User-Agent': 'Mymedia Get'
		}

	req = urllib2.Request(url, data, headers)
	handle = urllib2.urlopen(req)
	info = handle.info()
	return info

def get_url(url, data=None, headers=None):
	print '[get_info] url: %s, data: %r, headers: %r' % (url, data, headers)
	if headers is None:
		headers = {
# 			'User-Agent': 'Mymedia Get'
			'User-Agent': 'Opera/9.64 (Windows NT 5.1; U; en) Presto/2.1.1'
		}

	req = urllib2.Request(url, data, headers)
	handle = urllib2.urlopen(req)
	result = handle.geturl()
# 	info = handle.info()
	return result

def get_tudou(tudou_url):
# 	tudou_url = 'http://www.tudou.com/programs/view/Veq0WIbqwa8/'

	html = get_html(tudou_url)

	pattern = 'embed src="([^"]+)"'
	obj = re.compile(pattern).search(html)
	if obj:
		view_url = obj.group(1)
	else:
		raise

	print 'view_url: %s' % (view_url)
	
	location = get_url(view_url)
	print location


	pattern = 'iid=([0-9]+)'
	obj = re.compile(pattern).search(location)
	if obj:
		iid = obj.group(1)

	cdn_url = 'http://v2.tudou.com/v2/cdn?id=%s' % (iid)
	out.write(cdn_url+'\n\n')
	xml = get_html(cdn_url)

	pattern = '>([^<]+)<'
	links = re.findall(pattern, xml)

	for link in links:
		out.write(link+'\n')

	out.write('\n')

if __name__ == '__main__':
	input = 'input.txt'
	
	for line in open(input, 'rb'):
		line = line.strip()

		if 0 == len(line):
			continue

		if '#' == line[0]:
			continue

		get_tudou(line)



