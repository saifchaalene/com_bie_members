<?php
namespace Combiemembers\Component\Bie_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;

class UserController extends BaseController
{
    public function getCurrent(): void
    {
        $user = Factory::getApplication()->getIdentity();

        echo new JsonResponse([
            'id'        => $user->id,
            'name'      => $user->name,
            'username'  => $user->username,
            'email'     => $user->email,
            'logged_in' => !$user->guest,
        ]);

        Factory::getApplication()->close(); 
    }

    public function allowTask(string $task): bool
    {
        return $task === 'getCurrent' || parent::allowTask($task);
    }
}
