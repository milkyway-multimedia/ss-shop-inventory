<?php namespace Milkyway\SS\Shop\Inventory\Extensions;

/**
 * Milkyway Multimedia
 * SiteConfig.php
 *
 * @package milkyway-multimedia/ss-shop-inventory
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use DB;
use DataExtension;
use FieldList;
use Product;
use CheckboxField;
use NumericField;
use TextField;
use Email;
use SiteConfig;

class Config extends DataExtension
{
    private static $db = [
        'Shop_DisableInventory' => 'Boolean',
        'Shop_NotifyWhenStockReaches' => 'Int',
        'Shop_DefaultStock' => 'Int',
        'Shop_NotifyEmail' => 'Varchar',
    ];

    private static $defaults = [
        'Shop_NotifyWhenStockReaches' => 5,
        'Shop_DefaultStock' => 10,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $productDefaults = (array) Product::config()->defaults;

        $fields->addFieldsToTab('Root.Shop.ShopTabs.ShopInventory', [
                CheckboxField::create('Shop_DisableInventory', _t('ShopInventory.DisableInventory', 'Disable inventory management')),
                NumericField::create('Shop_NotifyWhenStockReaches', _t('ShopInventory.NotifyWhenStockReaches', 'Notify when stock reaches')),
                NumericField::create('Shop_DefaultStock', _t('ShopInventory.DefaultStock', 'Default stock for new products'))->setAttribute('placeholder', isset($productDefaults['Stock']) ? $productDefaults['Stock'] : 5),
                TextField::create('Shop_NotifyEmail', _t('ShopInventory.NotifyEmail', 'Email to notify'))->setAttribute('placeholder', Config::env('AdminForEmail') ? : Email::config()->admin_email),
            ]
        );
    }

    public static function env($setting, $default = null, $params = [])
    {
        $callbacks = [];

        if (class_exists('SiteConfig') && !DB::get_schema()->isSchemaUpdating()) {
            $siteConfig = SiteConfig::current_site_config();

            $callbacks['ShopConfig'] = function ($keyParts, $key) use ($setting, $siteConfig) {
                return $siteConfig->{str_replace('ShopConfig.Inventory.', 'Shop_', $setting)};
            };
        }

        return singleton('env')->get($setting, $default, array_merge([
            'beforeConfigNamespaceCheckCallbacks' => $callbacks,
        ], $params));
    }
}
