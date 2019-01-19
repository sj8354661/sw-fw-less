<?php

namespace App\models;

use App\components\ModelQuery;
use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryInterface;

abstract class AbstractMysqlModel extends AbstractModel
{
    protected static $table = '';

    /**
     * @return ModelQuery|QueryInterface|SelectInterface|InsertInterface|DeleteInterface|UpdateInterface
     */
    public static function select()
    {
        return ModelQuery::select()->from(static::$table)->setModelClass(static::class);
    }

    /**
     * @return ModelQuery|QueryInterface|SelectInterface|InsertInterface|DeleteInterface|UpdateInterface
     */
    public static function update()
    {
        return ModelQuery::update()->table(static::$table)->setModelClass(static::class);
    }

    /**
     * @return ModelQuery|QueryInterface|SelectInterface|InsertInterface|DeleteInterface|UpdateInterface
     */
    public static function insert()
    {
        return ModelQuery::insert()->into(static::$table)->setModelClass(static::class);
    }

    /**
     * @return ModelQuery|QueryInterface|SelectInterface|InsertInterface|DeleteInterface|UpdateInterface
     */
    public static function delete()
    {
        return ModelQuery::delete()->from(static::$table)->setModelClass(static::class);
    }

    /**
     * @return bool
     */
    public function save()
    {
        $this->fireEvent('saving');

        $primaryKey = static::$primaryKey;

        $attributes = $this->toArray();

        if (count($attributes) > 0) {
            $primaryValue = $this->{$primaryKey};
            if ($primaryValue) {
                if (count($attributes) > 1) {
                    $this->fireEvent('updating');
                    $attributes = $this->toArray();
                    $updateBuilder = static::update();
                    $updateBuilder->where("`{$primaryKey}` = :primaryValue", ['primaryValue' => $primaryValue]);
                    foreach ($attributes as $attributeName => $attribute) {
                        if ($attributeName == $primaryKey) {
                            continue;
                        }

                        $updateBuilder->col($attributeName)->bindValue($attributeName, $this->{$attributeName});
                    }
                    $res = $updateBuilder->write();
                    if ($res > 0) {
                        $this->fireEvent('updated');
                        $this->fireEvent('saved');
                    }
                    return true;
                }
            } else {
                $this->fireEvent('creating');
                $attributes = $this->toArray();
                $insertBuilder = static::insert();
                foreach ($attributes as $attributeName => $attribute) {
                    $insertBuilder->col($attributeName)->bindValue($attributeName, $this->{$attributeName});
                }

                $res = $insertBuilder->write() > 0;

                $lastInsetId = $insertBuilder->getLastInsertId();
                if ($lastInsetId) {
                    $this->setPrimaryValue($lastInsetId);
                }

                if ($res) {
                    $this->fireEvent('created');
                    $this->fireEvent('saved');
                }

                return $res;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function del()
    {
        $this->fireEvent('deleting');

        $primaryKey = static::$primaryKey;
        $primaryValue = $this->{$primaryKey};
        if ($primaryValue) {
            $res = static::delete()->where("`{$primaryKey}` = :primaryValue", ['primaryValue' => $primaryValue])
                ->write();

            if ($res > 0) {
                $this->fireEvent('deleted');
            }
            return true;
        }

        return false;
    }
}
