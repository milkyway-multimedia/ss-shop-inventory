<?php namespace Milkyway\SS\Shop\Inventory\Extensions;

/**
 * Milkyway Multimedia
 * Product.php
 *
 * @package milkyway-multimedia/ss-shop-inventory
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use DataExtension;
use ValidationResult;
use FieldList;
use FieldGroup;
use FormField;
use NumericField;
use ToggleCompositeField;
use CheckboxField;
use DropdownField;

class TrackStockOnBuyable extends DataExtension
{
    private static $defaults = [
        'Stock' => 5,
    ];

    protected $stockField = 'Stock';

    public function __construct($stockField = 'Stock')
    {
        parent::__construct();
        $this->stockField = $stockField;
    }

    public static function get_extra_config($class, $extension, $args)
    {
        $field = isset($args[0]) ? $args[0] : 'Stock';

        return [
            'db' => [
                $field                  => 'Int',
                $field . '_NoTracking'  => 'Boolean',
                $field . '_AlwaysAllow' => 'Boolean',
            ],
        ];
    }

    function populateDefaults()
    {
        $this->owner->{$this->stockField} = (int)Config::env('ShopConfig.Inventory.DefaultStock');
    }

    function updateCMSFields(\FieldList $fields)
    {
        if ($this->owner->hasExtension('ProductVariationsExtension') && $this->owner->Variations()->exists()) {
            return;
        }

        $fields->addFieldToTab(
            'Root.Main',
            FieldGroup::create(
                _t('ShopInventory.' . $this->stockField, FormField::name_to_label($this->stockField)),
                [
                    NumericField::create($this->stockField, ''),
                    ToggleCompositeField::create($this->stockField . '_Options',
                        _t('ShopInventory.STOCK_OPTIONS', 'Options'),
                        [
                            DropdownField::create($this->stockField . '_NoTracking', '', [
                                0 => _t('ShopInventory.TRACK_INVENTORY', 'Track inventory'),
                                1 => _t('ShopInventory.NO_TRACK_INVENTORY', "Don't track inventory"),
                            ]),
                            CheckboxField::create($this->stockField . '_AlwaysAllow',
                                _t('ShopInventory.ALWAYS_ALLOW_PURCHASE',
                                    'Always allow purchase even when out of stock')),
                        ]
                    )->setHeadingLevel(5),
                ]
            )->setName($this->stockField . '_Tab'),
            'Content'
        );
    }

    public function canPurchase($member, $quantity)
    {
        if (Config::env('ShopConfig.Inventory.AlwaysAllowPurchase') !== null) {
            return Config::env('ShopConfig.Inventory.AlwaysAllowPurchase');
        }

        if (!$this->owner->{$this->stockField . '_AlwaysAllow'} && $this->owner->AvailableStock() <= $quantity) {
            return false;
        }
    }

    public function AvailableStock()
    {
        if (!$this->owner->hasExtension('ProductVariationsExtension') || !$this->owner->Variations()->exists()) {
            $stock = $this->owner->{$this->stockField};
        } else {
            $stock = $this->owner->Variations()->sum($this->stockField);
        }

        $this->owner->extend('updateAvailableStock', $stock);

        return $stock;
    }

    public function incrementStock($value = 1, $orderItem = null, $write = true)
    {
        $result = ValidationResult::create();

        if ($this->owner->{$this->stockField . '_NoTracking'}) {
            return $result;
        }

        $this->owner->{$this->stockField} += $value;

        if ($write) {
            $this->owner->write();

            if ($this->owner->hasExtension('Versioned')) {
                $this->owner->writeToStage('Stage');
                $this->owner->publish('Stage', 'Live');
            }
        }

        $this->owner->extend('onIncrementStock', $value, $orderItem, $write);

        return $result;
    }

    public function decrementStock($value = 1, $orderItem = null, $write = true)
    {
        $result = ValidationResult::create();

        if ($this->owner->{$this->stockField . '_NoTracking'}) {
            return $result;
        }

        $currentStock = $this->owner->{$this->stockField};

        if ($currentStock < $value) {
            $value = $currentStock;
            $result->error('Not enough stock, stock has been adjusted.', 'adjustment');

            if ($orderItem) {
                $orderItem->Quantity = $value;
                if ($orderItem->exists() && $write) {
                    $orderItem->write();
                }
            }
        }

        $this->owner->{$this->stockField} -= $value;

        if ($this->owner->{$this->stockField} < 0) {
            $this->owner->{$this->stockField} = 0;
        }

        if ($this->owner->{$this->stockField} <= 0) {
            singleton('Eventful')->fire('shop:inventory:zero', $this->owner, $orderItem);
        } elseif (($notifyLimit = Config::env('ShopConfig.Inventory.NotifyWhenStockReaches')) && $this->owner->{$this->stockField} <= $notifyLimit) {
            singleton('Eventful')->fire('shop:inventory:low', $this->owner, $orderItem);
        }

        if ($write) {
            $this->owner->write();

            if ($this->owner->hasExtension('Versioned')) {
                $this->owner->writeToStage('Stage');
                $this->owner->publish('Stage', 'Live');
            }
        }

        $this->owner->extend('onDecrementStock', $value, $orderItem, $write);

        return $result;
    }

    public function getStockField()
    {
        return $this->stockField;
    }
} 