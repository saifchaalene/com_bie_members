<?php

namespace Combiemembers\Component\Bie_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;
use Joomla\Database\ParameterType;
use Combiemembers\Component\Bie_members\Administrator\Model\MembersdelegatesModel;

class ContactController extends BaseController
{
    protected MembersdelegatesModel $model;

    public function __construct(
        array $config = [],
        MVCFactoryInterface $factory = null,
        CMSApplicationInterface $app = null,
        Input $input = null,
        ComponentDispatcherFactoryInterface $dispatcherFactory = null
    ) {
        parent::__construct($config, $factory, $app, $input, $dispatcherFactory);
        $this->model = $this->getModel('Membersdelegates');
    }

    public function getContact(): void
    {
        $this->app->mimeType = 'application/json';
        $this->app->setHeader('Content-Type', $this->app->mimeType . '; charset=' . $this->app->charSet);
        $this->app->sendHeaders();

        $id = $this->input->getInt('id');

        if (!$id) {
            echo new JsonResponse(null, 'Missing contact ID', true);
            $this->app->close();
        }

        $contact = $this->model->getContactDataById($id);

        if ($contact) {
            echo new JsonResponse($contact);
        } else {
            echo new JsonResponse(null, 'Contact not found', true);
        }

        $this->app->close();
    }

   
    public function getIdentityCardUrl(): void
    {
        $contactId = $this->input->getInt('cid');
        $url = self::getSecureIdentityCardUrl($contactId);

        echo new JsonResponse(['success' => true, 'url' => $url]);
        $this->app->close();
    }

    public static function getSecureIdentityCardUrl(int $contactId): ?string
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_civicrm/civicrm.settings.php';
        require_once JPATH_ADMINISTRATOR . '/components/com_civicrm/civicrm/CRM/Core/Config.php';
        \CRM_Core_Config::singleton();

        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'filepname']))
            ->from($db->quoteName('civicrm_contacts_identity_card'))
            ->where($db->quoteName('contact_id') . ' = :contactId')
            ->bind(':contactId', $contactId, ParameterType::INTEGER);

        $db->setQuery($query);
        $row = $db->loadAssoc();

        if (!$row || empty($row['filepname'])) {
            return null;
        }

        $fileId = \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_File', $row['filepname'], 'id', 'uri');
        if (!$fileId) {
            return null;
        }

        try {
            $result = civicrm_api3('Attachment', 'getvalue', [
                'return' => 'url',
                'id' => $fileId,
                'check_permissions' => 0,
            ]);
            return $result;
        } catch (\CiviCRM_API3_Exception $e) {
            \Joomla\CMS\Factory::getApplication()->enqueueMessage('CiviCRM API error: ' . $e->getMessage(), 'error');
            return null;
        }
    }





public function getActivitysByContactId(): void
{
    $this->app->mimeType = 'application/json';
    $this->app->setHeader('Content-Type', $this->app->mimeType . '; charset=' . $this->app->charSet);
    $this->app->sendHeaders();

    $contactId = $this->input->getInt('contact_id');

    if (!$contactId) {
        echo new JsonResponse(null, 'Missing contact_id', true);
        $this->app->close();
    }

    $db = Factory::getDbo();

    try {
        $activityTypeGroupId = (int) $db->setQuery(
            "SELECT id FROM civicrm_option_group WHERE name = 'activity_type'"
        )->loadResult();

        $activityStatusGroupId = (int) $db->setQuery(
            "SELECT id FROM civicrm_option_group WHERE name = 'activity_status'"
        )->loadResult();
    } catch (\RuntimeException $e) {
        echo new JsonResponse(null, 'Error loading option group IDs: ' . $e->getMessage(), true);
        $this->app->close();
    }

    $query = $db->getQuery(true);

    $query
        ->select([
            'a.id AS activity_id',
            'a.subject',
            'a.activity_date_time',
            'a.status_id',
            'a.activity_type_id',
            'ov.label_fr_FR AS activity_type_label',
            'ovs.label_fr_FR AS status_label'
        ])
        ->from($db->quoteName('civicrm_activity_contact', 'ac'))
        ->join('INNER', $db->quoteName('civicrm_activity', 'a') . ' ON a.id = ac.activity_id')
        ->join('LEFT', $db->quoteName('civicrm_option_value', 'ov') . ' ON ov.value = a.activity_type_id AND ov.option_group_id = ' . $activityTypeGroupId)
        ->join('LEFT', $db->quoteName('civicrm_option_value', 'ovs') . ' ON ovs.value = a.status_id AND ovs.option_group_id = ' . $activityStatusGroupId)
        ->where('ac.contact_id = :contactId')
     
        ->order('a.activity_date_time DESC');

    $query->bind(':contactId', $contactId, \Joomla\Database\ParameterType::INTEGER);

   try {
    $db->setQuery($query);
    $results = $db->loadAssocList();
    echo new JsonResponse([
        'total' => count($results),
        'activities' => $results
    ]);
} catch (\RuntimeException $e) {
    echo new JsonResponse(null, 'DB error: ' . $e->getMessage(), true);
}

    $this->app->close();
}

}
