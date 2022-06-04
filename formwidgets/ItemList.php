<?php

namespace Flynsarmy\Menu\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use Exception;
use Flynsarmy\Menu\Classes\MenuManager;
use Flynsarmy\Menu\Models\MenuItem;
use Request;

/**
 * Rich Editor
 * Renders a rich content editor field.
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class ItemList extends FormWidgetBase
{
    /**
     * {@inheritdoc}
     */
    public $defaultAlias = 'itemlist';

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('itemlist');
    }

    /**
     * Prepares the list data.
     */
    public function prepareVars()
    {
        // CMS Menus won't have this session key
        if (isset($this->controller->widget->formItems->sessionKey)) {
            $sessionKey = $this->controller->widget->formItems->sessionKey;
        } else {
            $sessionKey = '';
        }

        $columnName = $this->valueFrom;

        $this->vars['itemTypes'] = MenuManager::instance()->listItemTypes();
        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $this->model->$columnName()->withDeferred($sessionKey)->getNested();
    }

    /**
     * {@inheritdoc}
     */
    public function loadAssets()
    {
        // $this->addJs('js/itemlist.js');
    }

    public function getSaveValue($value)
    {
        //  The form field should not contribute any save data.
        return FormField::NO_SAVE_DATA;
    }

    public function onLoadTypeSelection()
    {
        $this->vars['item_types'] = MenuManager::instance()->listItemTypes();

        return $this->makePartial('type_selection');
    }

    public function onLoadCreateItem()
    {
        try {
            $type = post('type_id');
            if (!$type = MenuManager::instance()->resolveItemType($type)) {
                throw new Exception('Invalid item type: ' . post('type_id'));
            }
            $this->vars['type'] = $type;
            $itemTypes = MenuManager::instance()->listItemTypes();
            $this->vars['typeInfo'] = $itemTypes[$type];

            /*
             * Create a form widget to render the form
             */
            $config = $this->makeConfig('$/flynsarmy/menu/models/menuitem/fields.yaml');
            $config->model = new MenuItem();
            $config->context = 'create';
            $form = $this->makeWidget('Backend\Widgets\Form', $config);

            $form->bindEvent('form.extendFields', function () use ($type, $form) {
                $itemTypeObj = new $type();
                $itemTypeObj->extendItemForm($form);
            });

            $this->vars['form'] = $form;
        } catch (Exception $ex) {
            $this->vars['fatalError'] = $ex->getMessage();
        }

        return $this->makePartial('create_item');
    }

    public function onCreateItem()
    {
        $item = new MenuItem();
        $item->fill(Request::input());

        if (class_exists($item->master_object_class)) {
            $itemTypeObj = new $item->master_object_class();
            $itemTypeObj->extendItemModel($item);
            if ($item->validate()) {
                $item->url = $itemTypeObj->getUrl($item);
            }
        }

        $item->save();

        $this->model->items()->add($item, Request::input('_session_key'));

        // \Log::info(print_r($_POST, true));
        $this->prepareVars();

        return [
            '#reorderRecords' => $this->makePartial('item_records', ['records' => $this->vars['value']]),
        ];
    }

    public function onLoadEditItem()
    {
        try {
            $id = post('id', 0);
            if (!$item = MenuItem::find($id)) {
                throw new Exception('Menu item not found.');
            }
            $this->vars['id'] = $id;
            $this->vars['type'] = $type = $item->master_object_class;
            $itemTypes = MenuManager::instance()->listItemTypes();
            $this->vars['typeInfo'] = $itemTypes[$type];

            /*
             * Create a form widget to render the form
             */
            $config = $this->makeConfig('$/flynsarmy/menu/models/menuitem/fields.yaml');
            $config->model = $item;
            $config->context = 'edit';
            $form = $this->makeWidget('Backend\Widgets\Form', $config);

            $form->bindEvent('form.extendFields', function () use ($type, $form) {
                $itemTypeObj = new $type();
                $itemTypeObj->extendItemForm($form);
            });

            $this->vars['form'] = $form;
        } catch (Exception $ex) {
            $this->vars['fatalError'] = $ex->getMessage();
        }

        return $this->makePartial('update_item');
    }

    public function onEditItem()
    {
        $id = post('id', 0);
        if (!$item = MenuItem::find($id)) {
            throw new Exception('Menu item not found.');
        }
        $item->fill(Request::input());

        $master_object_class = Request::input('master_object_class');
        if (class_exists($master_object_class)) {
            $itemTypeObj = new $master_object_class();
            $itemTypeObj->extendItemModel($item);
            if ($item->validate()) {
                $item->url = $itemTypeObj->getUrl($item);
            }
        }

        $item->save();

        $this->prepareVars();

        return [
            '#reorderRecords' => $this->makePartial('item_records', ['records' => $this->vars['value']]),
        ];
    }

    public function onRemoveItem()
    {
        $id = post('id', 0);
        if (!$item = MenuItem::find($id)) {
            throw new Exception('Menu item not found.');
        }
        $item->delete();

        $this->prepareVars();

        return [
            '#reorderRecords' => $this->makePartial('item_records', ['records' => $this->vars['value']]),
        ];
    }

    public function onMove()
    {
        $sourceNode = MenuItem::find(post('sourceNode'));
        $targetNode = post('targetNode') ? MenuItem::find(post('targetNode')) : null;

        if ($sourceNode == $targetNode) {
            return;
        }

        switch (post('position')) {
            case 'before':
                $sourceNode->moveBefore($targetNode);
                break;
            case 'after':
                $sourceNode->moveAfter($targetNode);
                break;
            case 'child':
                $sourceNode->makeChildOf($targetNode);
                break;
            default:
                $sourceNode->makeRoot();
                break;
        }
    }
}
