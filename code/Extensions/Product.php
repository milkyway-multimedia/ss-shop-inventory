<?php namespace Milkyway\SS\Shop\Inventory\Extensions;
/**
 * Milkyway Multimedia
 * Product.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class Product extends \DataExtension {
    private static $db = [
        'Stock' => 'Int',
    ];

    protected $stockField = 'Stock';

    public function __construct($stockField = 'Stock') {
        parent::__construct();
        $this->stockField = $stockField;
    }

    function updateCMSFields(\FieldList $fields){
        if(!$this->owner->hasExtension('ProductVariationsExtension') || !$this->owner->Variations()->exists())
            $fields->addFieldToTab('Root.Main', NumericField::create($this->stockField, _t('ShopInventory.STOCK', 'Stock')), 'Content');
    }

    public function canPurchase($member, $quantity) {
        if(SiteConfig::env('Shop_AlwaysAllowPurchase') !== null)
            return SiteConfig::env('Shop_AlwaysAllowPurchase');

        if($this->owner->AvailableStock() > 0)
            return true;
    }

    public function AvailableStock() {
        if(!$this->owner->hasExtension('ProductVariationsExtension') || !$this->owner->Variations()->exists())
            $stock = $this->owner->{$this->stockField};
        else {
            $stock = $this->owner->Variations()->sum($this->stockField);
        }

        $this->owner->extend('updateAvailableStock', $stock);

        return $stock;
    }

    public function incrementStock($value = 1) {
        $this->owner->{$this->stockField} += $value;
        return $this;
    }

    public function decrementStock($value = 1) {
        $this->owner->{$this->stockField} -= $value;
        return $this;
    }
} 