<?php

namespace Loewenstark\Maileon2\Observer\Predispatch\Newsletter\Manage;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Save implements ObserverInterface
{
    /**
     * @var \Loewenstark\Maileon2\Model\Api
     */
    protected $maileonApi;

    /**
     * @var \Loewenstark\Maileon2\Helper\Data
     */
    protected $maileonHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $response;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;
    
    public function __construct(
        \Loewenstark\Maileon2\Model\Api $maileonApi,
        \Loewenstark\Maileon2\Helper\Data $maileonHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->maileonApi = $maileonApi;
        $this->maileonHelper = $maileonHelper;
        $this->customerSession = $customerSession;
        
        $this->formKeyValidator = $formKeyValidator;
        $this->request = $context->getRequest();
        $this->response = $context->getResponse();
        $this->messageManager = $context->getMessageManager();
        
        $this->responseFactory = $responseFactory;
        $this->url = $url;
    }
    
    public function execute(Observer $observer)
    {
        if ($this->maileonHelper->isActive()) {
            if (!$this->formKeyValidator->validate($this->getRequest())) {
                return $this->_redirect('customer/account/');
            }
            $this->customerSession->setMaileon2NlStatus(null);

            $email = $this->customerSession->getCustomer()->getEmail();
            $success = false;
            if ((boolean)$this->getRequest()->getParam('is_subscribed', false)) {
                if ($this->maileonApi->subscribe($email)) {
                    $success = true;
                    $this->messageManager->addSuccess(__('We saved the subscription.'));
                    $this->customerSession->setMaileon2NlStatus([
                        'timestamp' => (time()+120),
                        'status' => true
                    ]);
                }
            } else {
                if ($this->maileonApi->unsubscribe($email)) {
                    $success = true;
                    $this->messageManager->addSuccess(__('We removed the subscription.'));
                }
            }
            if (!$success) {
                $this->messageManager->addError(__('Something went wrong while saving your subscription.'));
            }

            $redirectionUrl = $this->url->getUrl('customer/account');
            $this->responseFactory->create()
                    ->setRedirect($redirectionUrl)
                    ->sendResponse();
            exit; // searching for an better Solution,
                  // bcz. the Original Controll will be triggerd without
            return $this;
        }
    }

    /**
     *
     * @return \Magento\Framework\App\RequestInterface
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    protected function getResponse()
    {
        return $this->response;
    }
}
