<?php
namespace Craft;

/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 */

/**
 * Class DateTime
 *
 * @package craft.app.etc.dates
 */
class DateTime extends \DateTime
{
	const W3C_DATE = 'Y-m-d';
	const MYSQL_DATETIME = 'Y-m-d H:i:s';
	const UTC = 'UTC';
	const DATEFIELD_24HOUR = 'Y-m-d H:i';
	const DATEFIELD_12HOUR = 'Y-m-d h:i A';

	/**
	 * Creates a new \Craft\DateTime object (rather than \DateTime)
	 *
	 * @param string $format
	 * @param string $time
	 * @param mixed  $timezone The timezone the string is set in (defaults to UTC).
	 * @return DateTime
	 */
	public static function createFromFormat($format, $time, $timezone = null)
	{
		if (!$timezone)
		{
			// Default to UTC
			$timezone = static::UTC;
		}

		if (is_string($timezone))
		{
			$timezone = new \DateTimeZone($timezone);
		}

		$dateTime = parent::createFromFormat($format, $time, $timezone);

		if ($dateTime)
		{
			$timeStamp = $dateTime->getTimestamp();

			if (DateTimeHelper::isValidTimeStamp($timeStamp))
			{
				return new DateTime('@'.$dateTime->getTimestamp());
			}
		}

		return false;
	}

