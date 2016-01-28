<?php
namespace ancor\data;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\QueryInterface;

use yii\data\ActiveDataProvider as _ActiveDataProvider;

/**
 * @inheritdoc
 * added a 'get parameter' for pagination
 * page-from-pk - value of primary key. (WHERE id <= `page-from-pk`)
 *
 * ```
 * ?page-from-pk=25
 * ```
 */
class ActiveDataProvider extends _ActiveDataProvider
{
    /**
     * @var mixed value of primary key
     */
    public $pageFromPk;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->pageFromPk = Yii::$app->request->get('page-from-pk');
    } // end init()
    
    /**
     * @inheritdoc
     */
    protected function prepareModels()
    {
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }
        $query = clone $this->query;
        if (($pagination = $this->getPagination()) !== false) {
            $pagination->totalCount = $this->getTotalCount();
            $query->limit($pagination->getLimit())->offset($pagination->getOffset());
        }
        if (($sort = $this->getSort()) !== false) {
            $query->addOrderBy($sort->getOrders());
        }
        $this->setPageFrom($query);
        return $query->all($this->db);
    }

    /**
     * @inheritdoc
     */
    protected function prepareTotalCount()
    {
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }
        $query = clone $this->query;
        $this->setPageFrom($query);
        return (int) $query->limit(-1)->offset(-1)->orderBy([])->count('*', $this->db);
    }

    /**
     * Set the page-from-pk option
     * @param QueryInterface $query
     */
    protected function setPageFrom( QueryInterface $query )
    {
        if ( $this->pageFromPk )
        {
            $class = $this->query->modelClass;
            $pks = $class::primaryKey();
            if ( count($pks) > 1) {
                throw new NotSupportedException('The "page-from-pk" filter can not be apply for composite primary key.');
            }
            $query->andWhere(['<=', $pks[0], $this->pageFromPk]);
        }
        return $query;
    } // end setPageFrom()
    
}
