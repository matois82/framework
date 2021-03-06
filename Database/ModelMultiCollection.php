<?php
namespace Colibri\Database;

use Colibri\Database;
use Colibri\Util\Arr;

/**
 * ObjectMultiCollection.
 */
class ModelMultiCollection extends ModelCollection
{
    /** @var string */
    protected $fkTableName = 'fkTableName_not_set';
    /** @var array */
    private $fkTableFields = [];
    /** @var array */
    private $intermediateFields = [];

    /**
     * ObjectMultiCollection constructor.
     *
     * @param mixed $parentID
     *
     * @throws \Colibri\Database\DbException
     */
    public function __construct($parentID = null)
    {
        parent::__construct($parentID);

        $metadata                 = static::db()->metadata()->getColumnsMetadata($this->fkTableName);
        $this->fkTableFields      = &$metadata['fields'];
        $this->intermediateFields = array_diff($this->fkTableFields, $this->FKName);
    }

    /**
     * @return \Colibri\Database\Query
     */
    protected function query(): Query
    {
        return $this->intermediateFields
            ? Query::select(['*'], $this->intermediateFields)
            : Query::select(['*'])
        ;
    }

    /**
     * @return Query
     */
    protected function addToDbQuery(): Query
    {
        return Query::insert()->into($this->fkTableName)->set([
            $this->FKName[0] => $this->FKValue[0],
            $this->FKName[1] => $this->FKValue[1],
        ]);
    }

    /**
     * @return Query
     */
    protected function delFromDbQuery(): Query
    {
        return Query::delete()->from($this->fkTableName)->where([
            $this->FKName[0] => $this->FKValue[0],
            $this->FKName[1] => $this->FKValue[1],
        ]);
    }

    /**
     * @return Query
     */
    protected function selFromDbAllQuery(): Query
    {
        $query = $this->FKValue[0] !== null
            ? $this->getQuery()
                ->from(static::$itemClass::getTableName())
                ->join($this->fkTableName, $this->FKName[1], 'id', Query\JoinType::INNER)
                ->where([
                    'j1.' . $this->FKName[0] => $this->FKValue[0],
                ])
            : $this->getQuery()
                ->from(static::$itemClass::getTableName())
        ;

        return $query;
    }

    /**
     * @return Query
     */
    protected function delFromDbAllQuery(): Query
    {
        return Query::delete()
            ->from($this->fkTableName)
            ->where([$this->FKName[0] => $this->FKValue[0]]);
    }

    // with Items
    ///////////////////////////////////////////////////////////////////////////

    /**
     * @param array $row
     *
     * @return \Colibri\Database\Model
     */
    protected function instantiateItem(array $row)
    {
        if ( ! count($this->intermediateFields)) {
            return parent::instantiateItem($row);
        }

        $itemAttributes         = Arr::only($row, $this->itemFields);
        $intermediateAttributes = Arr::only($row, $this->intermediateFields);
        /** @var \Colibri\Database\Model $item */
        $item = new static::$itemClass($itemAttributes);

        return $item->setIntermediate($intermediateAttributes);
    }

    // with DataBase
    ///////////////////////////////////////////////////////////////////////////

    /**
     * @param \Colibri\Database\Model $object
     *
     * @throws \Colibri\Database\DbException
     * @throws \Colibri\Database\Exception\SqlException
     * @throws \UnexpectedValueException
     */
    protected function addToDb(Database\Model &$object)
    {
        $this->FKValue[1] = $object->id;
        $this->doQuery($this->addToDbQuery());
    }

    /**
     * @param int $id
     *
     * @throws \Colibri\Database\DbException
     * @throws \Colibri\Database\Exception\SqlException
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    protected function delFromDb($id)
    {
        $this->FKValue[1] = $id;
        $this->doQuery($this->delFromDbQuery());
    }

    /**
     * @throws \Colibri\Database\DbException
     * @throws \Colibri\Database\Exception\SqlException
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    protected function delFromDbAll()
    {
        $this->doQuery($this->delFromDbAllQuery());
    }
}
