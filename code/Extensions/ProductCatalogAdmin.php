<?php
/**
 * Milkyway Multimedia
 * ProductCatalogAdmin.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shop\Inventory\Extensions;

class ProductCatalogAdmin extends \Extension {
    private static $allowed_actions = [
        'EditForm',
    ];

    protected $stockComponent;

	function updateEditForm($form) {
        $model = singleton($this->owner->modelClass);

        if($model->hasExtension('Milkyway\SS\Shop\Inventory\Extensions\TrackStockOnBuyable') && $gf = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->owner->modelClass))) {
            if(\ClassInfo::exists('GridFieldEditableColumns')) {
                $gf->Config->addComponent($this->stockComponent = with(new \GridFieldEditableColumns)->setDisplayFields([
                            $model->StockField => [
                                'title' => _t('ShopInventory.'.$model->StockField, $model->StockField),
                                'callback' => function($record, $col, $grid) {
                                    return \NumericField::create($col, $col, $record->$col);
                                },
                            ],
                        ]
                    )
                , 'GridFieldEditButton');

                $form->Actions()->push(\FormAction::create('saveStock', 'Update Stock'));
            }
        }
	}

    function saveStock($data, $form, $request) {
        // @todo placeholder dataobject for now
        $model = singleton($this->owner->modelClass);

        if($model->hasExtension('Milkyway\SS\Shop\Inventory\Extensions\TrackStockOnBuyable') && $gf = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->owner->modelClass))) {
            if($this->stockComponent && ($this->stockComponent instanceof \GridField_SaveHandler)) {
                $this->stockComponent->handleSave($gf, $model);

                // A bit annoying, but since its versioned we have to save and publish
                $gf->List->each(
                    function ($item) {
                        if($item->hasExtension('Versioned')) {
                            $item->writeToStage('Stage');
                            $item->publish('Stage','Live');
                        }
                    }
                );
            } else
                $form->saveInto($model);
        }

        if ($model->exists()) {
            $model->delete();
            $model->destroy();
        }

        $this->owner->Response->addHeader('X-Status', rawurlencode(_t('ShopInventory.STOCK_SAVED', 'Stock saved.')));

        return $this->owner->getResponseNegotiator()->respond($request);
    }

    protected function sanitiseClassName($class) {
        return str_replace('\\', '-', $class);
    }
} 