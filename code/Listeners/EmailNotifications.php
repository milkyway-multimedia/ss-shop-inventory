<?php
/**
 * Milkyway Multimedia
 * Notifications.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shop\Inventory\Listeners;

use Milkyway\SS\Shop\Inventory\Extensions\Config;

class EmailNotifications {
    public function zero($buyable, $orderItem) {
        $this->sendNotification([
                'Subject' => _t('ShopInventory.EmailSubject-NONE', 'NO STOCK FOR: {title}', ['title' => $buyable->Title]),
                'Body' => _t('ShopInventory.EmailBody-NONE', 'There is no longer any stock for {title}. <a href="{url}">You can update the stock for {title} in the {application}</a>', [
                        'title' => $buyable->Title,
                        'url' => $buyable->hasMethod('CMSEditLink') ? $buyable->CMSEditLink() : $buyable->CMSEditLink,
                        'application' => singleton('LeftAndMain')->ApplicationName,
                    ]),
                ], $buyable, $orderItem);
    }

    public function belowIndicator($buyable, $orderItem) {
        $this->sendNotification([
                'Subject' => _t('ShopInventory.EmailSubject-NONE', 'LOW STOCK FOR: {title}', ['title' => $buyable->Title]),
                'Body' => _t('ShopInventory.EmailBody-NONE', 'There is only {stock} in stock for {title}, and has fallen below {indicator}. <a href="{url}">You can update the stock for {title} in the {application}</a>', [
                        'title' => $buyable->Title,
                        'stock' => $buyable->AvailableStock(),
                        'indicator' => Config::env('Shop_NotifyWhenStockReaches'),
                        'url' => $buyable->hasMethod('CMSEditLink') ? $buyable->CMSEditLink() : $buyable->CMSEditLink,
                        'application' => singleton('LeftAndMain')->ApplicationName,
                    ]),
            ], $buyable, $orderItem);
    }

    protected function sendNotification($params, $buyable, $orderItem = null) {
		$email = \Email::create();

	    foreach($params as $param => $value)
		    $email->$param = $value;

	    if(!$email->To);
	    $email->To = Config::env('Shop_NotifyEmail') ? : Config::env('AdminForEmail') ? : \Config::inst()->get('Email', 'admin_email');

	    if(!$email->From);
	        $email->From = Config::env('Shop_NotifyEmail') ? : Config::env('AdminForEmail') ? : \Config::inst()->get('Email', 'admin_email');

	    $email->Buyable = $buyable;

	    if($orderItem) {
		    $email->LastOrderItem = $orderItem;
		    $email->LastOrder = $orderItem->Order();
	    }

	    $email->addCustomHeader('X-Priority', 1);
	    $email->addCustomHeader('X-MSMail-Priority', 1);

	    return $email->send();
    }
} 