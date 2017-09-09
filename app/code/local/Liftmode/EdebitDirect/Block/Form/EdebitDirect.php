<?php
/**
 *
 * @category   Mage
 * @package    Liftmode_EdebitDirect
 * @copyright  Copyright (c)  Dmitry Bashlov, contributors.
 */

class Liftmode_EdebitDirect_Block_Form_EdebitDirect extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payment/form/edebitdirect.phtml');
    }
}
