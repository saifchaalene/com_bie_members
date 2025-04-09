<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Bie_members
 * @author     Tasos Triantis <tasos.tr@gmail.com>
 * @copyright  2025 Tasos Triantis
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Combiemembers\Component\Bie_members\Administrator\Model;
// No direct access.
defined('_JEXEC') or die;
require_once JPATH_ADMINISTRATOR . '/components/com_civicrm/civicrm/vendor/autoload.php';

use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\Database\ParameterType;
use \Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Filesystem\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersUtils;
use Combiemembers\Component\Bie_members\Administrator\Helper\Bie_membersHelper;



/**
 * Methods supporting a list of Membersdelegates records.
 *
 * @since  1.0.0
 */
class MembersdelegatesModel extends ListModel
{
	/**
	* Constructor.
	*
	* @param   array  $config  An optional associative array of configuration settings.
	*
	* @see        JController
	* @since      1.6
	*/
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'state', 'a.state',
				'ordering', 'a.ordering',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'country_id', 'a.country_id',
				'gender_id', 'a.gender_id',
				'contact_id', 'a.contact_id',
				'prefix', 'a.prefix',
				'first_name', 'a.first_name',
				'last_name', 'a.last_name',
				'gender', 'a.gender',
				'organisation', 'a.organisation',
				'group', 'a.group',
				'preferred_language', 'a.preferred_language',
				'date_of_announce', 'a.date_of_announce',
				'job_title', 'a.job_title',
				'primary_email', 'a.primary_email',
				'secondary_email', 'a.secondary_email',
				'city', 'a.city',
				'street_address', 'a.street_address',
				'supplemental_address_1', 'a.supplemental_address_1',
				'supplemental_address_2', 'a.supplemental_address_2',
				'postal_code', 'a.postal_code',
				'country', 'a.country',
				'phone', 'a.phone',
				'mobile_phone', 'a.mobile_phone',
				'website', 'a.website',
				'facebook', 'a.facebook',
				'twitter', 'a.twitter',
				'notes', 'a.notes',
				'id', 'a.`id`',
				'name_en', 'a.`name_en`',
				'name_fr', 'a.`name_fr`',
				'start_date', 'a.`start_date`',
				'end_date', 'a.`end_date`',
				'type_id', 'a.`type_id`',

				
			);
		}

		parent::__construct($config);
	}


	

	

	

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function populateState($ordering = null, $direction = null): void
	{
		// Initialise variables.
		$app = Factory::getApplication();
	
		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
	
		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);
	
		// Filtering prefix
		$this->setState('filter.prefix', $app->getUserStateFromRequest($this->context . '.filter.prefix', 'filter_prefix', '', 'string'));
	
		// Filtering gender_id
		$this->setState('filter.gender_id', $app->getUserStateFromRequest($this->context . '.filter.gender_id', 'filter_gender_id', '', 'string'));
	
		// Filtering type
		$this->setState('filter.type', $app->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', '', 'string'));
	
		// Filtering country
		$this->setState('filter.country', $app->getUserStateFromRequest($this->context . '.filter.country', 'filter_country', '', 'string'));
	
		// Filtering newsletter_type
		$this->setState('filter.newsletter_type', $app->getUserStateFromRequest($this->context . '.filter.newsletter_type', 'filter_newsletter_type', '', 'string'));
	
		// Load the parameters
		$params = \Joomla\CMS\Component\ComponentHelper::getParams('com_bie_members');
		$this->setState('params', $params);
	
		// Default list ordering
		parent::populateState('a.country', 'ASC');
	}
	

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string A store id.
	 *
	 * @since   1.0.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		
		return parent::getStoreId($id);
		
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   1.0.0
	 */
	
	protected function getListQuery()
{
	$app  = Factory::getApplication();
	$db    = $this->getDbo();
	$query = $db->getQuery(true);

	// Select the required fields from the table.
	$query->select('DISTINCT a.*')
	      ->from($db->quoteName('civicrm_delegates', 'a'));

	// Filter by search in multiple fields
	$search = $this->getState('filter.search');

	if (!empty($search))
	{
		if (stripos($search, 'id:') === 0)
		{
			$query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
		}
		else
		{
			$search = $db->quote('%' . $db->escape($search, true) . '%');
			$query->where(
				$db->quoteName('a.country') . ' LIKE ' . $search .
				' OR ' . $db->quoteName('a.first_name') . ' LIKE ' . $search .
				' OR ' . $db->quoteName('a.last_name') . ' LIKE ' . $search .
				' OR ' . $db->quoteName('a.gender_id') . ' LIKE ' . $search
			);
		}
	}

	// Filtering gender_id
	$filter_gender_id = $this->state->get("filter.gender_id");
	if ($filter_gender_id)
	{
		$query->where($db->quoteName('a.gender_id') . ' = ' . $db->quote($filter_gender_id));
	}

	// Filtering type
	$filter_type = $this->state->get("filter.type");
	if ($filter_type && $filter_type < 3)
	{
		$query->where($db->quoteName('a.type') . ' = ' . $db->quote($filter_type));
	}

	// Filtering country
	$filter_country = $this->state->get("filter.country");
	if ($filter_country)
	{
		$query->where($db->quoteName('a.country_id') . ' = ' . $db->quote($filter_country));
	}

	// Filtering newsletter subscription status
	$filter_newsletter_type = $this->state->get("filter.newsletter_type");
	if ($filter_newsletter_type == 1 || $filter_newsletter_type == 2)
	{
		$status = ($filter_newsletter_type == 2) ? 0 : 1;
		$query->join('INNER', $db->quoteName('civicrm_contact_newsletter', 'n') . ' ON n.contact_id = a.id')
		      ->where($db->quoteName('n.status') . ' = ' . $db->quote($status));
	}
	elseif ($filter_newsletter_type == 3)
	{
		$query->join('LEFT', $db->quoteName('civicrm_contact_newsletter', 'n') . ' ON n.contact_id = a.id')
		      ->where($db->quoteName('n.status') . ' IS NULL');
	}
	elseif ($filter_newsletter_type == 4)
	{
		$query->join('LEFT', $db->quoteName('civicrm_contact_newsletter', 'n') . ' ON n.contact_id = a.id')
		      ->where($db->quoteName('n.status') . ' IS NOT NULL');
	}

	// Add the list ordering clause.
	$orderCol  = $this->state->get('list.ordering');
	$orderDirn = $this->state->get('list.direction');

	if ($orderCol && $orderDirn)
	{
		$query->order($db->escape($orderCol . ' ' . $orderDirn));
	}

	//$app->enqueueMessage((string) $query, 'notice');
	//Factory::getApplication()->enqueueMessage($db->replacePrefix((string) $query), 'notice');

	return $query;
}

	/**
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
	 */
	
