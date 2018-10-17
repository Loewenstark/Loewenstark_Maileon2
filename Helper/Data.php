<?php

namespace Loewenstark\Maileon2\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     *
     * @var string
     */
    protected $magentoVersion = 'CE-2.2.4'; // sample

    /**
     *
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encModel;

    /**
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $configModel;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Encryption\EncryptorInterface $context
     * @param \Magento\Framework\App\ProductMetadataInterface $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->encModel = $encryptor;
        $this->magentoVersion = $productMetadata->getEdition().'-'.$productMetadata->getVersion();
        $this->configModel = $scopeConfig;
    }

    /**
     * is Active?
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->configModel->isSetFlag('maileon2/general/active');
    }

    /**
     * get API Key
     *
     * @return bool
     */
    public function getApiKey()
    {
        $secret = $this->configModel->getValue('maileon2/general/apikey');
        if (substr_count($secret, '-') > 1) {
            return $secret;
        }
        return $this->encModel->decrypt($secret);
    }

    /**
     * get API Key as Base64
     *
     * @return string
     */
    public function getBase64ApiKey()
    {
        return base64_encode($this->getApiKey());
    }

    /**
     * get Doi Key (Optional)
     *
     * @return string|null
     */
    public function getDoiKey()
    {
        return $this->configModel->getValue('maileon2/general/doikey');
    }

    /**
     * use Extented Tracking
     *
     * @return bool
     */
    public function getUseDoiPlus()
    {
        return $this->configModel->isSetFlag('maileon2/general/doiplus');
    }

    /**
     * getMagento Version incl. Edition
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->magentoVersion;
    }

    /**
     * check TLS/SSL Cert
     *
     * @return bool
     */
    public function getVerfiyPeer()
    {
        return $this->configModel->isSetFlag('maileon2/connection/verfiypeer');
    }

    /**
     * Force TLS 1.2 Mode
     *
     * @return bool
     */
    public function getForceTls12()
    {
        return $this->configModel->isSetFlag('maileon2/connection/forcetls12');
    }

    /**
     *
     * @return int
     */
    public function getTimeout()
    {
        $value = (int) $this->configModel->getValue('maileon2/connection/timeout');
        if (empty($value)) {
            $value = 10;
        }
        return $value;
    }
}
