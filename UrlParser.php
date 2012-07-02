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
	/**
	 * Функция парсит сыслку на видеопортал, и вытягивает оттуда описание видео, название и сам код для вставки.
	 * youtube smotri.com mailru rutube и другие.
	 *
	 * @static
	 *
	 * @param     $url
	 * @param int $width  ширина видео вставляемого в код
	 * @param int $height высота
	 *
	 * @return array|bool
	 */
	public static function videoParser($url, $width = 320, $height = 240)
	{
		if (empty($url)) {
			return false;
		}
		$pageHtml = self::getPage($url, $info);
		if ($info["http_code"] != 200 && $info["http_code"] != 404) {
			return false;
		}
		$headerCharset = array();
		preg_match('/charset=(?P<charset>.+)/', $info['content_type'], $headerCharset);
		$charset = 'utf-8';
		if (isset($headerCharset['charset'])) {
			$charset = trim(strtolower($headerCharset['charset']));
		}
		else {
			$matchesCharset = array();
			preg_match('/<meta [^"]*"content-type" content="[^=]+=(?P<charset>[^"]+)"/mi', $pageHtml, $matchesCharset);
			if (isset($matchesCharset['charset'])) {
				$charset = trim(strtolower($matchesCharset['charset']));
			}
		}
//		$pageHtmlTest = $pageHtml;
//		$winPageHtml = iconv($charset, 'Windows-1251//TRANSLIT//IGNORE', $pageHtml);
//		$pageHtml = iconv('Windows-1251', 'UTF-8//IGNORE', $winPageHtml);
//		$winPageHtml = mb_convert_encoding($pageHtml, 'Windows-1251','auto');
//		$pageHtml = mb_convert_encoding($winPageHtml , 'UTF-8','Windows-1251');
//		$pageHtml = iconv($charset, 'UTF-8', $pageHtml);
		if ($charset != 'utf-8') {
			$pageHtml = iconv($charset, 'UTF-8', $pageHtml);
		}
		$matches = array();
		$match = array(
			'thumbnail'  => '',
			'url'        => '',
			'id'         => '',
			'http'       => '',
			'host'       => $info['url'],
			'title'      => '',
			'description'=> '',
			'video_src'  => '',
			'height'     => $height,
			'width'      => $width,
		);
		preg_match('/\<meta (name="title"|property="og:title") content="?([^"]*)"/s', $pageHtml, $matches['title']);
		preg_match('/\<h1 class="mb20"\>?(.*)\<\/h1\>/', $pageHtml, $matches['mailru_title']);
		//preg_match('/\<h1\>Описание:\<\/h1.\>?(.*)/', $pageHtml, $matches['mailru_description']);
		//preg_match('/\<meta property="og:title" content="(?P<title>.*)"/si', $pageHtml, $matches[1]);

		preg_match('/\<meta (name="description"|property="og:description") content="?([^"]*)"/s', $pageHtml, $matches['description']);
		preg_match('/(link rel="video_src" href=|meta property="og:video" content=)"?(.*)"/', $pageHtml, $matches['video_src']);
		preg_match('/(link rel="image_src" href=|meta property="og:image" content=)"?(.*)"/', $pageHtml, $matches['image_src']);

		preg_match('/smotri\.com.*(id|file)=(.*)/', $url, $matches['url_video_smotri_com']);
		if (isset($matches['mailru_description'][1]) && !empty($matches['mailru_description'][1])) {
			$match['description'] = html_entity_decode($matches['mailru_description'][1]);
		}
		elseif (isset($matches['description'][2])) {
			$match['description'] = html_entity_decode($matches['description'][2]);
		}
		// title
		if (isset($matches['mailru_title'][1]) && !empty($matches['mailru_title'][1])) {
			$match['title'] = html_entity_decode($matches['mailru_title'][1]);
		}
		elseif (isset($matches['title'][2])) {
			$match['title'] = html_entity_decode($matches['title'][2]);
		}
		// video_src
		if (isset($matches['video_src'][2]) && !empty($matches['video_src'][2])) {
			$match['url'] = '<embed src="' . $matches['video_src'][2] . '" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $width . '" height="' . $height . '"></embed>';
			$match['video_src'] = $matches['video_src'][2];
		}
		elseif (isset($matches['url_video_smotri_com'][2])) {
			$match['url'] = '<embed src="http://pics.smotri.com/player.swf?xmlsource=http%3A%2F%2Fpics.smotri.com%2Fcskins%2Fblue%2Fskin_color.xml&xmldatasource=http%3A%2F%2Fpics.smotri.com%2Fskin_ng.xml&file=' . $matches['url_video_smotri_com'][2] . '" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $width . '" height="' . $height . '"></embed>';
			$match['video_src'] = 'http://pics.smotri.com/player.swf?xmlsource=http%3A%2F%2Fpics.smotri.com%2Fcskins%2Fblue%2Fskin_color.xml&xmldatasource=http%3A%2F%2Fpics.smotri.com%2Fskin_ng.xml&file=' . $matches['url_video_smotri_com'][2];
		}
		else {
			return false;
		}
		// image_src
		if (isset($matches['image_src'][2]) && !empty($matches['image_src'][2])) {
			$match['thumbnail'] = $matches['image_src'][2];
		}
		$match['description'] = mb_convert_encoding($match['description'], 'UTF-8', 'auto');
		$match['title'] = mb_convert_encoding($match['title'], 'UTF-8', 'auto');
		return $match;
	}

	/**
	 * Парсинг сайтов. Вытягивает title. И находит первые тексты для описания.
	 *Автоматически конвертирует кодировку, если она не равна utf-8
	 * @static
	 *
	 * @param $url
	 *
	 * @return array|bool
	 */
	static public function siteParser($url)
	{
		if (empty($url)) {
			return array();
		}
		$curl = self::getPage($url, $info);
		if ($info["http_code"] == 200) {
			$headerCharset = array();
			preg_match('/charset=(?P<charset>.+)/', $info['content_type'], $headerCharset);
			$charset = 'utf-8';
			if (isset($headerCharset['charset'])) {
				$charset = trim(strtolower($headerCharset['charset']));
			}
			else {
				$matchesCharset = array();
				preg_match('/<meta [^"]*"content-type" content="[^=]+=(?P<charset>[^"]+)"/mi', $curl, $matchesCharset);
				if (isset($matchesCharset['charset'])) {
					$charset = trim(strtolower($matchesCharset['charset']));
				}
			}
			preg_match('/<title\>(?P<text>[^\<]+)\<\/title\>/m', $curl, $matches);
			preg_match('/<meta name="description" content="(?P<text>[^"]+)"/mi', $curl, $matchesDescr);
			$result = array('title' => '', 'description' => '');

			if (isset($matches['text'])) {
				if ($charset != 'utf-8') {
					$result['title'] = iconv($charset, 'UTF-8', $matches['text']);
				}
				else {
					$result['title'] = $matches['text'];
				}
				$result['title'] = htmlspecialchars_decode($result['title']);
			}
			if (isset($matchesDescr['text'])) {
				if ($charset != 'utf-8') {
					$result['description'] = iconv($charset, 'UTF-8', $matchesDescr['text']);
				}
				else {
					$result['description'] = $matchesDescr['text'];
				}
				$result['description'] = htmlspecialchars_decode($result['description']);
			}

			$parse = strpos($url, '://');
			if ($parse === false) {
				$link['urloriginal'] = 'http://' . $url;
			}
			else {
				$link['urloriginal'] = $url;
			}

			$result['url'] = parse_url($link['urloriginal']);
			$result['urloriginal'] = $link['urloriginal'];
		}
		else {
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
