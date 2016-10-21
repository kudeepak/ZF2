<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class MenuController extends AbstractActionController
{
    
    public function listAction()
    {
        return new ViewModel();
    }
    
    public function manageAction()
    {
        return new ViewModel();
    }
}