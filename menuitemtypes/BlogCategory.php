<?php

namespace Flynsarmy\Menu\MenuItemTypes;

use Backend\Widgets\Form;
use Flynsarmy\Menu\Classes\DropDownHelper;
use Flynsarmy\Menu\Models\MenuItem;
use Flynsarmy\Menu\Models\Settings;
use Winter\Blog\Models\Category;
use ApplicationException;
use URL;

class BlogCategory extends ItemTypeBase
{
    protected $categories = [];

    /**
     * Add fields to the MenuItem form.
     *
     * @param Form $form
     *
     * @return void
     */
    public function extendItemForm(Form $form)
    {
        if (!$this->categories) {
            $categories = Category::select('id', 'name')->orderBy('name')->lists('name', 'id');
            $this->categories = $categories;
        }

        $form->addFields([
            'master_object_id' => [
                'label'   => 'Blog Category',
                // @phpcs:ignore Generic.Files.LineLength.TooLong
                'comment' => 'Select the blog category you wish to link to. Remember to set the Blog Categories Page option on the Menu Settings page!',
                'type'    => 'dropdown',
                'options' => $this->categories,
                'tab'     => 'Item',
            ],
        ], 'primary');
    }

    /**
     * Adds any validation rules to $item->rules array that are required
     * by the ItemType's extended fields. If necessary, add custom messages
     * to $item->customMessages.
     *
     * For example:
     * $item->rules['master_object_id'] = 'required';
     * $item->customMessages['master_object_id.required'] = 'The Blog Post field is required.';
     *
     *
     * @param MenuItem $item
     *
     * @return void
     */
    public function extendItemModel(MenuItem $item)
    {
        $item->rules['master_object_id'] = 'required';
        $item->customMessages['master_object_id.required'] = 'The Blog Post field is required.';
    }

    /**
     * Add fields to the MenuItem form.
     *
     * @param Form $form
     *
     * @return void
     */
    public function extendSettingsForm(Form $form)
    {
        $form->addFields([
            'blog_category_page' => [
                'tab'     => 'Blog',
                'label'   => 'Blog Category Page',
                'comment' => 'Select the page your blog categories are displayed on',
                'type'    => 'dropdown',
                'options' => DropDownHelper::instance()->pages(),
            ],
        ], 'primary');
    }

    /**
     * Adds any validation rules to $settings->rules array that are required
     * by the ItemType's extended fields. If necessary, add custom messages
     * to $settings->customMessages.
     *
     * For example:
     * $settings->rules['master_object_id'] = 'required';
     * $settings->customMessages['master_object_id.required'] = 'The Blog Post field is required.';
     *
     *
     * @param \Flynsarmy\Menu\Models\Settings $settings
     *
     * @return void
     */
    public function extendSettingsModel(Settings $settings)
    {
        $settings->rules['blog_category_page'] = 'required';
        $settings->customMessages['blog_category_page.required'] = 'The Blog Category Page field is required.';
    }

    /**
     * Returns the URL for the master object of given ID.
     *
     * @param MenuItem $item Master object iD
     *
     * @return string
     */
    public function getUrl(MenuItem $item)
    {
        $page_url = Settings::get('blog_category_page', 'blog/category');
        $category = Category::find($item->master_object_id);

        if (!$category) {
            throw new ApplicationException('Category not found.');
        }

        return URL::to($page_url . '/' . $category->slug);
    }
}
