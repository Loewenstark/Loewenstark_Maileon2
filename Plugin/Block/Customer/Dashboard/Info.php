<?php

namespace Loewenstark\Maileon2\Plugin\Block\Customer\Dashboard;

use Magento\Customer\Block\Account\Dashboard\Info as DashboardInfo;
use Loewenstark\Maileon2\Plugin\Block\Customer\AbstractPlugin as CustomerDashboard;

class Info extends CustomerDashboard
{
    public function aroundGetIsSubscribed(DashboardInfo $model, callable $proceed)
    {
        return $this->isSubscribed($model, $proceed);
    }
}
