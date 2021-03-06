<?php
/**
 * Created by PhpStorm.
 * User: volynets
 * Date: 01.09.17
 * Time: 10:01
 */

namespace core\readModels\Shop;

use core\entities\Blog\Post\Post;
use core\readModels\Shop\views\CatalogMenuView;
use Elasticsearch\Client;
use core\entities\Shop\Category\Category;
use core\readModels\Shop\views\CategoryView;
use yii\helpers\ArrayHelper;

class CategoryReadRepository
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getRoot(): Category
    {
        return Category::find()->roots()->one();
    }

    /**
     * @return Category[]
     */
    public function getAll(): array
    {
        return Category::find()->andWhere(['>', 'depth', 0])->orderBy('lft')->all();
    }

    public function find($id): ?Category
    {
        return Category::find()->andWhere(['id' => $id])->andWhere(['>', 'depth', 0])->one();
    }

    public function findBySlug($slug): ?Category
    {
        return Category::find()->andWhere(['slug' => $slug])->andWhere(['>', 'depth', 0])->one();
    }

    private function regulateCategory(CatalogMenuView $object, Category $category): CatalogMenuView
    {
        if ($children = $this->getChildrenOfParent($category)) {
            array_map(function (Category $child) use ($object){
                $item = $object->addChild($child);
                $this->regulateCategory($item, $child);
            }, $children);
        }
        return $object;
    }

    public function getAllTree(): array
    {
        $results = [];
        foreach ($this->getChildrenOfParent() as $child) {
            $parent = $this->regulateCategory(new CatalogMenuView($child), $child);
            $results[] = $parent;
        }
        return $results;
    }

    public function getChildrenOfParent(Category $prent = null): array
    {
        if (!$prent) {
            $prent = $this->getRoot();
        }

        return Category::find()->andWhere(['>', 'depth', 0])->orderBy('lft')
            ->andWhere(['and', ['>', 'lft', $prent->lft], ['<', 'rgt', $prent->rgt], ['depth' => $prent->depth + 1]])
            ->orderBy('lft')
            ->all();
    }

    public function getTreeWithSubsOf(Category $category = null): array
    {
        $query = Category::find()->andWhere(['>', 'depth', 0])->orderBy('lft');

        if ($category) {
            $criteria = ['or', ['depth' => 1]];
            foreach (ArrayHelper::merge([$category], $category->parents) as $item) {
                $criteria[] = ['and', ['>', 'lft', $item->lft], ['<', 'rgt', $item->rgt], ['depth' => $item->depth + 1]];
            }
            $query->andWhere($criteria);
        } else {
            $query->andWhere(['depth' => 1]);
        }

        $aggs = $this->client->search([
            'index' => 'shop',
            'type' => 'products',
            'body' => [
                'size' => 0,
                'aggs' => [
                    'group_by_category' => [
                        'terms' => [
                            'field' => 'categories',
                        ]
                    ]
                ],
            ],
        ]);

        $counts = ArrayHelper::map($aggs['aggregations']['group_by_category']['buckets'], 'key', 'doc_count');

        return array_map(function (Category $category) use ($counts) {
            return new CategoryView($category, ArrayHelper::getValue($counts, $category->id, 0));
        }, $query->all());
    }

}