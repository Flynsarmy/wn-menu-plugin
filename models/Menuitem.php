<?php

namespace Flynsarmy\Menu\Models;

use Cms\Classes\Controller;
use Model;

/**
 * Menuitem Model.
 */
class Menuitem extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\NestedTree;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'flynsarmy_menu_menuitems';

    // public $implement = ['Winter.Storm.Database.Behaviors.NestedSetModel'];

    // Soft implements. Only if TranslatableModel exists
    public $implement = ['@Winter.Translate.Behaviors.TranslatableModel'];

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'enabled', 'label', 'title_attrib', 'id_attrib', 'class_attrib',
        'target_attrib', 'selected_item_id', 'url', 'data', 'master_object_class',
        'master_object_id', 'parent_id'
    ];

    public $belongsTo = [
        'menu' => ['Flynsarmy\Menu\Models\Menu'],
    ];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'label' => 'required',
    ];

    protected $jsonable = ['data'];

    /**
     * @var array Translatable fields
     */
    public $translatable = ['label', 'title_attrib'];

    public $customMessages = [];

    protected $cache = [];

    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Generates a class attribute based on the menu settings and item position.
     *
     * @param array $settings
     * @param int   $depth
     *
     * @return string
     */
    public function getClassAttrib(array $settings, $depth)
    {
        if (!empty($this->cache['classAttrib'])) {
            return $this->cache['classAttrib'];
        }

        $classes = [];
        if ($this->class_attrib) {
            $classes = explode(' ', $this->class_attrib);
        }

        if (is_int($depth)) {
            $classes[] = $settings['depth_prefix'] . $depth;
        }

        if ($this->getChildren()->count()) {
            $classes[] = $settings['has_children_class'];
        }

        if (!empty($settings['selected_item']) && $settings['selected_item'] == $this->selected_item_id) {
            $classes[] = $settings['selected_item_class'];
        }

        return $this->cache['classAttrib'] = implode(' ', $classes);
    }

    public function render(Controller $controller, array $settings, $depth, $url, $child_count = 0)
    {
        if (!$this->enabled) {
            return '';
        }

        // Support custom itemType-specific output
        if (class_exists($this->master_object_class)) {
            $itemTypeObj = new $this->master_object_class();
            if ($output = $itemTypeObj->onRender($this, $controller, $settings, $depth, $url, $child_count)) {
                return $output;
            }
        }

        return require __DIR__ . '/../partials/_menuitem.php';
    }

    public function beforeCreate()
    {
        $this->setDefaultLeftAndRight();
    }

    /**
     * Forces translations to be saved.
     */
    public function afterSave()
    {
        if (!class_exists('Winter\Translate\Behaviors\TranslatableModel')) {
            return;
        }

        $translatedAttributes = \Input::get('RLTranslate', []);

        $save = false;
        foreach ($translatedAttributes as $locale => $inputs) {
            foreach ($inputs as $attr => $value) {
                if ($this->getAttributeTranslated($attr, $locale) != $value) {
                    $t = $this->setAttributeTranslated($attr, $value, $locale);
                    $save = true;
                }
            }
        }
        if ($save) {
            $this->save();
        }
    }
}
