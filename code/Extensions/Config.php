<?php
/**
 * Milkyway Multimedia
 * SiteConfig.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shop\Inventory\Extensions;

use Doctrine\Common\Inflector\Inflector;

class Config extends \DataExtension {
    public static $environment = [];

    private static $db = [
        'Shop_DisableInventory' => 'Boolean',
        'Shop_NotifyWhenStockReaches' => 'Int',
        'Shop_NotifyEmail' => 'Varchar',
    ];

    private static $defaults = [
        'Shop_NotifyWhenStockReaches' => 5,
    ];

    private static $shop_affect_stock_during = 'placement'; // Can be: cart, placement, payment

    public function updateCMSFields(\FieldList $fields) {
        $fields->addFieldsToTab('Root.Shop.ShopTabs.ShopInventory', [
                \CheckboxField::create('Shop_DisableInventory', _t('ShopInventory.DISABLE', 'Disable')),
                \NumericField::create('Shop_NotifyWhenStockReaches', _t('ShopInventory.NotifyWhenStockReaches', 'Notify when stock reaches')),
                \TextField::create('Shop_NotifyEmail', _t('ShopInventory.NotifyWhenStockReaches', 'Email to notify'))->setAttribute('placeholder', Config::env('AdminForEmail') ? : \Config::inst()->get('Email', 'admin_email')),
            ]
        );
    }

    public static function env($setting, \ViewableData $object = null) {
        if($object && $object->$setting)
            return $object->$setting;

        if(isset(self::$environment[$setting]))
            return self::$environment[$setting];

        $value = null;

        $dbSetting = $setting;
        $envSetting = strtolower(Inflector::tableize($setting));

        if($object)
            $value = $object->config()->$envSetting;
        elseif (\ShopConfig::current()->$dbSetting) {
            $value = \ShopConfig::current()->$dbSetting;
        }
        elseif (\ShopConfig::config()->$envSetting) {
            $value = \ShopConfig::config()->$envSetting;
        }
        else if (getenv($envSetting)) {
            $value = getenv($envSetting);
        } elseif (isset($_ENV[$envSetting])) {
            $value = $_ENV[$envSetting];
        }

        if ($value) {
            self::$environment[$setting] = $value;
        }

        return $value;
    }
} 