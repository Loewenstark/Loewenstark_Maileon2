<?php

namespace Loewenstark\Maileon2\Plugin\Model\Newsletter;

use \Magento\Newsletter\Model\Subscriber as NewsletterSubscriber;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Subscriber
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
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        \Loewenstark\Maileon2\Model\Api $maileonApi,
        \Loewenstark\Maileon2\Helper\Data $maileonHelper,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->maileonApi = $maileonApi;
        $this->maileonHelper = $maileonHelper;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
    }

    /**
     *
     * @param NewsletterSubscriber $model
     * @param \Loewenstark\Maileon2\Plugin\Model\Newsletter\callable $proceed
     * @param string $email
     * @return int
     */
    public function aroundSubscribe(NewsletterSubscriber $model, callable $proceed, $email)
    {
        if ($this->useMaileon()) {
            $this->maileonApi->subscribe($email);
            return \Magento\Newsletter\Model\Subscriber::STATUS_NOT_ACTIVE;
        }
        return $proceed($email);
    }

    /**
     *
     * @param NewsletterSubscriber $model
     * @param \Loewenstark\Maileon2\Plugin\Model\Newsletter\callable $proceed
     * @return \Magento\Newsletter\Model\Subscriber
     */
    public function aroundUnsubscribe(NewsletterSubscriber $model, callable $proceed)
    {
        if ($this->useMaileon()) {
            $this->maileonApi->unsubscribe($model->getSubscriberEmail());
        }
        return $proceed();
    }
    
    /**
     *
     * @param NewsletterSubscriber $model
     * @param callable $proceed
     * @param int $customerId
     * @return NewsletterSubscriber
     */
    public function aroundSubscribeCustomerById(NewsletterSubscriber $model, callable $proceed, $customerId)
    {
        if ($this->useMaileon()) {
            $customerData = $this->loadByCustomer($customerId);
            if ($customerData) {
                $email = $customerData->getEmail();
                $this->maileonApi->subscribe($email);
            }
            return $model;
        }
        return $proceed($customerId);
    }

    /**
     *
     * @param NewsletterSubscriber $model
     * @param callable $proceed
     * @param int $customerId
     * @return NewsletterSubscriber
     */
    public function aroundUpdateSubscription(NewsletterSubscriber $model, callable $proceed, $customerId)
    {
        if ($this->useMaileon()) {
            if ($model->isStatusChanged()) {
                $customerData = $this->loadByCustomer($customerId);
                if ($customerData) {
                    $email = $customerData->getEmail();
                    $status = $this->maileonApi->isSubscribed($email);
                    if ($status) {
                        $this->maileonApi->unsubscribe($email);
                    } else {
                        $this->maileonApi->subscribe($email);
                    }
                }
            }
            return $model;
        }
        return $proceed($customerId);
    }

    /**
     *
     * @param NewsletterSubscriber $model
     * @param callable $proceed
     * @param int $customerId
     * @return NewsletterSubscriber
     */
    public function aroundUnsubscribeCustomerById(NewsletterSubscriber $model, callable $proceed, $customerId)
    {
        if ($this->useMaileon()) {
            $customerData = $this->loadByCustomer($customerId);
            if ($customerData) {
                $email = $customerData->getEmail();
                $this->maileonApi->unsubscribe($email);
            }
            return $model;
        }
        return $proceed($customerId);
    }

    /**
     *
     * @param NewsletterSubscriber $model
     * @param callable $proceed
     * @return NewsletterSubscriber
     */
    public function aroundSendUnsubscriptionEmail(NewsletterSubscriber $model, callable $proceed)
    {
        if ($this->useMaileon()) {
            return $model;
        }
        return $proceed();
    }

    /**
     *
     * @param NewsletterSubscriber $model
     * @param callable $proceed
     * @return NewsletterSubscriber
     */
    public function aroundSendConfirmationSuccessEmail(NewsletterSubscriber $model, callable $proceed)
    {
        if ($this->useMaileon()) {
            return $model;
        }
        return $proceed();
    }

    /**
     *
     * @param NewsletterSubscriber $model
     * @param callable $proceed
     * @return NewsletterSubscriber
     */
    public function aroundSendConfirmationRequestEmail(NewsletterSubscriber $model, callable $proceed)
    {
        if ($this->useMaileon()) {
            return $model;
        }
        return $proceed();
    }

    /**
     * Load subscriber info by customerId
     *
     * @param int $customerId
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function loadByCustomer($customerId)
    {
        $customerData = null;
        try {
            $customerData = $this->customerRepository->getById($customerId);
            $customerData->setStoreId($this->storeManager->getStore()->getId());
        } catch (NoSuchEntityException $e) {
            //
        }
        return $customerData;
    }

    /**
     *
     * @return boolean
     */
    protected function useMaileon()
    {
        return $this->maileonHelper->isActive();
    }
}
