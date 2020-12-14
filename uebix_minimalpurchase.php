<?php
/**
 * 2014-2020 Uebix di Di Bella Antonino
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Uebix commercial License
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@uebix.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this PrestaShop Module to newer
 * versions in the future. If you wish to customize this PrestaShop Module for your
 * needs please refer to info@uebix.com for more information.
 *
 *  @author    Uebix <info@uebix.com>
 *  @copyright 2020-2020 Uebix
 *  @license   commercial use only, contact info@uebix.com for licence
 *  International Registered Trademark & Property of Uebix di Di Bella Antonino
 */
if (! defined('_PS_VERSION_')) {
    exit();
}

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class Uebix_minimalpurchase extends Module
{

    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'uebix_minimalpurchase';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'Uebix';
        $this->need_instance = 0;
        
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;
        
        parent::__construct();
        
        $this->displayName = $this->l('Use Minimal Purchase Tax Included');
        $this->description = $this->l('Set and check minimal purchase in cart with included taxes.');
        
        $this->ps_versions_compliancy = array(
            'min' => '1.6',
            'max' => _PS_VERSION_
        );
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        $languages = Language::getLanguages(false);
        $messages = [];
        foreach ($languages as $language) {
            $messages[(int) $language['id_lang']] = 'A minimum shopping cart total of %amount% (tax incl.) is required to validate your order. Current cart total is %total% (tax incl.).';
        }
        Configuration::updateValue('UEBIX_MINIMALPURCHASE_TEXT', $messages);
        
        return parent::install() && $this->registerHook('actionPresentCart');
    }

    public function uninstall()
    {
        Configuration::deleteByName('UEBIX_MINIMALPURCHASE_TEXT');
        
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitUebix_minimalpurchaseModule')) == true) {
            $this->postProcess();
        }
        
        $this->context->smarty->assign('module_dir', $this->_path);
        
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl') . $this->renderForm();
        
        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();
        
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitUebix_minimalpurchaseModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );
        
        return $helper->generateForm(array(
            $this->getConfigForm()
        ));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'textarea',
                        'name' => 'UEBIX_MINIMALPURCHASE_TEXT',
                        'label' => $this->l('Message'),
                        'desc' => $this->l('Enter a valid text message. Use %amount% placeholder for the minimal purchase total, %total% for the current cart total and %discounts% for the current discounts total in cart.'),
                        'lang' => true,
                        'cols' => 60,
                        'rows' => 5
                    ),
                    array(
                        'type' => 'text',
                        'name' => 'UEBIX_VOUCHER_STARTSEQ',
                        'label' => $this->l('Initial characters sequence of the voucher'),
                        'desc' => $this->l('If a discount voucher starts with this character sequence, the order total will be calculated without discount codes.')
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-eur"></i>',
                        'desc' => $this->l('Minimum purchase total required in order to validate the order'),
                        'name' => 'PS_PURCHASE_MINIMUM',
                        'label' => $this->l('Minimum purchase total')
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $languages = Language::getLanguages(false);
        $messages = [];
        foreach ($languages as $language) {
            $messages[(int) $language['id_lang']] = Configuration::get('UEBIX_MINIMALPURCHASE_TEXT', (int) $language['id_lang']);
        }
        return array(
            'UEBIX_MINIMALPURCHASE_TEXT' => $messages,
            'UEBIX_VOUCHER_STARTSEQ' => Configuration::get('UEBIX_VOUCHER_STARTSEQ'),
            'PS_PURCHASE_MINIMUM' => Configuration::get('PS_PURCHASE_MINIMUM')
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        
        foreach (array_keys($form_values) as $key) {
            if ($key == 'UEBIX_MINIMALPURCHASE_TEXT') {
                $languages = Language::getLanguages(false);
                $messages = [];
                foreach ($languages as $language) {
                    $messages[(int) $language['id_lang']] = Tools::getValue($key . '_' . ((int) $language['id_lang']));
                }
                Configuration::updateValue($key, $messages);
            } else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
    }

    public function hookActionPresentCart(&$params = false)
    {
        if ($params !== false && is_array($params) && isset($params['presentedCart'])) {
            if (isset($params['presentedCart']['minimalPurchase']) && isset($params['presentedCart']['minimalPurchaseRequired'])) {
                $priceFormatter = new PriceFormatter();
                $productsTotalIncludingTax = $this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
                $cartRules = $this->context->cart->getCartRules();
                $productsDiscountTotalIncludingTax = 0;
                $foundVoucherCode = false;
                $startSequence = trim(Configuration::get('UEBIX_VOUCHER_STARTSEQ'));
                
                if (! Tools::isEmpty($startSequence)) {
                    foreach ($cartRules as $cartRule) {
                        if (isset($cartRule['obj']) && Validate::isLoadedObject($cartRule['obj']) && stripos(trim($cartRule['obj']->code), $startSequence) === 0) {
                            $foundVoucherCode = true;
                            break;
                        }
                    }
                }
                if (! $foundVoucherCode) {
                    $productsDiscountTotalIncludingTax = $this->context->cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
                }
                $orderTotal = $productsTotalIncludingTax - $productsDiscountTotalIncludingTax;
                $minimalPurchase = $params['presentedCart']['minimalPurchase'];
                
                $search = [
                    '%amount%',
                    '%total%',
                    '%discounts%'
                ];
                
                $replace = [
                    $priceFormatter->format($minimalPurchase),
                    $priceFormatter->format($orderTotal),
                    $priceFormatter->format($productsDiscountTotalIncludingTax)
                ];
                
                $message = Configuration::get('UEBIX_MINIMALPURCHASE_TEXT', $this->context->language->id, null, null, 'A minimum shopping cart total of %amount% (tax incl.) is required to validate your order. Current cart total is %total% (tax incl.).');
                $message = str_ireplace($search, $replace, $message);
                
                $params['presentedCart']['minimalPurchaseRequired'] = ($orderTotal < $minimalPurchase) ? $message : '';
            }
        }
    }
}
