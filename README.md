Silverstripe Shop Inventory
===========================
**Silverstripe Shop Inventory** is a simple stock inventory tracker.

It hooks into the milkyway-multimedia/ss-events-handler module, so you can easily add global event listeners to hook it into an external PoS system.

## Install
Add the following to your composer.json file

```

    "require"          : {
		"milkyway-multimedia/ss-shop-inventory": "dev-master"
	}

```

## Configuration
Most configuration can be done via the CMS. You can also do some YAML configuration for how inventory is handled.

```

ShopConfig:
  Inventory:
    AffectStockDuring: 'placement'
    # Above can be either placement, payment or cart - cart is rather volatile, and will update inventory during each add/remove from cart scenario
    ZeroIndicatorInterval_Hours: 1 # Control how often the zero indicator event is fired (hours)
    LowIndicatorInterval_Hours: 24 # Control how often the low indicator event is fired (hours)

```

## License
* MIT

## Version
* Version 0.2 - Alpha

## Contact
#### Milkyway Multimedia
* Homepage: http://milkywaymultimedia.com.au
* E-mail: mell@milkywaymultimedia.com.au
* Twitter: [@mwmdesign](https://twitter.com/mwmdesign "mwmdesign on twitter")