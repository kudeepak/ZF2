<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class DashboardController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }
    
    public function someAction() {
        $this->layout('layout/dashboard');
        $viewModel = new ViewModel();
        
        return $viewModel;
    }
}