public function getItems()
{
    $newsletterids = [
        'en_US' => 1,
        'fr_FR' => 2
    ];

    $items = parent::getItems();

    foreach ($items as $oneItem)
    {
        $oneItem->gender_id = Text::_('COM_BIE_MEMBERS_DELEGATES_GENDER_ID_OPTION_' . strtoupper((string) $oneItem->gender_id));
        $oneItem->type = Text::_('COM_BIE_MEMBERS_DELEGATES_TYPE_OPTION_' . strtoupper((string) $oneItem->type));

        $oneItem->view_url = Route::_('index.php?option=com_civicrm&task=civicrm.contact.view&reset=1&tmpl=popup&cid=' . (int) $oneItem->id);
        $oneItem->edit_url = Route::_('index.php?option=com_civicrm&task=civicrm.contact.view&reset=1&tmpl=component&cid=' . (int) $oneItem->id);
        $oneItem->comment_url = Route::_('index.php?option=com_civicrm&task=civicrm.contact.view.note&cid=' . (int) $oneItem->id . '&action=add&tmpl=popup');

        $oneItem->start_date = $this->dateformat($oneItem->start_date);
        $oneItem->end_date = $this->dateformat($oneItem->end_date);

        $oneItem->notes_url = "";
        $oneItem->notes = $this->getNumNotes((int) $oneItem->id);
        $oneItem->mails = $this->getMails((int) $oneItem->id);
        $oneItem->phones = $this->getPhones((int) $oneItem->id);

        $prefix = "label_" . $oneItem->preferred_language;
        $oneItem->prefix = $oneItem->$prefix ?? '';
        $oneItem->fullname = trim($oneItem->prefix . ' ' . $oneItem->first_name . ' ' . $oneItem->last_name);

        $bows_user = Bie_membersUtils::getNewBOWSUser((int) $oneItem->id);
        $oneItem->username = $bows_user->username ?? '';
        $oneItem->mail = $bows_user->email ?? '';
        $oneItem->active = isset($bows_user->block) && $bows_user->block == 0 ? 1 : 0;

        $newsletter_id = $newsletterids[$oneItem->preferred_language] ?? 0;
        $oneItem->isSubscribed = Bie_membersUtils::checkStatusNewsletter($oneItem->id, $newsletter_id);

        if ((int) $oneItem->notes > 0) {
            $oneItem->notes_url = Route::_('index.php?option=com_civicrm&task=civicrm.contact.view.note&reset=1&tmpl=popup&cid=' . (int) $oneItem->id);
        }
    }

    if (!empty($items)) {
       // Factory::getApplication()->enqueueMessage('<pre>' . print_r($items[0], true) . '</pre>', 'notice');
    }

    return $items;
}

	function countTotalItems() {
        $db = $this->getDBO();
        $query = $this->getListQuery();
        $db->setQuery($query);
        $items = $db->loadObjectList();
        
        return count($items);
        
     }     
	 function countMembers() {

        $db = $this->getDBO();
        $query = $db->getQuery(true);        
	$query->select('count(a.id)')
              ->from('`civicrm_member_states` AS a')
              ->where("a.`type_id` = '1'");
        $db->setQuery($query);                
        return $db->loadResult();        
     }   
	 function countMembersWithVotingRight() {
        $db = $this->getDBO();
        $query = $db->getQuery(true);        
	$query->select('count(a.id)')
              ->from('`civicrm_member_states` AS a')
              ->where("a.`type_id` = '1'")
              ->where("a.`right_vote` = '1'");  
        $db->setQuery($query);                
        return $db->loadResult();        
     }           
	
      
	 function dateformat($date)
	 {
		 if (!is_string($date) || empty($date)) {
			 return $date;
		 }
	 
		 $d = explode("-", $date);
		 if (count($d) === 3) {
			 return $d[2] . "/" . $d[1] . "/" . $d[0];
		 }
	 
		 return $date;
	 }
	 
		 
	function getNumNotes($cID) {
        
        $db = $this->getDBO();
        $query = $db->getQuery(true);
        $query
            ->select('COUNT(*)')
            ->from($db->quoteName('civicrm_note'))
            ->where($db->quoteName('entity_id') . ' = '. $db->quote($cID))
            ->where($db->quoteName('entity_table') . ' = '. $db->quote('civicrm_contact'));       
        
        $db->setQuery($query);
        $count = $db->loadResult();
        
        return $count;
        
        
    }    
	function getMails($cID,$beautify = true, $separator = "<br/>")  {

        $db = $this->getDBO();
        $query = $db->getQuery(true);
        $query
            ->select('email')
            ->from($db->quoteName('civicrm_email'))
            ->where($db->quoteName('contact_id') . ' = '. $db->quote($cID)); 
        
        $db->setQuery($query);
        $column= $db->loadColumn();
        $arr = array();
        foreach ($column as $item) {
            if ($beautify) {
                $arr[] = Text::sprintf('COM_BIE_MEMBERS_MEMBERSDELEGATES_MAIL',$item,$item);    
            } else {
                $arr[] = $item;
            }
        }
        return implode($separator, $arr);

   }   
   function getPhones($cID,$separator = "<br/>")  {

	$db = $this->getDBO();
	$query = $db->getQuery(true);
	$query
		->select('phone')
		->from($db->quoteName('civicrm_phone'))
		->where($db->quoteName('contact_id') . ' = '. $db->quote($cID)); 
	
	$db->setQuery($query);
	$column= $db->loadColumn();
	return implode($separator, $column);

}   


