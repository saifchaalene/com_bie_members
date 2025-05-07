<?php

namespace Combiemembers\Component\Bie_members\Administrator\View\Angular;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Uri\Uri;

class HtmlView extends BaseHtmlView
{
    public function display($tpl = null): void
    {
        // Path to the Angular build files
        $base = Uri::root(true) . '/administrator/components/com_bie_members/media/angular/';
        
        // Set the base URL to the view
        $this->set('base', $base); // Use set() instead of assign()
        
        // Render the view
        parent::display($tpl);
    }
}
