<?php

namespace Loewenstark\Maileon2\Plugin\Block\Customer;

use \Magento\Customer\Block\Newsletter as CustomerNewsletter;

class Newsletter extends Dashboard
{
    public function aroundGetIsSubscribed(CustomerNewsletter $model, callable $proceed)
    {
        return $this->isSubscribed($model, $proceed);
    }
}
