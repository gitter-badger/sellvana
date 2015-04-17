<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Main
 *
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 */
class Sellvana_Catalog_Main extends BClass
{
    public function onLayoutEditorFetchLibrary($args)
    {
        $this->FCom_Core_LayoutEditor
            ->addLayoutType('product', [
                'title' => 'Product',
            ])
            ->addLayoutType('category', [
                'title' => 'Category',
            ])
            ->addDeclaredWidget('featured_products', [
                'title' => 'Featured Products',
                'view_name' => 'catalog/featured-products',
                'params' => [
                    'cnt' => [
                        'type' => 'input',
                        'args' => [
                            'label' => 'Products Count',
                            'type' => 'number',
                            'value' => 6,
                        ],
                    ],
                    'auto_scroll' => [
                        'type' => 'boolean',
                        'args' => [
                            'label' => 'Auto Scroll',
                        ]
                    ]
                ],
            ])
            ->addDeclaredWidget('popular_products', [
                'title' => 'Popular Products',
                'view_name' => 'catalog/popular-products',
                'params' => [
                    'cnt' => [
                        'type' => 'input',
                        'args' => [
                            'label' => 'Products Count',
                            'type' => 'number',
                            'value' => 6,
                        ],
                    ],
                ],
            ])
            ->addWidgetType('product_carousel', [
                'title' => 'Products Carousel',
                'source_view' => 'catalog/products/carousel',
                'view' => 'catalog/products/carousel',
                'pos' => 100,
                'compile' => function ($args) {
                    $w = $args['widget'];
                    $args['layout'][] = ['hook' => $w['area'], 'views' => $w['view']];
                    $args['layout'][] = ['view' => $w['view'], 'set' => ['skus' => $w['value'], 'widget_id' => $w['id']]];
                }
            ])
        ;
    }
}
