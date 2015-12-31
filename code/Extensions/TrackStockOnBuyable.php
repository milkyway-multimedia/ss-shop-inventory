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
use SS_Datetime;
use ValidationException;

class TrackStockOnBuyable extends DataExtension
{
    private static $defaults = [
        'Stock' => 5,
    ];

    protected $stockField = 'Stock';
    protected $autoAdjust = true;
    protected $validateStock = false;

    public function __construct($stockField = 'Stock', $autoAdjust = true, $validateStock = false)
    {
        parent::__construct();
        $this->stockField = $stockField;
        $this->autoAdjust = $autoAdjust;
        $this->validateStock = $autoAdjust;
    }

    public static function get_extra_config($class, $extension, $args)
    {
        $field = isset($args[0]) ? $args[0] : 'Stock';

        return [
            'db'                => [
                $field                     => 'Int',
                $field . '_NoTracking'     => 'Boolean',
                $field . '_AlwaysAllow'    => 'Boolean',
                $field . '_LastSentOnLow'  => 'Datetime',
                $field . '_LastSentOnZero' => 'Datetime',
            ],
            'searchable_fields' => [
                $field => [
                    'filter' => 'LessThanOrEqualFilter',
                    'field'  => 'NumericField',
                    'title' => _t('ShopInventory.STOCK_LESS_THAN', '{stock} less than', [
                        'stock' => _t('ShopInventory.' . $field, FormField::name_to_label($field)),
                    ]),
                ],
            ],
        ];
    }

    public function populateDefaults()
    {
        $this->owner->{$this->stockField} = (int)Config::env('ShopConfig.Inventory.DefaultStock');
    }

    public function updateCMSFields(FieldList $fields)
    {
        if (Config::env('ShopConfig.Inventory.DisableInventory')  || ($this->owner->hasExtension('ProductVariationsExtension') && $this->owner->Variations()->exists())) {
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

        if (!$this->owner->AllowPurchase) {
            $fields->removeByName($this->stockField . '_AlwaysAllow', true);
        }
    }

    public function canPurchase($member = null, $quantity = 1)
    {
        if (Config::env('ShopConfig.Inventory.DisableInventory') || Config::env('ShopConfig.Inventory.AlwaysAllowPurchase')) {
            return null;
        }

        $stock = $this->owner->AvailableStock();

        if (!$this->owner->{$this->stockField . '_AlwaysAllow'} && (!$stock || $stock < $quantity)) {
            return false;
        }

        return null;
    }

    public function AvailableStock($checkVariations = true)
    {
        $stock = 0;

        if (!$this->owner->hasExtension('ProductVariationsExtension') || !$this->owner->Variations()->exists()) {
            $stock = $this->owner->{$this->stockField};
        } else {
            if ($checkVariations) {
                $stock = $this->owner->Variations()->sum($this->stockField);
            }
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
        $this->fireEvents($orderItem);

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

        $currentStock = $this->owner->AvailableStock();

        if ($currentStock < $value) {
            if ($this->autoAdjust && $orderItem) {
                $orderItem->Quantity = strtolower(Config::env('ShopConfig.Inventory.AffectStockDuring')) == 'cart' ? $orderItem->PreviousQuantity + $currentStock : $currentStock;

                if ($orderItem->exists() && $write) {
                    $orderItem->write();
                    $result->error('Not enough stock, stock has been adjusted.', 'adjustment');
                }
            } else {
                $result->error('Not enough stock.', 'error');
            }

            if (!$this->autoAdjust || $this->validateStock) {
                throw new ValidationException($result);
            }
        }

        $this->owner->{$this->stockField} -= $value;

        if ($this->owner->{$this->stockField} < 0) {
            $this->owner->{$this->stockField} = 0;
        }

        $this->fireEvents($orderItem);

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

    public function isOutOfStock()
    {
        return $this->owner->{$this->stockField} <= 0;
    }

    public function isOnLowStock()
    {
        return ($notifyLimit = Config::env('ShopConfig.Inventory.NotifyWhenStockReaches')) && $this->owner->{$this->stockField} <= $notifyLimit;
    }

    protected function fireEvents($orderItem = null)
    {
        if ($this->owner->isOutOfStock() && $this->doFire('zero')) {
            singleton('Eventful')->fire('shop:inventory:zero', $this->owner, $orderItem);
            $this->owner->{$this->stockField . '_LastSentOnZero'} = SS_Datetime::now()->Rfc2822();
        } elseif ($this->owner->isOnLowStock() && $this->doFire('low')) {
            singleton('Eventful')->fire('shop:inventory:low', $this->owner, $orderItem);
            $this->owner->{$this->stockField . '_LastSentOnLow'} = SS_Datetime::now()->Rfc2822();
        }
    }

    protected function doFire($event = 'zero')
    {
        if ($event == 'low') {
            return $this->owner->{$this->stockField . '_LastSentOnLow'} ? $this->hoursIsWithin($this->owner->{$this->stockField . '_LastSentOnLow'},
                Config::env('ShopConfig.Inventory.LowIndicatorInterval_Hours', 24)) : true;
        }

        return $this->owner->{$this->stockField . '_LastSentOnZero'} ? $this->hoursIsWithin($this->owner->{$this->stockField . '_LastSentOnZero'},
            Config::env('ShopConfig.Inventory.ZeroIndicatorInterval_Hours', 1)) : true;
    }

    protected function hoursIsWithin($value, $range)
    {
        return round(abs(SS_Datetime::now()->Format('U') - strtotime($value)) / 3600) >= $range;
    }
}
