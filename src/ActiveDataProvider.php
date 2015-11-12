<?php
namespace ancor\rest;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\QueryInterface;

use yii\data\ActiveDataProvider as _ActiveDataProvider;

/**
 * @inheritdoc
 * added a 'get parameter' for pagination
 * page-from - value of primary key. (WHERE id <= `page-from`)
 *
 * ```
 * ?page-from=25
 * ```
 */
class ActiveDataProvider extends _ActiveDataProvider
{
    /**
     * @var mixed value of primary key
     */
    public $pageFrom;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->pageFrom = Yii::$app->request->get('page-from');
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
     * Set the page-from option
     * @param QueryInterface $query
     */
    protected function setPageFrom( QueryInterface $query )
    {
        if ( $this->pageFrom )
        {
            $class = $this->query->modelClass;
            $pks = $class::primaryKey();
            if ( count($pks) > 1) {
                throw new NotSupportedException('The "page-from" filter can not be apply for composite primary key.');
            }
            $query->andWhere(['<=', $pks[0], $this->pageFrom]);
        }
        return $query;
    } // end setPageFrom()
    
}
