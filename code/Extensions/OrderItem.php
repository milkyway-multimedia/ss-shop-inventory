<?php
/**
 * Milkyway Multimedia
 * OrderItem.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shop\Inventory\Extensions;


class OrderItem extends \DataExtension {
    function onPlacement() {
        if($this->owner->Buyable() && $this->isAffectedItem($this->owner->Buyable(), 'placement'))
            $this->owner->Buyable()->decrementStock($this->owner->Quantity, $this->owner);
    }

    function onPayment() {
        if($this->owner->Buyable() && $this->isAffectedItem($this->owner->Buyable(), 'payment'))
            $this->owner->Buyable()->decrementStock($this->owner->Quantity, $this->owner);
    }

    function onBeforeDelete() {
        if($this->owner->Buyable() && $this->owner->_ReturnStock)
            $this->owner->Buyable()->incrementStock($this->owner->Quantity);
    }

    protected function isAffectedItem($buyable, $during) {
        return !Config::env('Shop_DisableInventory') && strtolower(Config::env('Shop_AffectStockDuring')) == $during && ($buyable instanceof \Object) && $buyable->hasExtension('Milkyway\SS\Shop\Inventory\Extensions\TrackStockOnBuyable');
    }
} 