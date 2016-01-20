<?php namespace Milkyway\SS\Shop\Inventory\Extensions;

/**
 * Milkyway Multimedia
 * OrderItem.php
 *
 * @package milkyway-multimedia/ss-shop-inventory
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use Object;
use DataExtension;

class OrderItem extends DataExtension
{
    private $prevQuantity;

    public function onBeforeWrite()
    {
        if ($this->owner->isChanged('Quantity')) {
            $changed = $this->owner->getChangedFields();
            $this->prevQuantity = isset($changed['Quantity']) && isset($changed['Quantity']['before']) ? $changed['Quantity']['before'] : 0;
        }
    }

    public function onAfterWrite()
    {
        $this->owner->PreviousQuantity = $this->prevQuantity;
        $this->prevQuantity = null;
    }
}