public function exportxls($pks)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $this->populateState('a.country', 'asc');

    $db = Factory::getContainer()->get('DatabaseDriver');
    $query = $this->getListQuery();
    $db->setQuery($query);
    $items = $db->loadObjectList();

    $final_items = [];
    if (!empty($pks)) {
        foreach ($items as $item) {
            if (in_array($item->id, $pks)) {
                $final_items[] = $item;
            }
        }
    } else {
        $final_items = $items;
    }

    $headers = [
        'A1' => 'COM_BIE_MEMBERS_DELEGATES_ID',
        'B1' => 'COM_BIE_MEMBERS_DELEGATES_COUNTRY',
        'C1' => 'COM_BIE_MEMBERS_DELEGATES_PREFIX',
        'D1' => 'COM_BIE_MEMBERS_DELEGATES_FIRST_NAME',
        'E1' => 'COM_BIE_MEMBERS_DELEGATES_LAST_NAME',
        'F1' => 'COM_BIE_MEMBERS_DELEGATES_GENDER_ID',
        'G1' => 'COM_BIE_MEMBERS_DELEGATES_ROLE',
        'H1' => 'COM_BIE_MEMBERS_DELEGATES_START_DATE',
        'I1' => 'COM_BIE_MEMBERS_DELEGATES_END_DATE',
        'J1' => 'COM_BIE_MEMBERS_DELEGATES_TYPE',
        'K1' => 'COM_BIE_MEMBERS_DELEGATES_MAILS',
        'L1' => 'COM_BIE_MEMBERS_DELEGATES_PHONES',
        'M1' => 'COM_BIE_MEMBERS_DELEGATES_USERNAME',
        'N1' => 'COM_BIE_MEMBERS_DELEGATES_ADDRESS',
        'O1' => 'COM_BIE_MEMBERS_DELEGATES_CITY',
        'P1' => 'COM_BIE_MEMBERS_DELEGATES_POSTAL_CODE',
        'Q1' => 'COM_BIE_MEMBERS_DELEGATES_COUNTRY'
    ];

    foreach ($headers as $cell => $labelKey) {
        $sheet->setCellValue($cell, Text::_($labelKey));
    }

    $cnt = 2;
    foreach ($final_items as $row) {
        $bows_user = Bie_membersUtils::getBOWSUser((int) $row->id);
        $c = $this->getContact($row->id);
        $prefixKey = "label_" . $row->preferred_language;
        $prefix = $row->$prefixKey ?? '';

        $sheet->setCellValue('A' . $cnt, $row->id)
              ->setCellValue('B' . $cnt, $row->country)
              ->setCellValue('C' . $cnt, $prefix)
              ->setCellValue('D' . $cnt, $row->first_name)
              ->setCellValue('E' . $cnt, $row->last_name)
              ->setCellValue('F' . $cnt, $row->gender)
              ->setCellValue('G' . $cnt, $row->job_title)
              ->setCellValue('H' . $cnt, $this->dateformat($row->start_date))
              ->setCellValue('I' . $cnt, $this->dateformat($row->end_date))
              ->setCellValue('J' . $cnt, Text::_('COM_BIE_MEMBERS_DELEGATES_TYPE_OPTION_' . $row->type))
              ->setCellValue('K' . $cnt, $this->getMails((int) $row->id, false, ","))
              ->setCellValue('L' . $cnt, $this->getPhones((int) $row->id, ","))
              ->setCellValue('M' . $cnt, $bows_user->username ?? '')
              ->setCellValue('N' . $cnt, htmlspecialchars($c->street_address ?? ''))
              ->setCellValue('O' . $cnt, htmlspecialchars($c->city ?? ''))
              ->setCellValue('P' . $cnt, htmlspecialchars($c->postal_code ?? ''))
              ->setCellValue('Q' . $cnt, htmlspecialchars($c->country ?? ''));
        $cnt++;
    }

    $sheet->setTitle(Text::_('COM_BIE_MEMBERS_TITLE_DELEGATES'));

    $tmpPath = Factory::getConfig()->get('tmp_path');
    $filename = 'delegates_list_' . time() . '.xlsx';
    $filePath = Path::clean($tmpPath . '/' . $filename);

    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);

    $url = Uri::root() . 'tmp/' . $filename;
   // Factory::getApplication()->enqueueMessage(Text::sprintf('COM_BIE_MEMBERS_DOWNLOAD_FILE', $url), 'notice');
}

