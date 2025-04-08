<?php

namespace Joomla\Component\YourComponent\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;
use SimpleXMLElement;
use DateTimeZone;

class JFormFieldMycalendar extends FormField
{
	protected $type = 'Calendar';
	protected $layout = 'joomla.form.field.calendar';

	protected $maxlength;
	protected $format;
	protected $filter;
	protected $minyear;
	protected $maxyear;

	public function __get($name)
	{
		return match ($name) {
			'maxlength', 'format', 'filter', 'timeformat',
			'todaybutton', 'singleheader', 'weeknumbers', 'showtime',
			'filltable', 'minyear', 'maxyear' => $this->$name ?? null,
			default => parent::__get($name),
		};
	}

	public function __set($name, $value): void
	{
		switch ($name) {
			case 'maxlength':
			case 'timeformat':
				$this->$name = (int) $value;
				break;

			case 'todaybutton':
			case 'singleheader':
			case 'weeknumbers':
			case 'showtime':
			case 'filltable':
			case 'format':
			case 'filter':
			case 'minyear':
			case 'maxyear':
				$this->$name = (string) $value;
				break;

			default:
				parent::__set($name, $value);
		}
	}

	public function setup(SimpleXMLElement $element, $value, $group = null): bool
	{
		$return = parent::setup($element, $value, $group);

		if ($return) {
			$this->maxlength    = (int) ($this->element['maxlength'] ?? 45);
			$this->format       = (string) ($this->element['format'] ?? '%Y-%m-%d');
			$this->filter       = (string) ($this->element['filter'] ?? 'USER_UTC');
			$this->todaybutton  = (string) ($this->element['todaybutton'] ?? 'true');
			$this->weeknumbers  = (string) ($this->element['weeknumbers'] ?? 'true');
			$this->showtime     = (string) ($this->element['showtime'] ?? 'false');
			$this->filltable    = (string) ($this->element['filltable'] ?? 'true');
			$this->timeformat   = (int) ($this->element['timeformat'] ?? 24);
			$this->singleheader = (string) ($this->element['singleheader'] ?? 'false');
			$this->minyear      = isset($this->element['minyear']) ? (string) $this->element['minyear'] : null;
			$this->maxyear      = isset($this->element['maxyear']) ? (string) $this->element['maxyear'] : null;

			if ((int) $this->maxyear < 0 || (int) $this->minyear > 0) {
				$this->todaybutton = 'false';
			}
		}

		return $return;
	}

	protected function getInput(): string
	{
		$app = Factory::getApplication();
		$lang = Factory::getLanguage();
		$config = Factory::getConfig();
		$user = Factory::getUser();

		// Format translation
		if (!empty($this->element['translateformat']) && $this->element['translateformat'] !== 'false') {
			$this->format = $this->showtime !== 'false'
				? Text::_('DATE_FORMAT_CALENDAR_DATETIME')
				: Text::_('DATE_FORMAT_CALENDAR_DATE');
		}

		// Convert dd/mm/yyyy to Y-m-d if necessary
		if ($this->format === '%d/%m/%Y' && str_contains($this->value ?? '', '/')) {
			$d = explode('/', (string) $this->value);
			if (count($d) === 3 && $this->value !== '00/00/0000') {
				$this->value = "{$d[2]}-{$d[1]}-{$d[0]}";
			}
		}

		// Timezone filtering
		if (!empty($this->value) && $this->value !== $app->getDbo()->getNullDate()) {
			$timezone = match (strtoupper($this->filter)) {
				'SERVER_UTC' => new DateTimeZone($config->get('offset')),
				'USER_UTC'   => $user->getTimezone(),
				default      => null
			};

			if ($timezone) {
				$date = new Date($this->value, 'UTC');
				$date->setTimezone($timezone);
				$this->value = $date->format('Y-m-d H:i:s');
			}
		}

		// Convert to readable format
		if (!empty($this->value) && strtotime($this->value) !== false) {
			$this->value = (new \DateTime($this->value))->format(str_replace('%', '', $this->format));
		} else {
			$this->value = '';
		}

		return $this->getRenderer($this->layout)->render($this->getLayoutData());
	}

	protected function getLayoutData(): array
	{
		$data = parent::getLayoutData();
		$lang = Factory::getLanguage();
		$doc  = Factory::getDocument();

		$tag       = strtolower($lang->getTag());
		$calendar  = strtolower($lang->getCalendar());
		$direction = strtolower($doc->getDirection());

		// Locale and helper paths
		$helperPath  = "system/fields/calendar-locales/date/{$calendar}/date-helper.min.js";
		$localesPath = "system/fields/calendar-locales/{$tag}.js";

		if (!file_exists(JPATH_ROOT . "/media/{$helperPath}")) {
			$helperPath = 'system/fields/calendar-locales/date/gregorian/date-helper.min.js';
		}
		if (!file_exists(JPATH_ROOT . "/media/{$localesPath}")) {
			$localesPath = 'system/fields/calendar-locales/en.js';
		}

		return array_merge($data, [
			'value'        => $this->value,
			'maxLength'    => $this->maxlength,
			'format'       => $this->format,
			'filter'       => $this->filter,
			'todaybutton'  => $this->todaybutton === 'true',
			'weeknumbers'  => $this->weeknumbers === 'true',
			'showtime'     => $this->showtime === 'true',
			'filltable'    => $this->filltable === 'true',
			'timeformat'   => $this->timeformat,
			'singleheader' => $this->singleheader === 'true',
			'helperPath'   => $helperPath,
			'localesPath'  => $localesPath,
			'minYear'      => $this->minyear,
			'maxYear'      => $this->maxyear,
			'direction'    => $direction,
		]);
	}
}
