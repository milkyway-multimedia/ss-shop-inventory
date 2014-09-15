<?php
/**
 * Milkyway Multimedia
 * Notifications.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shop\Inventory\Listeners;

use Milkyway\SS\Shop\Inventory\Extensions\SiteConfig;

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
                        'indicator' => SiteConfig::env('Shop_NotifyWhenStockReaches'),
                        'url' => $buyable->hasMethod('CMSEditLink') ? $buyable->CMSEditLink() : $buyable->CMSEditLink,
                        'application' => singleton('LeftAndMain')->ApplicationName,
                    ]),
            ], $buyable, $orderItem);
    }

    protected function sendNotification($params, $buyable, $orderItem) {

    }
} 