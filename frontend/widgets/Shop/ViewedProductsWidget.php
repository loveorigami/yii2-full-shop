<?php
/**
 * Created by PhpStorm.
 * User: volynets
 * Date: 04.09.17
 * Time: 14:20
 */

namespace frontend\widgets\Shop;


use core\readModels\Shop\ProductReadRepository;
use yii\base\Widget;

class ViewedProductsWidget extends Widget
{
    private $repository;
    public $title = 'ПРОСМОТРЕННЫЕ';
    public $limit = 4;
    public $class = 'product-line-1';
    public $viewAll = 'viewed-products';

    public function __construct(ProductReadRepository $repository, array $config = [])
    {
        parent::__construct($config);
        $this->repository = $repository;
    }

    public function run()
    {
        return $this->render('product-line', [
            'products' => $this->repository->getViewed($this->limit),
            'title' => $this->title,
            'class' => $this->class,
            'viewAll' => $this->viewAll,
        ]);
    }

}