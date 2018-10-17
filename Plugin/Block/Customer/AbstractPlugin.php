<?php

namespace Loewenstark\Maileon2\Plugin\Block\Customer;

class AbstractPlugin
{
    /**
     *
     * @var \Loewenstark\Maileon2\Model\Api
     */
    protected $maileonApi;

    /**
     *
     * @var \Loewenstark\Maileon2\Helper\Data
     */
    protected $maileonHelper;

    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    public function __construct(
        \Loewenstark\Maileon2\Model\Api $maileonApi,
        \Loewenstark\Maileon2\Helper\Data $maileonHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Registry $registry
    ) {
        $this->maileonApi = $maileonApi;
        $this->maileonHelper = $maileonHelper;
        $this->customerSession = $customerSession;
    }

    /**
     *
     * @param \Magento\Newsletter\Model\Subscriber $model
     * @return bool
     */
    public function isSubscribed($model, callable $proceed)
    {
        if ($this->useMaileon()) {
            $sessionValue = $this->customerSession->getMaileon2NlStatus();
            if (is_array($sessionValue) && $sessionValue['timestamp'] > time()) {
                return $sessionValue['status'];
            }

            $status = $this->maileonApi->isSubscribed($this->customerSession->getCustomer()->getEmail());

            $this->customerSession->setMaileon2NlStatus([
                'timestamp' => (time()+120),
                'status' => $status
            ]);
            return $status;
        }
        return $proceed();
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
