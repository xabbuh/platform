<?php

namespace Oro\Bundle\EntityBundle\Migrations\Extension;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeTypeExtension implements DatabasePlatformAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var ForeignKeyConstraint[]
     */
    protected $foreignKeys = [];

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * @param Schema   $schema
     * @param QueryBag $queries
     * @param string   $tableName
     * @param string   $columnName
     * @param string   $type
     *
     * @throws \Exception
     */
    public function changePrimaryKeyType(Schema $schema, QueryBag $queries, $tableName, $columnName, $type)
    {
        $targetColumn = $schema->getTable($tableName)->getColumn($columnName);
        $type         = Type::getType($type);

        if ($targetColumn->getType() === $type) {
            return;
        }

        foreach ($schema->getTables() as $table) {
            /** @var ForeignKeyConstraint[] $foreignKeys */
            $foreignKeys = array_filter(
                $table->getForeignKeys(),
                function (ForeignKeyConstraint $foreignKey) use ($tableName, $columnName) {
                    if ($foreignKey->getForeignTableName() !== $tableName) {
                        return false;
                    }

                    if ($foreignKey->getForeignColumns() !== [$columnName]) {
                        return false;
                    }

                    return true;
                }
            );

            foreach ($foreignKeys as $foreignKey) {
                $this->foreignKeys[$foreignKey->getName()] = $foreignKey;

                $foreignKeyTableName   = $foreignKey->getLocalTable()->getName();
                $foreignKeyColumnNames = $foreignKey->getLocalColumns();

                $queries->addPreQuery(
                    $this->platform->getDropForeignKeySQL($foreignKey, $foreignKeyTableName)
                );

                $schema
                    ->getTable($foreignKeyTableName)
                    ->getColumn(reset($foreignKeyColumnNames))
                    ->setType($type);
            }
        }

        $targetColumn->setType($type);

        foreach ($this->foreignKeys as $foreignKey) {
            $queries->addPostQuery(
                $this->platform->getCreateForeignKeySQL($foreignKey, $foreignKey->getLocalTable())
            );
        }

        $this->foreignKeys = [];
    }
}
