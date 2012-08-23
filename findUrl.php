<?php
/**
 * Класс с одной статик функцией
 */
class findUrl
{
	/** Домен внутренней ссылки. Если оставить пустым, то все ссылки будут внешними */
	const DOMAIN = 'vasya.ru';
	/**
	 * Поиск ссылок в тексте, и подставновка вместо них ссылок с тегами. Так же есть разница между внутренними ссылками и внешними.
	 * @static
	 * @param  string $message // unformatted text
	 * @return string
	 */
	static function findLinkAway($message)
	{
		$edit_link = function($matches)
		{
			if (isset($matches[7]))
			{
				$matches[0] = str_replace('\/', '/', $matches[7]);
			}
			elseif (isset($matches[5]))
			{
				$matches[0] = str_replace('\/', '/', $matches[5]);
			}
			elseif (isset($matches[2]))
			{
				$matches[0] = str_replace('\/', '/', $matches[2]);
			}
			else
			{
				return $matches[0];
			}
			$matches[0] = str_replace('\/', '/', $matches[0]);
			$url = parse_url($matches[0]);
			if ($url)
			{
				$urlString = $url["scheme"] . '://' . $url['host'];
				if (isset($url["path"]))
				{
					$urlString .= $url["path"];
				}
				if (isset($url["query"]))
				{
					$urlString .= '?' . $url["query"];
				}
				if(isset($url['fragment']))
				{
					$urlString .= '#' . $url['fragment'];
				}
				if ($url['host'] != findUrl::DOMAIN)
				{
					return '<a target="_blank" href="' . 'http://' . findUrl::DOMAIN . '/index/away/?url=' . urlencode($urlString) . '">' . $urlString . '</a>';
				}
				else
				{
					return '<a href="' . $urlString . '">' . $urlString . '</a>';
				}
			}
		};
		// Какие знаки что обозначают x5C =  '\', x2F = '/', x28 = '(', x29 = ')', x27 = ', x60 = `, x20 =  ,
		$result = preg_replace_callback(
			'/("(http[s]{0,1}:\/\/([a-zA-Zа-яА-ЯёЁ0-9]+[\w0-9\x5C\x2F\x27\x28\x29\x20\x60\$\-\_\.\+\!\*\,\{\}\|\^\~\[\]\;\?\:\@\&\=\<\>\#\%]+)*)")|(\x27(http[s]{0,1}:\/\/([a-zA-Zа-яА-ЯёЁ0-9]+["\w0-9\x5C\x2F\x28\x29\x60\x20\$\-\_\.\+\!\*\,\{\}\|\^\~\[\]\;\?\:\@\&\=\<\>\#\%]+)*)\x27)|((http[s]{0,1}:\/\/([a-zA-Zа-яА-ЯёЁ0-9]+["\w0-9\x5C\x2F\x27\x28\x29\x60\$\-\_\.\+\!\*\,\{\}\|\^\~\[\]\;\?\:\@\&\=\<\>\#\%]+)*))/u'
			, $edit_link, $message);
		return $result;
	}
}
