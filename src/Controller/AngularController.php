<?php

namespace Combiemembers\Component\Bie_members\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class AngularController extends BaseController
{
    /**
     * Display the Angular app
     *
     * @return void
     */
    public function display(): void
    {
        // Render the Angular app's HTML file
        $this->getView('angular')->display();
    }
}
