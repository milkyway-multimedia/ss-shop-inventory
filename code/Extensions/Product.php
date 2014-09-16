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

    private static $defaults = [
        'Stock' => 5,
    ];

    protected $stockField = 'Stock';

    public function __construct($stockField = 'Stock') {
        parent::__construct();
        $this->stockField = $stockField;
    }

    function populateDefaults() {
        if($this->owner->hasDatabaseField($this->stockField))
            $this->owner->{$this->stockField} = Config::env('Shop_DefaultStock') ? : $this->owner->Stock;
    }

    function updateCMSFields(\FieldList $fields){
        if(!$this->owner->hasExtension('ProductVariationsExtension') || !$this->owner->Variations()->exists())
            $fields->addFieldToTab('Root.Main', \NumericField::create($this->stockField, _t('ShopInventory.STOCK', 'Stock')), 'Content');
    }

    public function canPurchase($member, $quantity) {
        if(Config::env('Shop_AlwaysAllowPurchase') !== null)
            return Config::env('Shop_AlwaysAllowPurchase');

        if($this->owner->AvailableStock() <= 0)
            return false;
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

    public function incrementStock($value = 1, $write = true) {
        $this->owner->{$this->stockField} += $value;

	    if($write) {
		    $this->owner->write();

		    if($this->owner->hasExtension('Versioned')) {
			    $this->owner->writeToStage('Stage');
			    $this->owner->publish('Stage','Live');
		    }
	    }

        return $this;
    }

    public function decrementStock($value = 1, $write = true) {
        $this->owner->{$this->stockField} -= $value;

	    if($this->owner->{$this->stockField} <= 0)
		    \Injector::inst()->get('Milkyway\SS\Events\Dispatcher')->fire('ShopInventory', 'zero');
	    elseif(Config::env('Shop_NotifyWhenStockReaches') && $this->owner->{$this->stockField} <= Config::env('Shop_NotifyWhenStockReaches'))
		    \Injector::inst()->get('Milkyway\SS\Events\Dispatcher')->fire('ShopInventory', ['belowIndicator']);

	    if($write) {
		    $this->owner->write();

		    if($this->owner->hasExtension('Versioned')) {
			    $this->owner->writeToStage('Stage');
			    $this->owner->publish('Stage','Live');
		    }
	    }

        return $this;
    }

    public function getStockField() {
        return $this->stockField;
    }
} 