public function exportToOutlook(array $pks): void
{
    $this->populateState('a.country', 'asc');

    $db = Factory::getDbo();
    $query = $this->getListQuery();
    $db->setQuery($query);
    $items = $db->loadObjectList();

    $finalItems = [];
    if (!empty($pks)) {
        foreach ($items as $item) {
            if (in_array($item->id, $pks)) {
                $finalItems[] = $item;
            }
        }
    } else {
        $finalItems = $items;
    }

    $lines = [
        [
            "Title", "First Name", "Last Name", "Company", "Job Title",
            "E-mail Address", "E-mail 2 Address", "E-mail 3 Address",
            "Business Phone", "Business Phone 2", "Other Phone",
            "Business Street", "Business City", "Business Postal Code", "Business Country/Region"
        ]
    ];

    foreach ($finalItems as $row) {
        $c = $this->getContact((int) $row->id);

        $prefixKey = "label_" . $row->preferred_language;
        $prefix = $row->$prefixKey ?? '';

        $mails = explode(",", $this->getMails((int) $row->id, false, ","));
        $mail1 = $mails[0] ?? '';
        $mail2 = $mails[1] ?? '';
        $mail3 = $mails[2] ?? '';

        $phones = explode(",", $this->getPhones((int) $row->id, ","));
        $phone1 = $phones[0] ?? '';
        $phone2 = $phones[1] ?? '';
        $phone3 = $phones[2] ?? '';

        $lines[] = [
            $prefix,
            $row->first_name,
            $row->last_name,
            $row->country,
            $row->job_title,
            $mail1,
            $mail2,
            $mail3,
            $phone1,
            $phone2,
            $phone3,
            $c->street_address ?? '',
            $c->city ?? '',
            $c->postal_code ?? '',
            $c->country ?? ''
        ];
    }

    $tmpPath = Factory::getConfig()->get('tmp_path');
    $fileName = 'delegates_outlook_' . time() . '.csv';
    $filePath = Path::clean($tmpPath . '/' . $fileName);

    $fp = fopen($filePath, 'w');
    foreach ($lines as $fields) {
        fputcsv($fp, $fields);
    }
    fclose($fp);

    $url = Uri::root() . 'tmp/' . $fileName;
    Factory::getApplication()->enqueueMessage(Text::sprintf('COM_BIE_MEMBERS_DOWNLOAD_FILE', $url), 'notice');
}


public function getContact($contact_id)
{
    if (!defined('CIVICRM_SETTINGS_PATH')) {
        define('CIVICRM_SETTINGS_PATH', JPATH_ADMINISTRATOR . '/components/com_civicrm/civicrm.settings.php');
    }

    if (!defined('CIVICRM_CORE_PATH')) {
        define('CIVICRM_CORE_PATH', JPATH_ADMINISTRATOR . '/components/com_civicrm/civicrm/');
    }

    require_once CIVICRM_SETTINGS_PATH;
    require_once CIVICRM_CORE_PATH . 'CRM/Core/Config.php';

    \CRM_Core_Config::singleton();

    try {
        $result = civicrm_api3('Contact', 'get', [
            'contact_id' => $contact_id,
            'sequential' => 1,
        ]);

        if (!empty($result['values'][0])) {
            return (object) $result['values'][0]; // Retourne l'objet contact
        }
    } catch (Exception $e) {
        Factory::getApplication()->enqueueMessage('Erreur CiviCRM : ' . $e->getMessage(), 'error');
    }

    return false;
}



}
