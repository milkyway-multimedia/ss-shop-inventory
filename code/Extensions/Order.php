<?php namespace Milkyway\SS\Shop\Inventory\Extensions;

/**
 * Milkyway Multimedia
 * Order.php
 *
 * @package milkyway-multimedia/ss-shop-inventory
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use Object;
use DataExtension;

class Order extends DataExtension
{
    public function onPlaceOrder()
    {
        $this->owner->Items()->each(function ($item) {
            if ($item->Buyable() && $this->isAffectedItem($item->Buyable(), 'placement')) {
                $item->Buyable()->decrementStock($item->Quantity, $item);
            }
        });
    }

    public function onPaid()
    {
        $this->owner->Items()->each(function ($item) {
            if ($item->Buyable() && $this->isAffectedItem($item->Buyable(), 'payment')) {
                $item->Buyable()->decrementStock($item->Quantity, $item);
            }
        });
    }

    public function afterAdd($item, $buyable, $quantity, $filter)
    {
        if ($buyable && $this->isAffectedItem($buyable, 'cart')) {
            $buyable->decrementStock($quantity, $item);
        }
    }

    public function afterRemove($item, $buyable, $quantity, $filter)
    {
        if ($buyable && $this->isAffectedItem($buyable, 'cart')) {
            $quantity = $item->exists() ? $quantity : $item->Quantity;
            $buyable->incrementStock($quantity, $item);
        }
    }

    public function afterSetQuantity($item, $buyable, $quantity, $filter)
    {
        if (($item->PreviousQuantity === null && !$item->_brandnew) || !$buyable || !$this->isAffectedItem($buyable,
                'cart')
        ) {
            return;
        }

        if ($item->_brandnew) {
            $buyable->decrementStock($quantity, $item);
        } elseif ($item->PreviousQuantity > $quantity) {
            $buyable->incrementStock(($item->PreviousQuantity - $quantity), $item);
        } elseif ($quantity > $item->PreviousQuantity) {
            $buyable->decrementStock(($quantity - $item->PreviousQuantity), $item);
        }

        $item->PreviousQuantity = null;
    }

    protected function isAffectedItem($buyable, $during = 'placement')
    {
        return !Config::env('ShopConfig.Inventory.DisableInventory') && strtolower(Config::env('ShopConfig.Inventory.AffectStockDuring')) == $during && ($buyable instanceof Object) && $buyable->hasExtension('Milkyway\SS\Shop\Inventory\Extensions\TrackStockOnBuyable');
    }
} 