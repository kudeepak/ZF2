<?php
namespace Login\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Login\Model\Login;

class LoginController extends AbstractActionController
{

    protected $form;

    protected $storage;

    protected $authservice;

    public function getAuthService()
    {
        if (! $this->authservice) {
            $this->authservice = $this->getServiceLocator()->get('AuthService');
        }
        
        return $this->authservice;
    }

    public function getSessionStorage()
    {
        if (! $this->storage) {
            $this->storage = $this->getServiceLocator()->get('Login\Model\AuthStorage');
        }
        
        return $this->storage;
    }

    public function loginAction()
    {        
        // if already login, redirect to success page
        if ($this->getAuthService()->hasIdentity()) {
            return $this->redirect()->toRoute('application');
        }
        
        $config = $this->getServiceLocator()->get('config');
        
        $form = new \Login\Form\LoginForm();
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $form->setData($request->getPost());
            $login = new Login();
            $form->setInputFilter($login->getInputFilter());
            $form->setData($request->getPost());
            if ($form->isValid()) {
                // check authentication...
                $this->getAuthService()
                    ->getAdapter()
                    ->setIdentity($request->getPost('username'))
                    ->setCredential($request->getPost('password'));
                
                $result = $this->getAuthService()->authenticate();
                foreach ($result->getMessages() as $message) {
                    // save message temporary into flashmessenger
                    $this->flashmessenger()->addMessage($message);
                }
                
                if ($result->isValid()) {                    
                    // check if it has rememberMe :
                    if ($request->getPost('rememberme') == 1) {
                        $this->getSessionStorage()->setRememberMe(1, $config['session']['remember_me_seconds']);
                        // set storage again
                        $this->getAuthService()->setStorage($this->getSessionStorage());
                    }
                    
                    $this->getAuthService()
                        ->getStorage()
                        ->write($request->getPost('username'));
                    
                    return $this->redirect()->toRoute('application');
                } else {
                    return $this->redirect()->toRoute('login');
                }
            }
        }
        
        return array(
            'form' => $form,
            'messages' => $this->flashmessenger()->getMessages()
        );
    }

    public function logoutAction()
    {
        $this->getSessionStorage()->forgetMe();
        $this->getAuthService()->clearIdentity();
        $this->flashmessenger()->addMessage("You've been logged out");
        return $this->redirect()->toRoute('login');
    }
}
