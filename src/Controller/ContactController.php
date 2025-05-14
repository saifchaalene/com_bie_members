<?php

namespace Combiemembers\Component\Bie_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Input\Input;

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
}
