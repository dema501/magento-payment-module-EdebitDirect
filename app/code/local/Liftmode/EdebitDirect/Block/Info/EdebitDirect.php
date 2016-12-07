<?php
/**
 *
 * @category   Mage
 * @package    Liftmode_EdebitDirect
 * @copyright  Copyright (c)  LiftMode (Synaptent LLC).
 */

class Liftmode_EdebitDirect_Block_Info_EdebitDirect extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payment/info/edebitdirect.phtml');
    }

    public function getAccountNumber()
    {
        return ('XXXX' . substr($this->getInfo()->getAccountNumber(), -4));
    }

}
