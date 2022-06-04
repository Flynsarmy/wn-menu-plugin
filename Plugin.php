<?php

namespace Flynsarmy\Menu;

use Backend;
use Backend\Classes\NavigationManager;
use Cms\Controllers\Index as CmsController;
use Flynsarmy\Menu\Models\Menu;
use Flynsarmy\Menu\Widgets\MenuList;
use Event;
use System\Classes\PluginBase;
use System\Classes\PluginManager;

/**
 * Menus Plugin Information File.
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Menu',
            'description' => 'Create flexible menus straight from Winter CMS admin',
            'author'      => 'Flyn San',
            'icon'        => 'icon-bars',
            'homepage'    => 'https://github.com/Flynsarmy/wn-menu-plugin',
        ];
    }

    public function registerNavigation()
    {
        return [
            'menu' => [
                'label'       => 'Menus',
                'url'         => Backend::url('flynsarmy/menu/menus'),
                'icon'        => 'icon-bars',
                'permissions' => ['flynsarmy.menu.*'],
                'order'       => 500,

                'sideMenu' => [
                    'menus' => [
                        'label'       => 'All Menus',
                        'icon'        => 'icon-bars',
                        'url'         => Backend::url('flynsarmy/menu/menus'),
                        'permissions' => ['flynsarmy.menu.access_menus'],
                    ],
                    'settings' => [
                        'label'       => 'Settings',
                        'icon'        => 'icon-cog',
                        'url'         => Backend::url('flynsarmy/menu/settings'),
                        'permissions' => ['flynsarmy.menu.access_menu_settings'],
                    ],
                ],

            ]
        ];
    }

    public function registerPermissions()
    {
        return [
            'flynsarmy.menu.access_menus'          => ['label' => 'Menus - Access Menus', 'tab' => 'Flynsarmy'],
            'flynsarmy.menu.access_menu_settings'  => ['label' => 'Menus - Access Settings', 'tab' => 'Flynsarmy'],
        ];
    }

    public function registerComponents()
    {
        return [
            '\Flynsarmy\Menu\Components\Menu' => 'menu',
        ];
    }

    public function register_flynsarmy_menu_item_types()
    {
        $types = [
            'Flynsarmy\\Menu\\MenuItemTypes\\Page' => [
                'label'       => 'Page',
                'alias'       => 'page',
                'description' => 'A link to a CMS Page',
            ],
            'Flynsarmy\\Menu\\MenuItemTypes\\Partial' => [
                'label'       => 'Partial',
                'alias'       => 'partial',
                'description' => 'Render a CMS Partial',
            ],
            'Flynsarmy\\Menu\\MenuItemTypes\\Link' => [
                'label'       => 'Link',
                'alias'       => 'link',
                'description' => 'A given URL',
            ],
        ];

        if (PluginManager::instance()->hasPlugin('Winter.Blog')) {
            $types['Flynsarmy\\Menu\\MenuItemTypes\\BlogPost'] = [
                'label'       => 'Blog Post',
                'alias'       => 'blog_post',
                'description' => 'A link to a Blog Post',
            ];

            $types['Flynsarmy\\Menu\\MenuItemTypes\\BlogCategory'] = [
                'label'       => 'Blog Category',
                'alias'       => 'blog_category',
                'description' => 'A link to a Blog Category',
            ];
        }

        return $types;
    }

    public function boot()
    {
        // Add 'Menus' to the CMS sidebar
        Event::listen('backend.menu.extendItems', function (NavigationManager $manager) {
            $manager->addSideMenuItems('WINTER.CMS', 'CMS', [
                'menus' => [
                    'label'        => 'Menus',
                    'icon'         => 'icon-bars',
                    'url'          => 'javascript:;',
                    'attributes'   => ['data-menu-item' => 'menus'],
                    'permissions'  => ['cms.manage_menus'],
                    'counterLabel' => 'cms::lang.page.unsaved_label'
                ],
            ]);
        });

        CmsController::extend(function (CmsController $controller) {
            // Handle the data to be passed to our new sidebar 'Menus' menu item.
            new MenuList($controller, 'menuList', function () {
                return Menu::all()->toArray();
            });

            // Override the CMS sidepanel partial. This is pretty awful as only
            // one plugin can ever do it at a time. We'll eventually want to
            // replace this with a fireViewEvent() or something.
            $controller->addViewPath("$/flynsarmy/menu/controllers/cms/index");

            $controller->formConfigs['menu'] = "$/flynsarmy/menu/models/menu/cmsfields.yaml";
            $controller->types['menu'] = Menu::class;
        });
    }
}
