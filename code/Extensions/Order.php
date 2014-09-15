<?php
/**
 * Milkyway Multimedia
 * Order.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shop\Inventory\Extensions;


class Order extends \DataExtension {
    public function afterAdd($item, $buyable, $quantity, $filter) {
        if($this->isAffectedItem($buyable))
            $buyable->decrementStock($quantity);
    }

    public function afterRemove($item, $buyable, $quantity, $filter) {
        if($this->isAffectedItem($buyable))
            $buyable->incrementStock($quantity);
    }

    protected function isAffectedItem($buyable) {
        return !Config::env('Shop_DisableInventory') && strtolower(Config::env('Shop_AffectStockDuring')) == 'cart' && ($buyable instanceof \Object) && $buyable->hasExtension('Milkyway\SS\Shop\Inventory\Extensions\Product');
    }
} 