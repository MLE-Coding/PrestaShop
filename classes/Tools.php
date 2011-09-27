<?php
/*
* 2007-2011 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2011 PrestaShop SA
*  @version  Release: $Revision: 7521 $
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class ToolsCore
{
	protected static $file_exists_cache = array();
	protected static $_forceCompile;
	protected static $_caching;

	/**
	* Random password generator
	*
	* @param integer $length Desired length (optional)
	* @return string Password
	*/
	public static function passwdGen($length = 8)
	{
		$str = 'abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		for ($i = 0, $passwd = ''; $i < $length; $i++)
			$passwd .= self::substr($str, mt_rand(0, self::strlen($str) - 1), 1);
		return $passwd;
	}

	public static function strReplaceFirst($search, $replace, $subject, $cur=0) {
		return (strpos($subject, $search, $cur))?substr_replace($subject, $replace, (int)strpos($subject, $search, $cur), strlen($search)):$subject;
	}

	/**
	* Redirect user to another page
	*
	* @param string $url Desired URL
	* @param string $baseUri Base URI (optional)
	*/
	public static function redirect($url, $baseUri = __PS_BASE_URI__, Link $link = null)
	{
		if (!$link)
			$link = Context::getContext()->link;
		if (strpos($url, 'http://') === FALSE && strpos($url, 'https://') === FALSE)
		{
			if (strpos($url, $baseUri) !== FALSE && strpos($url, $baseUri) == 0)
				$url = substr($url, strlen($baseUri));
			if (strpos($url, 'index.php?controller=') !== FALSE && strpos($url, 'index.php/') == 0) {
				$url = substr($url, strlen('index.php?controller='));
				if((int)(Configuration::get('PS_REWRITING_SETTINGS') == 1))
					$url = self::strReplaceFirst('&', '?' , $url);
			}

			$explode = explode('?', $url);
			// don't use ssl if url is home page
			// used when logout for example
			$useSSL = !empty($url);
			$url = $link->getPageLink($explode[0], $useSSL);
			if (isset($explode[1]))
				$url .= '?'.$explode[1];
			$baseUri = '';
		}

		if (isset($_SERVER['HTTP_REFERER']) AND ($url == $_SERVER['HTTP_REFERER']))
			header('Location: '.$_SERVER['HTTP_REFERER']);
		else
			header('Location: '.$baseUri.$url);
		exit;
	}

	/**
	* Redirect url wich allready PS_BASE_URI
	*
	* @param string $url Desired URL
	*/
	public static function redirectLink($url)
	{
		if (!preg_match('@^https?://@i', $url))
		{
			if (strpos($url, __PS_BASE_URI__) !== FALSE && strpos($url, __PS_BASE_URI__) == 0)
				$url = substr($url, strlen(__PS_BASE_URI__));
			if (strpos($url, 'index.php?controller=') !== FALSE && strpos($url, 'index.php/') == 0)
				$url = substr($url, strlen('index.php?controller='));
			$explode = explode('?', $url);
			$url = Context::getContext()->link->getPageLink($explode[0]);
			if (isset($explode[1]))
				$url .= '?'.$explode[1];
		}
		header('Location: '.$url);
		exit;
	}

	/**
	* Redirect user to another admin page
	*
	* @param string $url Desired URL
	*/
	public static function redirectAdmin($url)
	{
		header('Location: '.$url);
		exit;
	}

	/**
	 * getProtocol return the set protocol according to configuration (http[s])
	 * @param Boolean true if require ssl
	 * @return String (http|https)
	 */
	public static function getProtocol($use_ssl = null)
	{
		return (!is_null($use_ssl) && $use_ssl ? 'https://' : 'http://');
	}

	/**
	 * getHttpHost return the <b>current</b> host used, with the protocol (http or https) if $http is true
	 * This function should not be used to choose http or https domain name.
	 * Use Tools::getShopDomain() or Tools::getShopDomainSsl instead
	 *
	 * @param boolean $http
	 * @param boolean $entities
	 * @return string host
	 */
	public static function getHttpHost($http = false, $entities = false)
	{
		$host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST']);
		if ($entities)
			$host = htmlspecialchars($host, ENT_COMPAT, 'UTF-8');
		if ($http)
			$host = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$host;
		return $host;
	}

	/**
	 * getShopDomain returns domain name according to configuration and ignoring ssl
	 *
	 * @param boolean $http if true, return domain name with protocol
	 * @param boolean $entities if true,
	 * @return string domain
	 */
	public static function getShopDomain($http = false, $entities = false)
	{
		if (!$domain = ShopUrl::getMainShopDomain())
			$domain = self::getHttpHost();
		if ($entities)
			$domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
		if ($http)
			$domain = 'http://'.$domain;
		return $domain;
	}

	/**
	 * getShopDomainSsl returns domain name according to configuration and depending on ssl activation
	 *
	 * @param boolean $http if true, return domain name with protocol
	 * @param boolean $entities if true,
	 * @return string domain
	 */
	public static function getShopDomainSsl($http = false, $entities = false)
	{
		if (!$domain = ShopUrl::getMainShopDomainSSL())
			$domain = self::getHttpHost();
		if ($entities)
			$domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
		if ($http)
			$domain = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$domain;
		return $domain;
	}

	/**
	* Get the server variable SERVER_NAME
	*
	* @return string server name
	*/
	static function getServerName()
	{
		if (isset($_SERVER['HTTP_X_FORWARDED_SERVER']) AND $_SERVER['HTTP_X_FORWARDED_SERVER'])
			return $_SERVER['HTTP_X_FORWARDED_SERVER'];
		return $_SERVER['SERVER_NAME'];
	}

	/**
	* Get the server variable REMOTE_ADDR, or the first ip of HTTP_X_FORWARDED_FOR (when using proxy)
	*
	* @return string $remote_addr ip of client
	*/
	static function getRemoteAddr()
	{
		// This condition is necessary when using CDN, don't remove it.
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND $_SERVER['HTTP_X_FORWARDED_FOR'] AND (!isset($_SERVER['REMOTE_ADDR']) OR preg_match('/^127\..*/i', trim($_SERVER['REMOTE_ADDR'])) OR preg_match('/^172\.16.*/i', trim($_SERVER['REMOTE_ADDR'])) OR preg_match('/^192\.168\.*/i', trim($_SERVER['REMOTE_ADDR'])) OR preg_match('/^10\..*/i', trim($_SERVER['REMOTE_ADDR']))))
		{
			if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ','))
			{
				$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				return $ips[0];
			}
			else
				return $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	* Check if the current page use SSL connection on not
	*
	* @return bool uses SSL
	*/
	public static function usingSecureMode()
	{
		if (isset($_SERVER['HTTPS']))
			return ($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) == 'on');
		// $_SERVER['SSL'] exists only in some specific configuration
		if (isset($_SERVER['SSL']))
			return ($_SERVER['SSL'] == 1 || strtolower($_SERVER['SSL']) == 'on');

		return false;
		}

	/**
	* Get the current url prefix protocol (https/http)
	*
	* @return string protocol
	*/
	public static function getCurrentUrlProtocolPrefix()
	{
		if(self::usingSecureMode())
			return 'https://';
		else
			return 'http://';
	}

	/**
	* Secure an URL referrer
	*
	* @param string $referrer URL referrer
	* @return secured referrer
	*/
	public static function secureReferrer($referrer)
	{
		if (preg_match('/^http[s]?:\/\/'.self::getServerName().'(:'._PS_SSL_PORT_.')?\/.*$/Ui', $referrer))
			return $referrer;
		return __PS_BASE_URI__;
	}

	/**
	* Get a value from $_POST / $_GET
	* if unavailable, take a default value
	*
	* @param string $key Value key
	* @param mixed $defaultValue (optional)
	* @return mixed Value
	*/
	public static function getValue($key, $defaultValue = false)
	{
	 	if (!isset($key) OR empty($key) OR !is_string($key))
			return false;
		$ret = (isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $defaultValue));

		if (is_string($ret) === true)
			$ret = urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($ret)));
		return !is_string($ret)? $ret : stripslashes($ret);
	}

	public static function getIsset($key)
	{
	 	if (!isset($key) OR empty($key) OR !is_string($key))
			return false;
	 	return isset($_POST[$key]) ? true : (isset($_GET[$key]) ? true : false);
	}

	/**
	* Change language in cookie while clicking on a flag
	*
	* @return string iso code
	*/
	public static function setCookieLanguage($cookie = null)
	{
		if (!$cookie)
			$cookie = Context::getContext()->cookie;
		/* If language does not exist or is disabled, erase it */
		if ($cookie->id_lang)
		{
			$lang = new Language((int)$cookie->id_lang);
			if (!Validate::isLoadedObject($lang) OR !$lang->active OR !$lang->isAssociatedToShop())
				$cookie->id_lang = NULL;
		}

		/* Automatically detect language if not already defined */
		if (!$cookie->id_lang AND isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$array = explode(',', self::strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
			if (self::strlen($array[0]) > 2)
			{
				$tab = explode('-', $array[0]);
				$string = $tab[0];
			}
			else
				$string = $array[0];
			if (Validate::isLanguageIsoCode($string))
			{
				$lang = new Language((int)(Language::getIdByIso($string)));
				if (Validate::isLoadedObject($lang) AND $lang->active)
					$cookie->id_lang = (int)($lang->id);
			}
		}

		/* If language file not present, you must use default language file */
		if (!$cookie->id_lang OR !Validate::isUnsignedId($cookie->id_lang))
			$cookie->id_lang = (int)(Configuration::get('PS_LANG_DEFAULT'));

		$iso = Language::getIsoById((int)$cookie->id_lang);
		@include_once(_PS_THEME_DIR_.'lang/'.$iso.'.php');

		return $iso;
	}

	/**
	 * Set cookie id_lang
	 */
	public static function switchLanguage(Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();
		if ($id_lang = (int)(self::getValue('id_lang')) AND Validate::isUnsignedId($id_lang))
			$context->cookie->id_lang = $id_lang;
	}

	/**
	 * Set cookie currency from POST or default currency
	 *
	 * @return Currency object
	 */
	public static function setCurrency($cookie)
	{
		if (self::isSubmit('SubmitCurrency'))
			if (isset($_POST['id_currency']) AND is_numeric($_POST['id_currency']))
			{
				$currency = Currency::getCurrencyInstance((int)($_POST['id_currency']));
				if (is_object($currency) AND $currency->id AND !$currency->deleted AND $currency->isAssociatedToShop())
					$cookie->id_currency = (int)($currency->id);
			}

		if ((int)$cookie->id_currency)
		{
			$currency = Currency::getCurrencyInstance((int)$cookie->id_currency);
			if (is_object($currency) AND (int)$currency->id AND (int)$currency->deleted != 1 AND $currency->active AND $currency->isAssociatedToShop())
				return $currency;
		}
		$currency = Currency::getCurrencyInstance((int)(Configuration::get('PS_CURRENCY_DEFAULT')));
		if (is_object($currency) AND $currency->id)
			$cookie->id_currency = (int)($currency->id);
		return $currency;
	}

	/**
	* Return price with currency sign for a given product
	*
	* @param float $price Product price
	* @param object $currency Current currency (object, id_currency, NULL => context currency)
	* @return string Price correctly formated (sign, decimal separator...)
	*/
	public static function displayPrice($price, $currency = NULL, $no_utf8 = false, Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();
		if ($currency === NULL)
			$currency = $context->currency;
		// if you modified this function, don't forget to modify the Javascript function formatCurrency (in tools.js)
		elseif (is_int($currency))
			$currency = Currency::getCurrencyInstance((int)($currency));
		$c_char = (is_array($currency) ? $currency['sign'] : $currency->sign);
		$c_format = (is_array($currency) ? $currency['format'] : $currency->format);
		$c_decimals = (is_array($currency) ? (int)($currency['decimals']) : (int)($currency->decimals)) * _PS_PRICE_DISPLAY_PRECISION_;
		$c_blank = (is_array($currency) ? $currency['blank'] : $currency->blank);
		$blank = ($c_blank ? ' ' : '');
		$ret = 0;
		if (($isNegative = ($price < 0)))
			$price *= -1;
		$price = self::ps_round($price, $c_decimals);
		switch ($c_format)
	 	{
	 	 	/* X 0,000.00 */
	 	 	case 1:
				$ret = $c_char.$blank.number_format($price, $c_decimals, '.', ',');
				break;
			/* 0 000,00 X*/
			case 2:
				$ret = number_format($price, $c_decimals, ',', ' ').$blank.$c_char;
				break;
			/* X 0.000,00 */
			case 3:
				$ret = $c_char.$blank.number_format($price, $c_decimals, ',', '.');
				break;
			/* 0,000.00 X */
			case 4:
				$ret = number_format($price, $c_decimals, '.', ',').$blank.$c_char;
				break;
		}
		if ($isNegative)
			$ret = '-'.$ret;
		if ($no_utf8)
			return str_replace('€', chr(128), $ret);
		return $ret;
	}

	public static function displayPriceSmarty($params, &$smarty)
	{
		if (array_key_exists('currency', $params))
		{
			$currency = Currency::getCurrencyInstance((int)($params['currency']));
			if (Validate::isLoadedObject($currency))
				return self::displayPrice($params['price'], $currency, false);
		}
		return self::displayPrice($params['price']);
	}

	/**
	* Return price converted
	*
	* @param float $price Product price
	* @param object $currency Current currency object
	* @param boolean $to_currency convert to currency or from currency to default currency
	*/
	public static function convertPrice($price, $currency = NULL, $to_currency = true, Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();
		if ($currency === NULL)
			$currency = $context->currency;
		elseif (is_numeric($currency))
			$currency = Currency::getCurrencyInstance($currency);

		$c_id = (is_array($currency) ? $currency['id_currency'] : $currency->id);
		$c_rate = (is_array($currency) ? $currency['conversion_rate'] : $currency->conversion_rate);

		if ($c_id != (int)(Configuration::get('PS_CURRENCY_DEFAULT')))
		{
			if ($to_currency)
				$price *= $c_rate;
			else
				$price /= $c_rate;
		}

		return $price;
	}



	/**
	* Display date regarding to language preferences
	*
	* @param array $params Date, format...
	* @param object $smarty Smarty object for language preferences
	* @return string Date
	*/
	public static function dateFormat($params, &$smarty)
	{
		return self::displayDate($params['date'], $smarty->ps_language->id, (isset($params['full']) ? $params['full'] : false));
	}

	/**
	* Display date regarding to language preferences
	*
	* @param string $date Date to display format UNIX
	* @param integer $id_lang Language id
	* @param boolean $full With time or not (optional)
	* @return string Date
	*/
	public static function displayDate($date, $id_lang, $full = false, $separator='-')
	{
	 	if (!$date OR !strtotime($date))
	 		return $date;
		if (!Validate::isDate($date) OR !Validate::isBool($full))
			die (self::displayError('Invalid date'));
	 	$tmpTab = explode($separator, substr($date, 0, 10));
	 	$hour = ' '.substr($date, -8);

		$language = Language::getLanguage((int)($id_lang));
	 	if ($language AND strtolower($language['iso_code']) == 'fr')
	 		return ($tmpTab[2].'-'.$tmpTab[1].'-'.$tmpTab[0].($full ? $hour : ''));
	 	else
	 		return ($tmpTab[0].'-'.$tmpTab[1].'-'.$tmpTab[2].($full ? $hour : ''));
	}

	/**
	* Sanitize a string
	*
	* @param string $string String to sanitize
	* @param boolean $full String contains HTML or not (optional)
	* @return string Sanitized string
	*/
	public static function safeOutput($string, $html = false)
	{
	 	if (!$html)
			$string = @htmlentities(strip_tags($string), ENT_QUOTES, 'utf-8');
		return $string;
	}

	public static function htmlentitiesUTF8($string, $type = ENT_QUOTES)
	{
		if (is_array($string))
			return array_map(array('Tools', 'htmlentitiesUTF8'), $string);
		return htmlentities($string, $type, 'utf-8');
	}

	public static function htmlentitiesDecodeUTF8($string)
	{
		if (is_array($string))
			return array_map(array('Tools', 'htmlentitiesDecodeUTF8'), $string);
		return html_entity_decode($string, ENT_QUOTES, 'utf-8');
	}

	public static function safePostVars()
	{
		$_POST = array_map(array('Tools', 'htmlentitiesUTF8'), $_POST);
	}

	/**
	* Delete directory and subdirectories
	*
	* @param string $dirname Directory name
	*/
	public static function deleteDirectory($dirname, $delete_self = true)
	{
		$dirname = rtrim($dirname, '/').'/';
		$files = scandir($dirname);
		foreach ($files as $file)
			if ($file != '.' AND $file != '..')
			{
				if (is_dir($dirname.$file))
					self::deleteDirectory($dirname.$file, true);
				elseif (file_exists($dirname.$file))
					unlink($dirname.$file);
			}
		if($delete_self)
			rmdir($dirname);
	}

	/**
	* Display an error according to an error code
	*
	* @param string $string Error message
	* @param boolean $htmlentities By default at true for parsing error message with htmlentities
	*/
	public static function displayError($string = 'Fatal error', $htmlentities = true, Context $context = null)
	{
		global $_ERRORS;

		@include_once(_PS_TRANSLATIONS_DIR_.$context->language->iso_code.'/errors.php');

		if (defined('_PS_MODE_DEV_') AND _PS_MODE_DEV_ AND $string == 'Fatal error')
			return ('<pre>'.print_r(debug_backtrace(), true).'</pre>');
		if (!is_array($_ERRORS))
			return str_replace('"', '&quot;', $string);
		$key = md5(str_replace('\'', '\\\'', $string));
		$str = (isset($_ERRORS) AND is_array($_ERRORS) AND key_exists($key, $_ERRORS)) ? ($htmlentities ? htmlentities($_ERRORS[$key], ENT_COMPAT, 'UTF-8') : $_ERRORS[$key]) : $string;
		return str_replace('"', '&quot;', stripslashes($str));
	}

	/**
	 * Display an error with detailed object
	 *
	 * @param mixed $object
	 * @param boolean $kill
	 * @return $object if $kill = false;
	 */
	public static function dieObject($object, $kill = true)
	{
		echo '<xmp style="text-align: left;">';
		print_r($object);
		echo '</xmp><br />';
		if ($kill)
			die('END');
		return $object;
	}

	/**
	* ALIAS OF dieObject() - Display an error with detailed object
	*
	* @param object $object Object to display
	*/
	public static function d($object, $kill = true)
	{
		return (self::dieObject($object, $kill));
	}

	/**
	* ALIAS OF dieObject() - Display an error with detailed object but don't stop the execution
	*
	* @param object $object Object to display
	*/
	public static function p($object)
	{
		return (self::dieObject($object, false));
	}

	/**
	* Check if submit has been posted
	*
	* @param string $submit submit name
	*/
	public static function isSubmit($submit)
	{
		return (
			isset($_POST[$submit]) OR isset($_POST[$submit.'_x']) OR isset($_POST[$submit.'_y'])
			OR isset($_GET[$submit]) OR isset($_GET[$submit.'_x']) OR isset($_GET[$submit.'_y'])
		);
	}

	/**
	* Get meta tages for a given page
	*
	* @param integer $id_lang Language id
	* @return array Meta tags
	*/
	public static function getMetaTags($id_lang, $page_name)
	{
		global $maintenance;

		if (!(isset($maintenance) AND (!in_array(self::getRemoteAddr(), explode(',', Configuration::get('PS_MAINTENANCE_IP'))))))
		{
		 	/* Products specifics meta tags */
			if ($id_product = self::getValue('id_product'))
			{
				$sql = 'SELECT `name`, `meta_title`, `meta_description`, `meta_keywords`, `description_short`
						FROM `'._DB_PREFIX_.'product` p
						LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (pl.`id_product` = p.`id_product`'.Context::getContext()->shop->sqlLang('pl').')
						WHERE pl.id_lang = '.(int)$id_lang.'
							AND pl.id_product = '.(int)$id_product.'
							AND p.active = 1';
				$row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
				if ($row)
				{
					if (empty($row['meta_description']))
						$row['meta_description'] = strip_tags($row['description_short']);
					return self::completeMetaTags($row, $row['name']);
				}
			}

			/* Categories specifics meta tags */
			elseif ($id_category = self::getValue('id_category'))
			{
				$page_number = (int)self::getValue('p');
				$row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
				SELECT `name`, `meta_title`, `meta_description`, `meta_keywords`, `description`
				FROM `'._DB_PREFIX_.'category_lang` cl
				WHERE cl.`id_lang` = '.(int)($id_lang).' AND cl.`id_category` = '.(int)$id_category.Context::getContext()->shop->sqlLang('cl'));
				if ($row)
				{
					if (empty($row['meta_description']))
						$row['meta_description'] = strip_tags($row['description']);

					// Paginate title
					if (!empty($row['meta_title']))
						$row['meta_title'] = $row['meta_title'].(!empty($page_number) ? ' ('.$page_number.')' : '').' - '.Configuration::get('PS_SHOP_NAME');
					else
						$row['meta_title'] = $row['name'].(!empty($page_number) ? ' ('.$page_number.')' : '').' - '.Configuration::get('PS_SHOP_NAME');

					return self::completeMetaTags($row, $row['name']);
				}
			}

			/* Manufacturers specifics meta tags */
			elseif ($id_manufacturer = self::getValue('id_manufacturer'))
			{
				$page_number = (int)self::getValue('p');
				$row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
				SELECT `name`, `meta_title`, `meta_description`, `meta_keywords`
				FROM `'._DB_PREFIX_.'manufacturer_lang` ml
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (ml.`id_manufacturer` = m.`id_manufacturer`)
				WHERE ml.id_lang = '.(int)($id_lang).' AND ml.id_manufacturer = '.(int)($id_manufacturer));
				if ($row)
				{
					if (empty($row['meta_description']))
						$row['meta_description'] = strip_tags($row['meta_description']);
					$row['meta_title'] .= $row['name'] . (!empty($page_number) ? ' ('.$page_number.')' : '');
					$row['meta_title'] .= ' - '.Configuration::get('PS_SHOP_NAME');
					return self::completeMetaTags($row, $row['meta_title']);
				}
			}

			/* Suppliers specifics meta tags */
			elseif ($id_supplier = self::getValue('id_supplier'))
			{
				$row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
				SELECT `name`, `meta_title`, `meta_description`, `meta_keywords`
				FROM `'._DB_PREFIX_.'supplier_lang` sl
				LEFT JOIN `'._DB_PREFIX_.'supplier` s ON (sl.`id_supplier` = s.`id_supplier`)
				WHERE sl.id_lang = '.(int)($id_lang).' AND sl.id_supplier = '.(int)($id_supplier));

				if ($row)
				{
					if (empty($row['meta_description']))
						$row['meta_description'] = strip_tags($row['meta_description']);
					if (!empty($row['meta_title']))
						$row['meta_title'] = $row['meta_title'].' - '.Configuration::get('PS_SHOP_NAME');
					return self::completeMetaTags($row, $row['name']);
				}
			}

			/* CMS specifics meta tags */
			elseif ($id_cms = self::getValue('id_cms'))
			{
				$row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
				SELECT `meta_title`, `meta_description`, `meta_keywords`
				FROM `'._DB_PREFIX_.'cms_lang`
				WHERE id_lang = '.(int)($id_lang).' AND id_cms = '.(int)($id_cms));
				if ($row)
				{
					$row['meta_title'] = $row['meta_title'].' - '.Configuration::get('PS_SHOP_NAME');
					return self::completeMetaTags($row, $row['meta_title']);
				}
			}

			/* CMS category specifics meta tags */
			elseif ($id_cms = self::getValue('id_cms_category'))
			{
				$row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
				SELECT `meta_title`, `meta_description`, `meta_keywords`
				FROM `'._DB_PREFIX_.'cms_category_lang`
				WHERE id_lang = '.(int)($id_lang).' AND id_cms_category = '.(int)($id_cms));
				if ($row)
				{
					$row['meta_title'] = $row['meta_title'].' - '.Configuration::get('PS_SHOP_NAME');
					return self::completeMetaTags($row, $row['meta_title']);
				}
			}
		}

		/* Default meta tags */
		return self::getHomeMetaTags($id_lang, $page_name);
	}

	/**
	* Get meta tags for a given page
	*
	* @param integer $id_lang Language id
	* @return array Meta tags
	*/
	public static function getHomeMetaTags($id_lang, $page_name)
	{
		/* Metas-tags */
		$metas = Meta::getMetaByPage($page_name, $id_lang);
		$ret['meta_title'] = (isset($metas['title']) AND $metas['title']) ? $metas['title'].' - '.Configuration::get('PS_SHOP_NAME') : Configuration::get('PS_SHOP_NAME');
		$ret['meta_description'] = (isset($metas['description']) AND $metas['description']) ? $metas['description'] : '';
		$ret['meta_keywords'] = (isset($metas['keywords']) AND $metas['keywords']) ? $metas['keywords'] :  '';
		return $ret;
	}


	public static function completeMetaTags($metaTags, $defaultValue, Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();

		if (empty($metaTags['meta_title']))
			$metaTags['meta_title'] = $defaultValue.' - '.Configuration::get('PS_SHOP_NAME');
		if (empty($metaTags['meta_description']))
			$metaTags['meta_description'] = Configuration::get('PS_META_DESCRIPTION', $context->language->id) ? Configuration::get('PS_META_DESCRIPTION', $context->language->id) : '';
		if (empty($metaTags['meta_keywords']))
			$metaTags['meta_keywords'] = Configuration::get('PS_META_KEYWORDS', $context->language->id) ? Configuration::get('PS_META_KEYWORDS', $context->language->id) : '';
		return $metaTags;
	}

	/**
	* Encrypt password
	*
	* @param object $object Object to display
	*/
	public static function encrypt($passwd)
	{
		return md5(pSQL(_COOKIE_KEY_.$passwd));
	}

	/**
	* Get token to prevent CSRF
	*
	* @param string $token token to encrypt
	*/
	public static function getToken($page = true, Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();
		if ($page === true)
			return (self::encrypt($context->customer->id.$context->customer->passwd.$_SERVER['SCRIPT_NAME']));
		else
			return (self::encrypt($context->customer->id.$context->customer->passwd.$page));
	}

	/**
	* Encrypt password
	*
	* @param object $object Object to display
	*/
	public static function getAdminToken($string)
	{
		return !empty($string) ? self::encrypt($string) : false;
	}

	public static function getAdminTokenLite($tab, Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();
		return Tools::getAdminToken($tab.(int)Tab::getIdFromClassName($tab).(int)$context->employee->id);
	}

	/**
	* Get the user's journey
	*
	* @param integer $id_category Category ID
	* @param string $path Path end
	* @param boolean $linkOntheLastItem Put or not a link on the current category
	* @param string [optionnal] $categoryType defined what type of categories is used (products or cms)
	*/
	public static function getPath($id_category, $path = '', $linkOntheLastItem = false, $categoryType = 'products', Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();

		$id_category = (int)$id_category;
		if ($id_category == 1)
			return '<span class="navigation_end">'.$path.'</span>';

		$pipe = Configuration::get('PS_NAVIGATION_PIPE');
		if (empty($pipe))
			$pipe = '>';

		$fullPath = '';
		if ($categoryType === 'products')
		{
			$interval = Category::getInterval($id_category);
			$intervalRoot = Category::getInterval($context->shop->getCategory());
			if ($interval)
			{
				$sql = 'SELECT c.id_category, cl.name, cl.link_rewrite
						FROM '._DB_PREFIX_.'category c
						LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = c.id_category'.$context->shop->sqlLang('cl').')
						WHERE c.nleft <= '.$interval['nleft'].'
							AND c.nright >= '.$interval['nright'].'
							AND c.nleft >= '.$intervalRoot['nleft'].'
							AND c.nright <= '.$intervalRoot['nright'].'
							AND cl.id_lang = '.(int)$context->language->id.'
							AND c.active = 1
						ORDER BY c.level_depth ASC';
				$categories = Db::getInstance()->ExecuteS($sql);

				$n = 1;
				$nCategories = (int)sizeof($categories);
				foreach ($categories AS $category)
				{
					$fullPath .=
					(($n < $nCategories OR $linkOntheLastItem) ? '<a href="'.self::safeOutput($context->link->getCategoryLink((int)$category['id_category'], $category['link_rewrite'])).'" title="'.htmlentities($category['name'], ENT_NOQUOTES, 'UTF-8').'">' : '').
					htmlentities($category['name'], ENT_NOQUOTES, 'UTF-8').
					(($n < $nCategories OR $linkOntheLastItem) ? '</a>' : '').
					(($n++ != $nCategories OR !empty($path)) ? '<span class="navigation-pipe">'.$pipe.'</span>' : '');
				}

				return $fullPath.$path;
			}
		}
		else if ($categoryType === 'CMS')
		{
			$category = new CMSCategory($id_category, $context->language->id);
			if (!Validate::isLoadedObject($category))
				die(self::displayError());
			$categoryLink = $context->link->getCMSCategoryLink($category);

			if ($path != $category->name)
				$fullPath .= '<a href="'.self::safeOutput($categoryLink).'">'.htmlentities($category->name, ENT_NOQUOTES, 'UTF-8').'</a><span class="navigation-pipe">'.$pipe.'</span>'.$path;
			else
				$fullPath = ($linkOntheLastItem ? '<a href="'.self::safeOutput($categoryLink).'">' : '').htmlentities($path, ENT_NOQUOTES, 'UTF-8').($linkOntheLastItem ? '</a>' : '');

			return self::getPath($category->id_parent, $fullPath, $linkOntheLastItem, $categoryType);
		}
	}

	/**
	* @param string [optionnal] $type_cat defined what type of categories is used (products or cms)
	*/
	public static function getFullPath($id_category, $end, $type_cat = 'products', Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();

		$id_category = (int)$id_category;
		$pipe = (Configuration::get('PS_NAVIGATION_PIPE') ? Configuration::get('PS_NAVIGATION_PIPE') : '>');

		$defaultCategory = 1;
		if ($type_cat === 'products')
		{
			$defaultCategory = $context->shop->getCategory();
			$category = new Category($id_category, $context->language->id);
		}
		else if ($type_cat === 'CMS')
		    $category = new CMSCategory($id_category, $context->language->id);

		if (!Validate::isLoadedObject($category))
			$id_category = $defaultCategory;
		if ($id_category == $defaultCategory)
			return htmlentities($end, ENT_NOQUOTES, 'UTF-8');

		return self::getPath($id_category, $category->name, true, $type_cat).'<span class="navigation-pipe">'.$pipe.'</span> <span class="navigation_product">'.htmlentities($end, ENT_NOQUOTES, 'UTF-8').'</span>';
	}

	/**
	 * Return the friendly url from the provided string
	 *
	 * @param string $str
	 * @param bool $utf8_decode => needs to be marked as deprecated
	 * @return string
	 */
	public static function link_rewrite($str, $utf8_decode = false)
	{
		return self::str2url($str);
	}

	/**
	 * Return a friendly url made from the provided string
	 * If the mbstring library is available, the output is the same as the js function of the same name
	 *
	 * @param string $str
	 * @return string
	 */
	public static function str2url($str)
	{
		if (function_exists('mb_strtolower'))
			$str = mb_strtolower($str, 'utf-8');

		$str = trim($str);
		$str = self::replaceAccentedChars($str);

		// Remove all non-whitelist chars.
		$str = preg_replace('/[^a-zA-Z0-9\s\'\:\/\[\]-]/','', $str);
		$str = preg_replace('/[\s\'\:\/\[\]-]+/',' ', $str);
		$str = preg_replace('/[ ]/','-', $str);
		$str = preg_replace('/[\/]/','-', $str);

		// If it was not possible to lowercase the string with mb_strtolower, we do it after the transformations.
		// This way we lose fewer special chars.
		$str = strtolower($str);

		return $str;
	}

	/**
	 * Replace all accented chars by their equivalent non accented chars.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function replaceAccentedChars($str)
	{
		$str = preg_replace('/[\x{0105}\x{0104}\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}]/u','a', $str);
		$str = preg_replace('/[\x{00E7}\x{010D}\x{0107}\x{0106}]/u','c', $str);
		$str = preg_replace('/[\x{010F}]/u','d', $str);
		$str = preg_replace('/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{011B}\x{0119}\x{0118}]/u','e', $str);
		$str = preg_replace('/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}]/u','i', $str);
		$str = preg_replace('/[\x{0142}\x{0141}\x{013E}\x{013A}]/u','l', $str);
		$str = preg_replace('/[\x{00F1}\x{0148}]/u','n', $str);
		$str = preg_replace('/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}\x{00D3}]/u','o', $str);
		$str = preg_replace('/[\x{0159}\x{0155}]/u','r', $str);
		$str = preg_replace('/[\x{015B}\x{015A}\x{0161}]/u','s', $str);
		$str = preg_replace('/[\x{00DF}]/u','ss', $str);
		$str = preg_replace('/[\x{0165}]/u','t', $str);
		$str = preg_replace('/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{016F}]/u','u', $str);
		$str = preg_replace('/[\x{00FD}\x{00FF}]/u','y', $str);
		$str = preg_replace('/[\x{017C}\x{017A}\x{017B}\x{0179}\x{017E}]/u','z', $str);
		$str = preg_replace('/[\x{00E6}]/u','ae', $str);
		$str = preg_replace('/[\x{0153}]/u','oe', $str);
		return $str;
	}

	/**
	* Truncate strings
	*
	* @param string $str
	* @param integer $maxLen Max length
	* @param string $suffix Suffix optional
	* @return string $str truncated
	*/
	/* CAUTION : Use it only on module hookEvents.
	** For other purposes use the smarty function instead */
	public static function truncate($str, $maxLen, $suffix = '...')
	{
	 	if (self::strlen($str) <= $maxLen)
	 		return $str;
	 	$str = utf8_decode($str);
	 	return (utf8_encode(substr($str, 0, $maxLen - self::strlen($suffix)).$suffix));
	}

	/**
	* Generate date form
	*
	* @param integer $year Year to select
	* @param integer $month Month to select
	* @param integer $day Day to select
	* @return array $tab html data with 3 cells :['days'], ['months'], ['years']
	*
	*/
	public static function dateYears()
	{
		for ($i = date('Y') - 10; $i >= 1900; $i--)
			$tab[] = $i;
		return $tab;
	}

	public static function dateDays()
	{
		for ($i = 1; $i != 32; $i++)
			$tab[] = $i;
		return $tab;
	}

	public static function dateMonths()
	{
		for ($i = 1; $i != 13; $i++)
			$tab[$i] = date('F', mktime(0, 0, 0, $i, date('m'), date('Y')));
		return $tab;
	}

	public static function hourGenerate($hours, $minutes, $seconds)
	{
	    return implode(':', array($hours, $minutes, $seconds));
	}

	public static function dateFrom($date)
	{
		$tab = explode(' ', $date);
		if (!isset($tab[1]))
		    $date .= ' ' . self::hourGenerate(0, 0, 0);
		return $date;
	}

	public static function dateTo($date)
	{
		$tab = explode(' ', $date);
		if (!isset($tab[1]))
		    $date .= ' ' . self::hourGenerate(23, 59, 59);
		return $date;
	}

	static function strtolower($str)
	{
		if (is_array($str))
			return false;
		if (function_exists('mb_strtolower'))
			return mb_strtolower($str, 'utf-8');
		return strtolower($str);
	}

	static function strlen($str, $encoding = 'UTF-8')
	{
		if (is_array($str))
			return false;
		$str = html_entity_decode($str, ENT_COMPAT, 'UTF-8');
		if (function_exists('mb_strlen'))
			return mb_strlen($str, $encoding);
		return strlen($str);
	}

	static function stripslashes($string)
	{
		if (_PS_MAGIC_QUOTES_GPC_)
			$string = stripslashes($string);
		return $string;
	}

	static function strtoupper($str)
	{
		if (is_array($str))
			return false;
		if (function_exists('mb_strtoupper'))
			return mb_strtoupper($str, 'utf-8');
		return strtoupper($str);
	}

	static function substr($str, $start, $length = false, $encoding = 'utf-8')
	{
		if (is_array($str))
			return false;
		if (function_exists('mb_substr'))
			return mb_substr($str, (int)($start), ($length === false ? self::strlen($str) : (int)($length)), $encoding);
		return substr($str, $start, ($length === false ? self::strlen($str) : (int)($length)));
	}

	static function ucfirst($str)
	{
		return self::strtoupper(self::substr($str, 0, 1)).self::substr($str, 1);
	}

	public static function orderbyPrice(&$array, $orderWay)
	{
		foreach($array as &$row)
			$row['price_tmp'] =  Product::getPriceStatic($row['id_product'], true, ((isset($row['id_product_attribute']) AND !empty($row['id_product_attribute'])) ? (int)($row['id_product_attribute']) : NULL), 2);
		if(strtolower($orderWay) == 'desc')
			uasort($array, 'cmpPriceDesc');
		else
			uasort($array, 'cmpPriceAsc');
		foreach($array as &$row)
			unset($row['price_tmp']);
	}

	public static function iconv($from, $to, $string)
	{
		if (function_exists('iconv'))
			return iconv($from, $to.'//TRANSLIT', str_replace('¥', '&yen;', str_replace('£', '&pound;', str_replace('€', '&euro;', $string))));
		return html_entity_decode(htmlentities($string, ENT_NOQUOTES, $from), ENT_NOQUOTES, $to);
	}

	public static function isEmpty($field)
	{
		return ($field === '' OR $field === NULL);
	}

	public static function ps_round($value, $precision = 0)
	{
		$method = (int)(Configuration::get('PS_PRICE_ROUND_MODE'));
		if ($method == PS_ROUND_UP)
			return self::ceilf($value, $precision);
		elseif ($method == PS_ROUND_DOWN)
			return self::floorf($value, $precision);
		return round($value, $precision);
	}

	public static function ceilf($value, $precision = 0)
	{
		$precisionFactor = $precision == 0 ? 1 : pow(10, $precision);
		$tmp = $value * $precisionFactor;
		$tmp2 = (string)$tmp;
		// If the current value has already the desired precision
		if (strpos($tmp2, '.') === false)
			return ($value);
		if ($tmp2[strlen($tmp2) - 1] == 0)
			return $value;
		return ceil($tmp) / $precisionFactor;
	}

	public static function floorf($value, $precision = 0)
	{
		$precisionFactor = $precision == 0 ? 1 : pow(10, $precision);
		$tmp = $value * $precisionFactor;
		$tmp2 = (string)$tmp;
		// If the current value has already the desired precision
		if (strpos($tmp2, '.') === false)
			return ($value);
		if ($tmp2[strlen($tmp2) - 1] == 0)
			return $value;
		return floor($tmp) / $precisionFactor;
	}

	/**
	 * file_exists() wrapper with cache to speedup performance
	 *
	 * @param string $filename File name
	 * @return boolean Cached result of file_exists($filename)
	 */
	public static function file_exists_cache($filename)
	{
		if (!isset(self::$file_exists_cache[$filename]))
			self::$file_exists_cache[$filename] = file_exists($filename);
		return self::$file_exists_cache[$filename];
	}

	public static function file_get_contents($url, $useIncludePath = false, $streamContext = NULL, $curlTimeOut = 5)
    {
		if ($streamContext == NULL)
			$streamContext = @stream_context_create(array('http' => array('timeout' => 5)));

		if (in_array(ini_get('allow_url_fopen'), array('On', 'on', '1')))
			return @file_get_contents($url, $useIncludePath, $streamContext);
		elseif (function_exists('curl_init') && in_array(ini_get('allow_url_fopen'), array('On', 'on', '1')))
		{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $curlTimeOut);
			$content = curl_exec($curl);
			curl_close($curl);
			return $content;
		}
		else
			return false;
    }

	public static function simplexml_load_file($url, $class_name = null)
	{
		if (in_array(ini_get('allow_url_fopen'), array('On', 'on', '1')))
			return simplexml_load_string(Tools::file_get_contents($url), $class_name);
		else
			return false;
	}

	public static function minifyHTML($html_content)
	{
		if (strlen($html_content) > 0)
		{
			//set an alphabetical order for args
			$html_content = preg_replace_callback(
				'/(<[a-zA-Z0-9]+)((\s?[a-zA-Z0-9]+=[\"\\\'][^\"\\\']*[\"\\\']\s?)*)>/'
				,array('Tools', 'minifyHTMLpregCallback')
				,$html_content);

			require_once(_PS_TOOL_DIR_.'minify_html/minify_html.class.php');
			$html_content = str_replace(chr(194) . chr(160), '&nbsp;', $html_content);
			$html_content = Minify_HTML::minify($html_content, array('xhtml', 'cssMinifier', 'jsMinifier'));

			if (Configuration::get('PS_HIGH_HTML_THEME_COMPRESSION'))
			{
				//$html_content = preg_replace('/"([^\>\s"]*)"/i', '$1', $html_content);//FIXME create a js bug
				$html_content = preg_replace('/<!DOCTYPE \w[^\>]*dtd\">/is', '', $html_content);
				$html_content = preg_replace('/\s\>/is', '>', $html_content);
				$html_content = str_replace('</li>', '', $html_content);
				$html_content = str_replace('</dt>', '', $html_content);
				$html_content = str_replace('</dd>', '', $html_content);
				$html_content = str_replace('</head>', '', $html_content);
				$html_content = str_replace('<head>', '', $html_content);
				$html_content = str_replace('</html>', '', $html_content);
				$html_content = str_replace('</body>', '', $html_content);
				//$html_content = str_replace('</p>', '', $html_content);//FIXME doesnt work...
				$html_content = str_replace("</option>\n", '', $html_content);//TODO with bellow
				$html_content = str_replace('</option>', '', $html_content);
				$html_content = str_replace('<script type=text/javascript>', '<script>', $html_content);//Do a better expreg
				$html_content = str_replace("<script>\n", '<script>', $html_content);//Do a better expreg
			}

			return $html_content;
		}
		return false;
	}

	/**
	* Translates a string with underscores into camel case (e.g. first_name -> firstName)
	* @prototype string public static function toCamelCase(string $str[, bool $capitaliseFirstChar = false])
	*/
	public static function toCamelCase($str, $capitaliseFirstChar = false)
	{
		$str = strtolower($str);
		if($capitaliseFirstChar)
			$str = ucfirst($str);
		return preg_replace_callback('/_([a-z])/', create_function('$c', 'return strtoupper($c[1]);'), $str);
	}

	public static function getBrightness($hex)
	{
		$hex = str_replace('#', '', $hex);
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
		return (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
	}
	public static function minifyHTMLpregCallback($preg_matches)
	{
		$args = array();
		preg_match_all('/[a-zA-Z0-9]+=[\"\\\'][^\"\\\']*[\"\\\']/is', $preg_matches[2], $args);
		$args = $args[0];
		sort($args);
		$output = $preg_matches[1].' '.implode(' ', $args).'>';
		return $output;
	}

	public static function packJSinHTML($html_content)
	{
		if (strlen($html_content) > 0)
		{
			$htmlContentCopy = $html_content;
			$html_content = preg_replace_callback(
				'/\\s*(<script\\b[^>]*?>)([\\s\\S]*?)(<\\/script>)\\s*/i'
				,array('Tools', 'packJSinHTMLpregCallback')
				,$html_content);

			// If the string is too big preg_replace return an error
			// In this case, we don't compress the content
			if( preg_last_error() == PREG_BACKTRACK_LIMIT_ERROR ) {
				error_log('ERROR: PREG_BACKTRACK_LIMIT_ERROR in function packJSinHTML');
				return $htmlContentCopy;
			}
			return $html_content;
		}
		return false;
	}

	public static function packJSinHTMLpregCallback($preg_matches)
	{
		$preg_matches[1] = $preg_matches[1].'/* <![CDATA[ */';
		$preg_matches[2] = self::packJS($preg_matches[2]);
		$preg_matches[count($preg_matches)-1] = '/* ]]> */'.$preg_matches[count($preg_matches)-1];
		unset($preg_matches[0]);
		$output = implode('', $preg_matches);
		return $output;
	}


	public static function packJS($js_content)
	{
		if (strlen($js_content) > 0)
		{
			require_once(_PS_TOOL_DIR_.'js_minify/jsmin.php');
			return JSMin::minify($js_content);
		}
		return false;
	}


	public static function parserSQL($sql)
	{
		if (strlen($sql) > 0)
		{
			require_once(_PS_TOOL_DIR_.'parser_sql/parser_sql.php');
			$parser = new parserSql($sql);
			return $parser->parsed;
		}
		return false;
	}

	public static function minifyCSS($css_content, $fileuri = false)
	{
		global $current_css_file;

		$current_css_file = $fileuri;
		if (strlen($css_content) > 0)
		{
			$css_content = preg_replace('#/\*.*?\*/#s', '', $css_content);
			$css_content = preg_replace_callback('#url\((?:\'|")?([^\)\'"]*)(?:\'|")?\)#s',array('Tools', 'replaceByAbsoluteURL'), $css_content);

			$css_content = preg_replace('#\s+#',' ',$css_content);
			$css_content = str_replace("\t", '', $css_content);
			$css_content = str_replace("\n", '', $css_content);
			//$css_content = str_replace('}', "}\n", $css_content);

			$css_content = str_replace('; ', ';', $css_content);
			$css_content = str_replace(': ', ':', $css_content);
			$css_content = str_replace(' {', '{', $css_content);
			$css_content = str_replace('{ ', '{', $css_content);
			$css_content = str_replace(', ', ',', $css_content);
			$css_content = str_replace('} ', '}', $css_content);
			$css_content = str_replace(' }', '}', $css_content);
			$css_content = str_replace(';}', '}', $css_content);
			$css_content = str_replace(':0px', ':0', $css_content);
			$css_content = str_replace(' 0px', ' 0', $css_content);
			$css_content = str_replace(':0em', ':0', $css_content);
			$css_content = str_replace(' 0em', ' 0', $css_content);
			$css_content = str_replace(':0pt', ':0', $css_content);
			$css_content = str_replace(' 0pt', ' 0', $css_content);
			$css_content = str_replace(':0%', ':0', $css_content);
			$css_content = str_replace(' 0%', ' 0', $css_content);

			return trim($css_content);
		}
		return false;
	}

	public static function replaceByAbsoluteURL($matches)
	{
		global $current_css_file;

		$protocol_link = self::getCurrentUrlProtocolPrefix();

		if (array_key_exists(1, $matches))
		{
			$tmp = dirname($current_css_file).'/'.$matches[1];
			return 'url(\''.$protocol_link.self::getMediaServer($tmp).$tmp.'\')';
		}
		return false;
	}

	/**
	 * addJS load a javascript file in the header
	 *
	 * @deprecated as of 1.5 use FrontController->addJS()
	 * @param mixed $js_uri
	 * @return void
	 */
	public static function addJS($js_uri)
	{
		Tools::displayAsDeprecated();
		$context = Context::getContext();
		$context->controller->addJs($js_uri);
	}

	/**
	 * addCSS allows you to add stylesheet at any time.
	 *
	 * @deprecated as of 1.5 use FrontController->addCSS()
	 * @param mixed $css_uri
	 * @param string $css_media_type
	 * @return true
	 */
	public static function addCSS($css_uri, $css_media_type = 'all')
	{
		Tools::displayAsDeprecated();
		$context = Context::getContext();
		$context->controller->addCSS($css_uri, $css_media_type);
	}

	/**
	* Combine Compress and Cache CSS (ccc) calls
	*
	* @param array css_files
	* @return array processed css_files
	*/
	public static function cccCss($css_files) {
		//inits
		$css_files_by_media = array();
		$compressed_css_files = array();
		$compressed_css_files_not_found = array();
		$compressed_css_files_infos = array();
		$protocolLink = self::getCurrentUrlProtocolPrefix();

		// group css files by media
		foreach ($css_files as $filename => $media)
		{
			if (!array_key_exists($media, $css_files_by_media))
				$css_files_by_media[$media] = array();

			$infos = array();
			$infos['uri'] = $filename;
			$url_data = parse_url($filename);
			$infos['path'] = _PS_ROOT_DIR_.self::str_replace_once(__PS_BASE_URI__, '/', $url_data['path']);
			$css_files_by_media[$media]['files'][] = $infos;
			if (!array_key_exists('date', $css_files_by_media[$media]))
				$css_files_by_media[$media]['date'] = 0;
			$css_files_by_media[$media]['date'] = max(
				file_exists($infos['path']) ? filemtime($infos['path']) : 0,
				$css_files_by_media[$media]['date']
			);

			if (!array_key_exists($media, $compressed_css_files_infos))
				$compressed_css_files_infos[$media] = array('key' => '');
			$compressed_css_files_infos[$media]['key'] .= $filename;
		}

		// get compressed css file infos
		foreach ($compressed_css_files_infos as $media => &$info)
		{
			$key = md5($info['key'].$protocolLink);
			$filename = _PS_THEME_DIR_.'cache/'.$key.'_'.$media.'.css';
			$info = array(
				'key' => $key,
				'date' => file_exists($filename) ? filemtime($filename) : 0
			);
		}
		// aggregate and compress css files content, write new caches files
		foreach ($css_files_by_media as $media => $media_infos)
		{
			$cache_filename = _PS_THEME_DIR_.'cache/'.$compressed_css_files_infos[$media]['key'].'_'.$media.'.css';
			if ($media_infos['date'] > $compressed_css_files_infos[$media]['date'])
			{
				$compressed_css_files[$media] = '';
				foreach ($media_infos['files'] as $file_infos)
				{
					if (file_exists($file_infos['path']))
						$compressed_css_files[$media] .= self::minifyCSS(file_get_contents($file_infos['path']), $file_infos['uri']);
					else
						$compressed_css_files_not_found[] = $file_infos['path'];
				}
				if (!empty($compressed_css_files_not_found))
					$content = '/* WARNING ! file(s) not found : "'.
						implode(',', $compressed_css_files_not_found).
						'" */'."\n".$compressed_css_files[$media];
				else
					$content = $compressed_css_files[$media];
				file_put_contents($cache_filename, $content);
				chmod($cache_filename, 0777);
			}
			$compressed_css_files[$media] = $cache_filename;
		}

		// rebuild the original css_files array
		$css_files = array();
		foreach ($compressed_css_files as $media => $filename)
		{
			$url = str_replace(_PS_THEME_DIR_, _THEMES_DIR_._THEME_NAME_.'/', $filename);
			$css_files[$protocolLink.self::getMediaServer($url).$url] = $media;
		}
		return $css_files;
	}


	/**
	* Combine Compress and Cache (ccc) JS calls
	*
	* @param array js_files
	* @return array processed js_files
	*/
	public static function cccJS($js_files) {
		//inits
		$compressed_js_files_not_found = array();
		$js_files_infos = array();
		$js_files_date = 0;
		$compressed_js_file_date = 0;
		$compressed_js_filename = '';
		$js_external_files = array();
		$protocolLink = self::getCurrentUrlProtocolPrefix();

		// get js files infos
		foreach ($js_files as $filename)
		{
			$expr = explode(':', $filename);

			if ($expr[0] == 'http')
				$js_external_files[] = $filename;
			else
			{
				$infos = array();
				$infos['uri'] = $filename;
				$url_data = parse_url($filename);
				$infos['path'] =_PS_ROOT_DIR_.self::str_replace_once(__PS_BASE_URI__, '/', $url_data['path']);
				$js_files_infos[] = $infos;

				$js_files_date = max(
					file_exists($infos['path']) ? filemtime($infos['path']) : 0,
					$js_files_date
				);
				$compressed_js_filename .= $filename;
			}
		}

		// get compressed js file infos
		$compressed_js_filename = md5($compressed_js_filename);

		$compressed_js_path = _PS_THEME_DIR_.'cache/'.$compressed_js_filename.'.js';
		$compressed_js_file_date = file_exists($compressed_js_path) ? filemtime($compressed_js_path) : 0;

		// aggregate and compress js files content, write new caches files
		if ($js_files_date > $compressed_js_file_date)
		{
			$content = '';
			foreach ($js_files_infos as $file_infos)
			{
				if (file_exists($file_infos['path']))
					$content .= file_get_contents($file_infos['path']).';';
				else
					$compressed_js_files_not_found[] = $file_infos['path'];
			}
			$content = self::packJS($content);

			if (!empty($compressed_js_files_not_found))
				$content = '/* WARNING ! file(s) not found : "'.
					implode(',', $compressed_js_files_not_found).
					'" */'."\n".$content;

			file_put_contents($compressed_js_path, $content);
			chmod($compressed_js_path, 0777);
		}

		// rebuild the original js_files array
		$url = str_replace(_PS_ROOT_DIR_.'/', __PS_BASE_URI__, $compressed_js_path);

		return array_merge(array($protocolLink.Tools::getMediaServer($url).$url), $js_external_files);
	}

	private static $_cache_nb_media_servers = null;
	public static function getMediaServer($filename)
	{
		if (self::$_cache_nb_media_servers === null)
		{
			if (_MEDIA_SERVER_1_ == '')
				self::$_cache_nb_media_servers = 0;
			elseif (_MEDIA_SERVER_2_ == '')
				self::$_cache_nb_media_servers = 1;
			elseif (_MEDIA_SERVER_3_ == '')
				self::$_cache_nb_media_servers = 2;
			else
				self::$_cache_nb_media_servers = 3;
		}

		if (self::$_cache_nb_media_servers AND ($id_media_server = (abs(crc32($filename)) % self::$_cache_nb_media_servers + 1)))
			return constant('_MEDIA_SERVER_'.$id_media_server.'_');
		return self::getHttpHost();
	}

	public static function generateHtaccess($path, $rewrite_settings, $cache_control, $specific = '', $disable_multiviews = false)
	{
		// Check current content of .htaccess and save all code outside of prestashop comments
		$specific_before = $specific_after = '';
		if (file_exists($path))
		{
			$content = file_get_contents($path);
			if (preg_match('#^(.*)\# ~~start~~.*\# ~~end~~[^\n]*(.*)$#s', $content, $m))
			{
				$specific_before = $m[1];
				$specific_after = $m[2];
			}
			else
			{
				// For retrocompatibility
				if (preg_match('#\# http://www\.prestashop\.com - http://www\.prestashop\.com/forums\s*(.*)<IfModule mod_rewrite\.c>#si', $content, $m))
					$specific_before = $m[1];
				else
					$specific_before = $content;
			}
		}

		// Write .htaccess data
		if (!$write_fd = @fopen($path, 'w'))
			return false;
		fwrite($write_fd, trim($specific_before)."\n\n");

		$domains = array();
		foreach (ShopUrl::getShopUrls() as $shop_url)
		{
			if (!isset($domains[$shop_url['domain']]))
				$domains[$shop_url['domain']] = array();

			$domains[$shop_url['domain']][] = array(
				'physical' =>	$shop_url['physical_uri'],
				'virtual' =>	$shop_url['virtual_uri'],
			);
		}

		// Write data in .htaccess file
		fwrite($write_fd, "# ~~start~~ Do not remove this comment, Prestashop will keep automatically the code outside this comment when .htaccess will be generated again\n");
		fwrite($write_fd, "# .htaccess automaticaly generated by PrestaShop e-commerce open-source solution\n");
		fwrite($write_fd, "# http://www.prestashop.com - http://www.prestashop.com/forums\n\n");

		// RewriteEngine
		fwrite($write_fd, "<IfModule mod_rewrite.c>\n");

		// Disable multiviews ?
		if ($disable_multiviews)
			fwrite($write_fd, "\n# Disable Multiviews\nOptions -Multiviews\n\n");

		fwrite($write_fd, "RewriteEngine on\n\n");
		foreach ($domains as $domain => $list_uri)
			foreach ($list_uri as $uri)
				// Rewrite virtual multishop uri
				if ($uri['virtual'])
				{
					fwrite($write_fd, 'RewriteCond %{HTTP_HOST} ^'.$domain.'$'."\n");
					fwrite($write_fd, 'RewriteRule ^'.ltrim($uri['virtual'], '/').'(.*) '.$uri['physical']."$1 [L]\n\n");
				}

		// Webservice
		fwrite($write_fd, 'RewriteRule ^api/?(.*)$ '."webservice/dispatcher.php?url=$1 [QSA,L]\n\n");

		if ($rewrite_settings)
		{
			// Compatibility with the old image filesystem
			fwrite($write_fd, "# Images\n");
			if (Configuration::get('PS_LEGACY_IMAGES'))
			{
				fwrite($write_fd, 'RewriteRule ^([a-z0-9]+)\-([a-z0-9]+)(\-[_a-zA-Z0-9-]*)/[_a-zA-Z0-9-]*\.jpg$ '._PS_PROD_IMG_.'$1-$2$3.jpg [L]'."\n");
				fwrite($write_fd, 'RewriteRule ^([0-9]+)\-([0-9]+)/[_a-zA-Z0-9-]*\.jpg$ '._PS_PROD_IMG_.'$1-$2.jpg [L]'."\n");
			}

			// Rewrite product images < 100 millions
			for ($i = 1; $i <= 8; $i++)
			{
				$img_path = $img_name = '';
				for ($j = 1; $j <= $i; $j++)
				{
					$img_path .= '$'.$j.'/';
					$img_name .= '$'.$j;
				}
				$img_name .= '$'.$j;
				fwrite($write_fd, 'RewriteRule ^'.str_repeat('([0-9])', $i).'(\-[_a-zA-Z0-9-]*)?/[_a-zA-Z0-9-]*\.jpg$ '._PS_PROD_IMG_.$img_path.$img_name.".jpg [L]\n");
			}
			fwrite($write_fd, 'RewriteRule ^c/([0-9]+)(\-[_a-zA-Z0-9-]*)/[_a-zA-Z0-9-]*\.jpg$ img/c/$1$2.jpg [L]'."\n");
			fwrite($write_fd, 'RewriteRule ^c/([a-zA-Z-]+)/[a-zA-Z0-9-]+\.jpg$ img/c/$1.jpg [L]'."\n");
		}

		// Redirections to dispatcher
		if ($rewrite_settings)
		{
			fwrite($write_fd, "\n# Dispatcher\n");
			fwrite($write_fd, "RewriteCond %{REQUEST_FILENAME} -s [OR]\n");
			fwrite($write_fd, "RewriteCond %{REQUEST_FILENAME} -l [OR]\n");
			fwrite($write_fd, "RewriteCond %{REQUEST_FILENAME} -d\n");
			fwrite($write_fd, "RewriteRule ^.*$ - [NC,L]\n");
			fwrite($write_fd, "RewriteRule ^.*\$ index.php [NC,L]\n");
		}

		fwrite($write_fd, "</IfModule>\n\n");

		// Cache control
		if ($cache_control)
		{
			$cache_control = "<IfModule mod_expires.c>
	ExpiresActive On
	ExpiresByType image/gif \"access plus 1 month\"
	ExpiresByType image/jpeg \"access plus 1 month\"
	ExpiresByType image/png \"access plus 1 month\"
	ExpiresByType text/css \"access plus 1 week\"
	ExpiresByType text/javascript \"access plus 1 week\"
	ExpiresByType application/javascript \"access plus 1 week\"
	ExpiresByType application/x-javascript \"access plus 1 week\"
	ExpiresByType image/x-icon \"access plus 1 year\"
</IfModule>

FileETag INode MTime Size
<IfModule mod_deflate.c>
	AddOutputFilterByType DEFLATE text/html
	AddOutputFilterByType DEFLATE text/css
	AddOutputFilterByType DEFLATE text/javascript
	AddOutputFilterByType DEFLATE application/javascript
	AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>\n\n";
			fwrite($write_fd, $cache_control);
		}

		fwrite($write_fd, "# ~~end~~ Do not remove this comment, Prestashop will keep automatically the code outside this comment when .htaccess will be generated again\n");
		fwrite($write_fd, "\n\n".trim($specific_after));
		fclose($write_fd);

		Module::hookExec('afterCreateHtaccess');

		return true;
	}

	/**
	 * jsonDecode convert json string to php array / object
	 *
	 * @param string $json
	 * @param boolean $assoc  (since 1.4.2.4) if true, convert to associativ array
	 * @return array
	 */
	public static function jsonDecode($json, $assoc = false)
	{
		if (function_exists('json_decode'))
			return json_decode($json, $assoc);
		else
		{
			include_once(_PS_TOOL_DIR_.'json/json.php');
			$pearJson = new Services_JSON(($assoc) ? SERVICES_JSON_LOOSE_TYPE : 0);
			return $pearJson->decode($json);
		}
	}

	/**
	 * Convert an array to json string
	 *
	 * @param array $data
	 * @return string json
	 */
	public static function jsonEncode($data)
	{
		if (function_exists('json_encode'))
			return json_encode($data);
		else
		{
			include_once(_PS_TOOL_DIR_.'json/json.php');
			$pearJson = new Services_JSON();
			return $pearJson->encode($data);
		}
	}

	/**
	 * Display a warning message indicating that the method is deprecated
	 */
	public static function displayAsDeprecated($message = null)
	{
		if (_PS_DISPLAY_COMPATIBILITY_WARNING_)
		{
			$backtrace = debug_backtrace();
			$callee = next($backtrace);

			if ($message)
				trigger_error($message, E_USER_WARNING);
			else
			{
				trigger_error('Function <strong>'.$callee['function'].'()</strong> is deprecated in <strong>'.$callee['file'].'</strong> on line <strong>'.$callee['line'].'</strong><br />', E_USER_WARNING);
				$message = self::displayError('The function').' '.$callee['function'].' ('.self::displayError('Line').' '.$callee['line'].') '.self::displayError('is deprecated and will be removed in the next major version.');
			}

			Logger::addLog($message, 3, $callee['class']);
		}
	}

	/**
	 * Display a warning message indicating that the parameter is deprecated
	 */
	public static function displayParameterAsDeprecated($parameter)
	{
		$backtrace = debug_backtrace();
		$callee = next($backtrace);
		$error = 'Parameter <strong>'.$parameter.'</strong> in function <strong>'.$callee['function'].'()</strong> is deprecated in <strong>'.$callee['file'].'</strong> on line <strong>'.$callee['Line'].'</strong><br />';
		$message = self::displayError('The parameter').' '.$parameter.' '.self::displayError(' in function ').' '.$callee['function'].' ('.self::displayError('Line').' '.$callee['Line'].') '.self::displayError('is deprecated and will be removed in the next major version.');

		self::throwDeprecated($error, $message, $callee['class']);
	}

	public static function displayFileAsDeprecated()
	{
		$backtrace = debug_backtrace();
		$callee = current($backtrace);
		$error = 'File <strong>'.$callee['file'].'</strong> is deprecated<br />';
		$message = Tools::displayError('The file').' '.$callee['file'].' '.Tools::displayError('is deprecated and will be removed in the next major version.');

		self::throwDeprecated($error, $message, $callee['class']);
	}

	protected static function throwDeprecated($error, $message, $class)
	{
		if (_PS_DISPLAY_COMPATIBILITY_WARNING_)
		{
//			trigger_error($error, E_USER_WARNING);
			Logger::addLog($message, 3, $class);
		}
	}

	public static function enableCache($level = 1, Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();
		$smarty = $context->smarty;
		if (!Configuration::get('PS_SMARTY_CACHE'))
			return;
		if ($smarty->force_compile == 0 AND $smarty->caching == $level)
			return ;
		self::$_forceCompile = (int)($smarty->force_compile);
		self::$_caching = (int)($smarty->caching);
		$smarty->force_compile = 0;
		$smarty->caching = (int)($level);
	}

	public static function restoreCacheSettings(Context $context = null)
	{
		if (!$context)
			$context = Context::getContext();

		if (isset(self::$_forceCompile))
			$context->smarty->force_compile = (int)(self::$_forceCompile);
		if (isset(self::$_caching))
			$context->smarty->caching = (int)(self::$_caching);
	}

	public static function isCallable($function)
	{
		$disabled = explode(',', ini_get('disable_functions'));
		return (!in_array($function, $disabled) AND is_callable($function));
	}

	public static function pRegexp($s, $delim)
	{
		$s = str_replace($delim, '\\'.$delim, $s);
		foreach (array('?', '[', ']', '(', ')', '{', '}', '-', '.', '+', '*', '^', '$') as $char)
			$s = str_replace($char, '\\'.$char, $s);
		return $s;
	}

	public static function str_replace_once($needle , $replace, $haystack)
	{
		$pos = strpos($haystack, $needle);
		if ($pos === false)
			return $haystack;
		return substr_replace($haystack, $replace, $pos, strlen($needle));
	}


	/**
	 * Function property_exists does not exist in PHP < 5.1
	 *
	 * @param object or class $class
	 * @param string $property
	 * @return boolean
	 */
	public static function property_exists($class, $property)
	{
		if (function_exists('property_exists'))
			return property_exists($class, $property);

        if (is_object($class))
            $vars = get_object_vars($class);
        else
            $vars = get_class_vars($class);

        return array_key_exists($property, $vars);
    }

	/**
     * @desc identify the version of php
     * @return string
     */
    public static function checkPhpVersion()
    {
    	$version = null;

    	if(defined('PHP_VERSION'))
    		$version = PHP_VERSION;
    	else
    		$version  = phpversion('');

		//Case management system of ubuntu, php version return 5.2.4-2ubuntu5.2
    	if(strpos($version, '-') !== false )
			$version  = substr($version, 0, strpos($version, '-'));

        return $version;
	}

    /**
     * @desc try to open a zip file in order to check if it's valid
     * @return bool success
     */
	public static function ZipTest($fromFile)
	{
		if (class_exists('ZipArchive', false))
		{
			$zip = new ZipArchive();
			return ($zip->open($fromFile, ZIPARCHIVE::CHECKCONS) === true);
		}
		else
		{
			require_once(dirname(__FILE__).'/../tools/pclzip/pclzip.lib.php');
			$zip = new PclZip($fromFile);
			return ($zip->privCheckFormat() === true);
		}
	}

    /**
     * @desc extract a zip file to the given directory
     * @return bool success
     */
	public static function ZipExtract($fromFile, $toDir)
	{
		if (!file_exists($toDir))
			mkdir($toDir, 0777);
		if (class_exists('ZipArchive', false))
		{
			$zip = new ZipArchive();
			if ($zip->open($fromFile) === true AND $zip->extractTo($toDir) AND $zip->close())
				return true;
			return false;
		}
		else
		{
			require_once(dirname(__FILE__).'/../tools/pclzip/pclzip.lib.php');
			$zip = new PclZip($fromFile);
			$list = $zip->extract(PCLZIP_OPT_PATH, $toDir);
			foreach ($list as $extractedFile)
				if ($extractedFile['status'] != 'ok')
					return false;
			return true;
		}
	}

	/**
	 * Get products order field name for queries.
	 *
	 * @param string $type by|way
	 * @param string $value If no index given, use default order from admin -> pref -> products
	 */
	public static function getProductsOrder($type, $value = null, $prefix = false)
	{
		switch ($type)
		{
			case 'by' :
				$orderByPrefix = '';
				if($prefix) {
					if ($value == 'id_product' || $value == 'date_add' || $value == 'price')
						$orderByPrefix = 'p.';
					elseif ($value == 'name')
						$orderByPrefix = 'pl.';
					elseif ($value == 'manufacturer')
						$orderByPrefix = 'm.';
					elseif ($value == 'position' || empty($value))
						$orderByPrefix = 'cp.';
				}

				$value = (is_null($value) || $value === false || $value === '') ? (int)Configuration::get('PS_PRODUCTS_ORDER_BY') : $value;
				$list = array(0 => 'name', 1 => 'price', 2 => 'date_add', 3 => 'date_upd', 4 => 'position', 5 => 'manufacturer_name', 6 => 'quantity');
				return $orderByPrefix.((isset($list[$value])) ? $list[$value] : ((in_array($value, $list)) ? $value : 'position'));
			break;

			case 'way' :
				$value = (is_null($value) || $value === false || $value === '') ? (int)Configuration::get('PS_PRODUCTS_ORDER_WAY') : $value;
				$list = array(0 => 'asc', 1 => 'desc');
				return ((isset($list[$value])) ? $list[$value] : ((in_array($value, $list)) ? $value : 'asc'));
			break;
		}
	}

	/**
	 * Convert a shorthand byte value from a PHP configuration directive to an integer value
	 * @param string $value value to convert
	 * @return int
	 */
	public static function convertBytes($value)
	{
		if (is_numeric($value))
		{
			return $value;
		}
		else
		{
			$value_length = strlen($value);
			$qty = substr($value, 0, $value_length - 1 );
			$unit = strtolower(substr($value, $value_length - 1));
			switch ($unit)
			{
				case 'k':
					$qty *= 1024;
					break;
				case 'm':
					$qty *= 1048576;
					break;
				case 'g':
					$qty *= 1073741824;
					break;
			}
			return $qty;
		}
	}

	public static function display404Error()
	{
		header('HTTP/1.1 404 Not Found');
		header('Status: 404 Not Found');
		include(dirname(__FILE__).'/../404.php');
		die;
	}

	/**
	 * Concat $begin and $end, add ? or & between strings
	 *
	 * @since 1.5.0
	 * @param string $begin
	 * @param string $end
	 * @return string
	 */
	public static function url($begin, $end)
	{
		return $begin.((strpos($begin, '?') !== false) ? '&' : '?').$end;
	}

	/**
	 * Display error and dies or silently log the error.
	 *
	 * @param string $msg
	 * @param bool $die
	 * @return success of logging
	 */
	public static function dieOrLog($msg, $die = true)
	{
		if ($die || (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_))
			die($msg);
		return Logger::addLog($msg);
	}

	/**
	 * Convert \n and \r\n and \r to <br />
	 *
	 * @param string $string String to transform
	 * @return string New string
	 */
	public static function nl2br($str)
	{
		return str_replace(array("\r\n", "\r", "\n"), '<br />', $str);
	}

	/**
	 * Clear cache for Smarty
	 *
	 * @param objet $smarty
	 */
	 public static function clearCache($smarty)
	 {
		$smarty->clearAllCache();
	}

	/**
	 * getMemoryLimit allow to get the memory limit in octet
	 *
	 * @since 1.4.5.0
	 * @return int the memory limit value in octet
	 */
	public static function getMemoryLimit()
	{
		$memory_limit = @ini_get('memory_limit');

		if (preg_match('/[0-9]+k/i', $memory_limit))
			return 1024 * (int)$memory_limit;

		if (preg_match('/[0-9]+m/i', $memory_limit))
			return 1024 * 1024 * (int)$memory_limit;

		if (preg_match('/[0-9]+g/i', $memory_limit))
			return 1024 * 1024 * 1024 * (int)$memory_limit;

		return $memory_limit;
	}

	/**
	 *
	 * @return bool true if the server use 64bit arch
	 */
	public static function isX86_64arch()
	{
		return (PHP_INT_MAX == '9223372036854775807');
	}

	/**
	 * Get max file upload size considering server settings and optional max value
	 *
	 * @param int $max_size optional max file size
	 * @return int max file size in bytes
	 */
	public static function getMaxUploadSize($max_size = 0)
	{
		$post_max_size = self::convertBytes(ini_get('post_max_size'));
		$upload_max_filesize = self::convertBytes(ini_get('upload_max_filesize'));
		if ($max_size > 0)
			$result = min($post_max_size, $upload_max_filesize, $max_size);
		else
			$result = min($post_max_size, $upload_max_filesize);
		return $result;
	}
}

/**
* Compare 2 prices to sort products
*
* @param float $a
* @param float $b
* @return integer
*/
/* Externalized because of a bug in PHP 5.1.6 when inside an object */
function cmpPriceAsc($a,$b)
{
	if ((float)($a['price_tmp']) < (float)($b['price_tmp']))
		return (-1);
	elseif ((float)($a['price_tmp']) > (float)($b['price_tmp']))
		return (1);
	return (0);
}

function cmpPriceDesc($a,$b)
{
	if ((float)($a['price_tmp']) < (float)($b['price_tmp']))
		return (1);
	elseif ((float)($a['price_tmp']) > (float)($b['price_tmp']))
		return (-1);
	return (0);
}

