<?php
/**
 * Milkyway Multimedia
 * ProductCatalogAdmin.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\Shop\Inventory\Extensions;

use Milkyway\SS\GridFieldUtils\SaveAllButton;

class ProductCatalogAdmin extends \Extension {
	function updateEditForm($form) {
        $model = singleton($this->owner->modelClass);

        if($model->hasExtension('Milkyway\SS\Shop\Inventory\Extensions\TrackStockOnBuyable') && $gf = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->owner->modelClass))) {
            if(\ClassInfo::exists('GridFieldEditableColumns')) {
	            $displayFields = [
		            $model->StockField => [
			            'title' => _t('ShopInventory.'.$model->StockField, $model->StockField),
			            'callback' => function($record, $col, $grid) {
				            return \NumericField::create($col, $col, $record->$col);
			            },
		            ],
	            ];

	            if($columns = $gf->Config->getComponentByType('GridFieldEditableColumns')) {
		            $columns->setDisplayFields($columns->getDisplayFields($gf) + $displayFields);
	            }
	            else {
		            $gf->Config->addComponent(with(new \GridFieldEditableColumns)->setDisplayFields($displayFields
			            )
			            , 'GridFieldEditButton');
	            }

	            if(!$gf->Config->getComponentByType('Milkyway\SS\GridFieldUtils\SaveAllButton'))
	                $gf->Config->addComponent(new SaveAllButton('buttons-before-left'));
            }
        }
	}

    protected function sanitiseClassName($class) {
        return str_replace('\\', '-', $class);
    }
} 