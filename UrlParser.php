<?php
namespace parser;
/**
 * Created by JetBrains PhpStorm.
 * User: PiVo
 * Date: 02.07.12
 * Time: 23:21
 * To change this template use File | Settings | File Templates.
 */
class UrlParser
{
	static public function urlParser($url)
	{
		if (empty($url))
		{
			return array();
		}
		$curl = self::getPage($url, $info);
		if ($info["http_code"] == 200)
		{
			$headerCharset = array();
			preg_match('/charset=(?P<charset>.+)/', $info['content_type'], $headerCharset);
			$charset = 'utf-8';
			if (isset($headerCharset['charset']))
			{
				$charset = trim(strtolower($headerCharset['charset']));
			}
			else
			{
				$matchesCharset = array();
				preg_match('/<meta [^"]*"content-type" content="[^=]+=(?P<charset>[^"]+)"/mi', $curl, $matchesCharset);
				if (isset($matchesCharset['charset']))
				{
					$charset = trim(strtolower($matchesCharset['charset']));
				}
			}
			preg_match('/<title\>(?P<text>[^\<]+)\<\/title\>/m', $curl, $matches);
			preg_match('/<meta name="description" content="(?P<text>[^"]+)"/mi', $curl, $matchesDescr);
			$result = array('title' => '', 'description' => '');

			if (isset($matches['text']))
			{
				if ($charset != 'utf-8')
				{
					$result['title'] = iconv($charset, 'UTF-8', $matches['text']);
				}
				else
				{
					$result['title'] = $matches['text'];
				}
				$result['title'] = htmlspecialchars_decode($result['title']);
			}
			if (isset($matchesDescr['text']))
			{
				if ($charset != 'utf-8')
				{
					$result['description'] = iconv($charset, 'UTF-8', $matchesDescr['text']);
				}
				else
				{
					$result['description'] = $matchesDescr['text'];
				}
				$result['description'] = htmlspecialchars_decode($result['description']);
			}

			$parse = strpos($url, '://');
			if ($parse === false)
			{
				$link['urloriginal'] = 'http://' . $url;
			}
			else
			{
				$link['urloriginal'] = $url;
			}

			$result['url'] = parse_url($link['urloriginal']);
			$result['urloriginal'] = $link['urloriginal'];
		}
		else
		{
			$result = false;
		}
		return $result;
	}

	protected static function getPage($url, &$info)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/534.10 (KHTML, like Gecko) Chrome/8.0.552.237 Safari/534.10");
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// настройки прокси
//		curl_setopt($ch, CURLOPT_PROXYPORT, 80);
//		curl_setopt($ch, CURLOPT_PROXY, '178.210.79.58');

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		return $result;
	}
}
