<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Language\Text;

jimport('joomla.form.formfield');

/**
 * Supports a value from an external table
 *
 * @since  1.0.0
 */
class MembersdelegatepreferredlanguageField extends \Joomla\CMS\Form\FormField
{
	/**
	 * The form field custom type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'membersdelegatepreferredlanguage';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.0.0
	 */
	protected function getInput()
	{
		$value = $this->value;

		$db    = Factory::getContainer()->get('DatabaseDriver');

		$db->setQuery("SELECT  label_en_US 
FROM civicrm_active_languages
WHERE is_active = 1
ORDER BY label_en_US ASC;
");

		$results = $db->loadObjectList();

		$input_options = 'class="form-select ' . $this->getAttribute('class') . '"';

		$options = array();

		
		// Iterate through all the results
		foreach ($results as $result)
		{
			$options[] = HTMLHelper::_('select.option', $result-> label_en_US, Text::_($result-> label_en_US ));
		}


		// If the value is a string -> Only one result
		if (is_string($value))
		{
			$value = array($value);
		}
		elseif (is_object($value))
		{
			// If the value is an object, let's get its properties.
			$value = get_object_vars($value);
		}

		// If the select is multiple
		if ($this->multiple)
		{
			$input_options .= 'multiple="multiple"';
		}
		else
		{
			array_unshift($options, HTMLHelper::_('select.option', '', ''));
		}

		$html = HTMLHelper::_('select.genericlist', $options, $this->name, $input_options, 'value', 'text', $value, $this->id);

		return $html;
	}

	/**
	 * Wrapper method for getting attributes from the form element
	 *
	 * @param   string  $attr_name  Attribute name
	 * @param   mixed   $default    Optional value to return if attribute not found
	 *
	 * @return  mixed The value of the attribute if it exists, null otherwise
	 */
	public function getAttribute($attr_name, $default = null)
	{
		if (!empty($this->element[$attr_name]))
		{
			return $this->element[$attr_name];
		}
		else
		{
			return $default;
		}
	}
}
