<?php namespace Milkyway\SS\Shop\Inventory\Listeners;

/**
 * Milkyway Multimedia
 * Notifications.php
 *
 * @package milkyway-multimedia/ss-shop-inventory
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use Milkyway\SS\Shop\Inventory\Extensions\Config;
use Email;
use CLosure;

class EmailNotifications
{
    public function zero($e, $buyable, $orderItem = null)
    {
        $url = $buyable->hasMethod('CMSEditLink') ? $buyable->CMSEditLink() : $buyable->CMSEditLink;

        $this->sendNotification([
            'params' => [
                'Subject' => _t('ShopInventory.EmailSubject-NONE', 'NO STOCK FOR: {title}', ['title' => $buyable->Title]),
                'Content'    => _t('ShopInventory.EmailBody-NONE',
                    'There is no longer any stock for {title}. <a href="{url}">You can update the stock for {title} in the {application}</a>.',
                    [
                        'title'       => $buyable->Title,
                        'url'         => $url,
                        'application' => singleton('LeftAndMain')->ApplicationName,
                    ]),
                'Link' => $url,
            ],
            'buyable' => $buyable,
            'item' => $orderItem,
         ]);
    }

    public function low($e, $buyable, $orderItem = null)
    {
        $url = $buyable->hasMethod('CMSEditLink') ? $buyable->CMSEditLink() : $buyable->CMSEditLink;

        $this->sendNotification([
            'params' => [
                'Subject' => _t('ShopInventory.EmailSubject-NONE', 'LOW STOCK FOR: {title}', ['title' => $buyable->Title]),
                'Content'    => _t('ShopInventory.EmailBody-NONE',
                    'There is only {stock} in stock for {title}, and has fallen below {indicator}. <a href="{url}">You can update the stock for {title} in the {application}</a>.',
                    [
                        'title'       => $buyable->Title,
                        'stock'       => $buyable->AvailableStock(),
                        'indicator'   => Config::env('ShopConfig.Inventory.NotifyWhenStockReaches'),
                        'url'         => $url,
                        'application' => singleton('LeftAndMain')->ApplicationName,
                    ]),
                'Link' => $url,
            ],
            'buyable' => $buyable,
            'item' => $orderItem,
        ]);
    }

    protected function sendNotification($params)
    {
        $email = Email::create();
        $email->setTemplate('Shop_Inventory_EmailNotification');

        if (isset($params['params'])) {
            foreach ($params['params'] as $paramName => $param) {
                $email->$paramName = $param;
            }
        }

        $adminEmail = function () {
            return Config::env('ShopConfig.Inventory.NotifyEmail') ?: Config::env('ShopConfig|SiteConfig.AdminForEmail') ?: Email::config()->admin_email;
        };

        if (!$email->To) {
            $email->To = $adminEmail();
        }

        if (!$email->From) {
            $email->From = $adminEmail instanceof Closure ? $adminEmail() : $adminEmail;
        }

        if (isset($params['buyable'])) {
            $email->Buyable = $params['buyable'];
        }

        if (isset($params['item'])) {
            $email->LastOrderItem = $params['item'];
            $email->LastOrder = $params['item']->Order();
        }

        $email->addCustomHeader('X-Priority', 1);
        $email->addCustomHeader('X-MSMail-Priority', 1);

        return $email->send();
    }
}