	/**
	 * Creates a new DateTime object from a string.
	 *
	 * Supports the following formats:
	 *
	 *  - An array of the date and time in the current locale's short formats
	 *  - All W3C date and time formats (http://www.w3.org/TR/NOTE-datetime)
	 *  - MySQL DATE and DATETIME formats (http://dev.mysql.com/doc/refman/5.1/en/datetime.html)
	 *  - Relaxed versions of W3C and MySQL formats (single-digit months, days, and hours)
	 *  - Unix timestamps
	 *
	 * @param string|array $date
	 * @param stirng|null  $timezone The PHP timezone identifier, if not specified in $date. Defaults to UTC. (See http://php.net/manual/en/timezones.php)
	 * @return DateTime|null|false
	 */
	public static function createFromString($date, $timezone = null)
	{
		// Was this a date/time-picker?
		if (is_array($date) && (isset($date['date']) || isset($date['time'])))
		{
			$dt = $date;

			if (!$timezone)
			{
				$timezone = craft()->getTimeZone();
			}

			if (empty($dt['date']) && empty($dt['time']))
			{
				return null;
			}

			$localeData = craft()->i18n->getLocaleData(craft()->language);
			$dateFormatter = $localeData->getDateFormatter();

			if (!empty($dt['date']))
			{
				$date = $dt['date'];
				$format = $dateFormatter->getDatepickerPhpFormat();

				// Check for a two-digit year
				$altFormat = str_replace('Y', 'y', $format);
				if (static::createFromFormat($altFormat, $date) !== false)
				{
					$format = $altFormat;
				}
			}
			else
			{
				$date = '';
				$format = '';

				// Default to the current date
				$current = new DateTime('now', new \DateTimeZone($timezone));
				$date .= $current->month().'/'.$current->day().'/'.$current->year();
				$format .= 'n/j/Y';
			}

			if (!empty($dt['time']))
			{
				// Replace the localized "AM" and "PM"
				$localeData = craft()->i18n->getLocaleData();
				$dt['time'] = str_replace(array($localeData->getAMName(), $localeData->getPMName()), array('AM', 'PM'), $dt['time']);

				$date .= ' '.$dt['time'];
				$format .= ' '.$dateFormatter->getTimepickerPhpFormat();
			}
		}
		else
		{
			$date = trim((string) $date);

			if (preg_match('/^
				(?P<year>\d{4})                                            # YYYY (four digit year)
				(?:
					-(?P<mon>\d\d?)                                        # -M or -MM (one or two digit month)
					(?:
						-(?P<day>\d\d?)                                    # -D or -DD (one or two digit day)
						(?:
							[T\ ](?P<hour>\d\d?)\:(?P<min>\d\d)            # [T or space]hh:mm (one or two digit hour and two digit minute)
							(?:
								\:(?P<sec>\d\d)                            # :ss (two digit second)
								(?:\.\d+)?                                 # .s (decimal fraction of a second -- not supported)
							)?
							(?:[ ]?(?P<ampm>(AM|PM|am|pm))?)?              # An optional space and AM or PM
							(?:Z|(?P<tzd>[+\-]\d\d\:\d\d))?                # Z or [+ or -]hh:ss (UTC or a timezone offset)
						)?
					)?
				)?$/x', $date, $m))
			{
				$format = 'Y-m-d H:i:s';

				$date = $m['year'] .
					'-'.(!empty($m['mon'])  ? sprintf('%02d', $m['mon'])  : '01') .
					'-'.(!empty($m['day'])  ? sprintf('%02d', $m['day'])  : '01') .
					' '.(!empty($m['hour']) ? sprintf('%02d', $m['hour']) : '00') .
					':'.(!empty($m['min'])  ? $m['min']                   : '00') .
					':'.(!empty($m['sec'])  ? $m['sec']                   : '00');

				if (!empty($m['tzd']))
				{
					$format .= 'P';
					$date   .= $m['tzd'];
				}

				if (!empty($m['ampm']))
				{
					$format .= ' A';
					$date .= ' '.$m['ampm'];
				}
			}
			else if (preg_match('/^\d{10}$/', $date))
			{
				$format = 'U';
			}
			else
			{
				$format = '';
			}
		}

		if ($timezone)
		{
			$format .= ' e';
			$date   .= ' '.$timezone;
		}

		return static::createFromFormat('!'.$format, $date);
	}

	/**
	 * @return string
	 */
	function __toString()
	{
		return $this->format(static::W3C_DATE);
	}

	/**
	 * @param string $format
	 * @param mixed  $timezone The timezone to output the date in (defaults to the current app timezone).
	 * @return string
	 */
	function format($format, $timezone = null)
	{
		if (!$timezone)
		{
			// Default to the current app timezone
			$timezone = craft()->timezone;
		}

		if (is_string($timezone))
		{
			$timezone = new \DateTimeZone($timezone);
		}

		$this->setTimezone($timezone);
		return parent::format($format);
	}

	/**
	 * @return string
	 */
	public function atom()
	{
		return $this->format(static::ATOM, static::UTC);
	}

	/**
	 * @return string
	 */
	public function cookie()
	{
		return $this->format(static::COOKIE, static::UTC);
	}

	/**
	 * @return string
	 */
	public function iso8601()
	{
		return $this->format(static::ISO8601, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc822()
	{
		return $this->format(static::RFC822, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc850()
	{
		return $this->format(static::RFC850, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc1036()
	{
		return $this->format(static::RFC1036, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc1123()
	{
		return $this->format(static::RFC1123, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc2822()
	{
		return $this->format(static::RFC2822, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rfc3339()
	{
		return $this->format(static::RFC3339, static::UTC);
	}

	/**
	 * @return string
	 */
	public function rss()
	{
		return $this->format(static::RSS, static::UTC);
	}

	/**
	 * @return string
	 */
	public function w3c()
	{
		return $this->format(static::W3C);
	}

	/**
	 * @return string
	 */
	public function w3cDate()
	{
		return $this->format(static::W3C_DATE);
	}

	/**
	 * @return string
	 */
	public function mySqlDateTime()
	{
		return $this->format(static::MYSQL_DATETIME);
	}

	/**
	 * @return string
	 */
	public function localeDate()
	{
		$localeData = craft()->i18n->getLocaleData(craft()->language);
		$dateFormatter = $localeData->getDateFormatter();
		$format = $dateFormatter->getDatepickerPhpFormat();
		return $this->format($format);
	}

	/**
	 * @return string
	 */
	public function localeTime()
	{
		$localeData = craft()->i18n->getLocaleData(craft()->language);
		$dateFormatter = $localeData->getDateFormatter();
		$format = $dateFormatter->getTimepickerPhpFormat();
		return $this->format($format);
	}

	/**
	 * @return string
	 */
	public function year()
	{
		return $this->format('Y');
	}

	/**
	 * @return string
	 */
	public function month()
	{
		return $this->format('n');
	}

	/**
	 * @return string
	 */
	public function day()
	{
		return $this->format('j');
	}

	/**
	 * @param \DateTime $datetime2
	 * @param bool      $absolute
	 * @return DateInterval
	 */
	public function diff($datetime2, $absolute = false)
	{
		$interval = parent::diff($datetime2, $absolute);

		// Convert it to a DateInterval in this namespace
		if ($interval instanceof \DateInterval)
		{
			$spec = 'P';

			if ($interval->y) $spec .= $interval->y.'Y';
			if ($interval->m) $spec .= $interval->m.'M';
			if ($interval->d) $spec .= $interval->d.'D';

			if ($interval->h || $interval->i || $interval->s)
			{
				$spec .= 'T';

				if ($interval->h) $spec .= $interval->h.'H';
				if ($interval->i) $spec .= $interval->i.'M';
				if ($interval->s) $spec .= $interval->s.'S';
			}

			// If $spec is P at this point, the interval was less than a second.
			// Accuracy be damned.
			if ($spec === 'P')
			{
				$spec = 'PT0S';
			}

			$newInterval = new DateInterval($spec);
			$newInterval->invert = $interval->invert;

			// Apparently 'days' is a read-only property. Oh well.
			//$newInterval->days = $interval->days;

			return $newInterval;
		}
		else
		{
			return $interval;
		}
	}

	/**
	 * Returns a nicely formatted date string for given Datetime string.
	 *
	 * @return string
	 */
	public function nice()
	{
		return DateTimeHelper::nice($this->getTimestamp());
	}
}
