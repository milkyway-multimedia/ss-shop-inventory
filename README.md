Silverstripe Shop Invetory
======
**Silverstripe Shop Order History** add some additional versioning and history to your orders, allowing you to completely use the CMS for order management, and plugging into external communication tools (only email is supported for now).

This makes a few changes to the current order processing within the shop module, in that the main source of status logging is via the status log, rather than through the Order itself. This allows users to set up their own statuses. If you do not like this idea, you can always decorate the OrderStatusLog class and replace the field with a dropdown.

## Features
A few of the features this module provides includes:

1. Full history - plugs in to Order events (and also relevant objects such as member, items, modifiers, address and payment depending on event)
2. Communication log - send emails via the CMS related to orders
3. Attach tracking number to orders

## Install
Add the following to your composer.json file

```

    "require"          : {
		"milkyway-multimedia/ss-shop-order-history": "dev-master"
	}

```

## License
* MIT

## Version
* Version 0.1 - Alpha

## Contact
#### Milkyway Multimedia
* Homepage: http://milkywaymultimedia.com.au
* E-mail: mell@milkywaymultimedia.com.au
* Twitter: [@mwmdesign](https://twitter.com/mwmdesign "mwmdesign on twitter")