<?php

namespace App\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\SqlWalker;

/**
 * SqlWalker extension to apply USE INDEX and FORCE INDEX hints using DQL on top of MySql.
 * Works with both createQuery and createQueryBuilder.
 *
 * Example:
 *  $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SqlIndexWalker::class);
 *  $query->setHint(SqlIndexWalker::HINT_INDEX, [
 *     'your_dql_table_alias' => 'FORCE INDEX FOR JOIN (your_composite_index) FORCE INDEX FOR ORDER BY (PRIMARY)',
 *     'your_another_dql_table_alias' => 'FORCE INDEX (PRIMARY)',
 *     ...
 * ]);
 *
 * @author Gergő Gänszler <ganszler.gergo@gmail.com>
 * @license https://github.com/ggergo/SqlIndexHintBundle/blob/master/LICENSE
 * @see https://github.com/ggergo/SqlIndexHintBundle
 *
 * @phpstan-import-type QueryComponent from Parser
 */
class SqlIndexWalker extends SqlWalker
{
    public const HINT_INDEX = 'SqlIndexWalker.Index';

    public const PREG_KEY_FROM = 'FROM';
    public const PREG_KEY_JOIN = 'JOIN';

    private EntityManagerInterface $em;


    /**
     * @param Query $query
     * @param ParserResult $parserResult
     * @param array<string, QueryComponent> $queryComponents
     */
    public function __construct($query, $parserResult, $queryComponents)
    {
        parent::__construct($query, $parserResult, $queryComponents);
        $this->em = $query->getEntityManager();
    }

    /**
     * Walks down a FromClause AST node, thereby generating the appropriate SQL with index hints.
     *
     * @param AST\FromClause $fromClause
     */
    public function walkFromClause($fromClause): string
    {
        $sql = parent::walkFromClause($fromClause);
        $hints = $this->getQuery()->getHint(self::HINT_INDEX);

        foreach ($this->getIndexHintParameters($fromClause) as $params) {
            if (!array_key_exists($params['dqlAlias'], $hints)) {
                continue;
            }

            $sql = $this->insertIndex($params['sqlKey'], $params['sqlTableAlias'], $hints[$params['dqlAlias']], $sql);
        }

        return $sql;
    }

    /**
     * Walks down a From clause, thereby generating parameters for index hints.
     */
    protected function getIndexHintParameters(AST\FromClause $fromClause): \Generator
    {
        foreach ($fromClause->identificationVariableDeclarations as $identificationVariableDecl) {
            yield $this->getFromIndexHintParams($identificationVariableDecl);
            foreach ($identificationVariableDecl->joins as $join) {
                yield $this->getJoinIndexHintParams($join);
            }
        }
    }

    /**
     * Returns parameters for FROM index hints.
     *
     * @return array<string, mixed>
     */
    protected function getFromIndexHintParams(AST\IdentificationVariableDeclaration $identificationVariableDecl): array
    {
        $params = [];

        $params['sqlKey'] = self::PREG_KEY_FROM;
        $params['dqlAlias'] = $identificationVariableDecl->rangeVariableDeclaration->aliasIdentificationVariable;

        $className = $identificationVariableDecl->rangeVariableDeclaration->abstractSchemaName;
        assert(class_exists($className));

        $class = $this->em->getClassMetadata($className);
        $params['sqlTableAlias'] = $this->getSQLTableAlias($class->getTableName(), $params['dqlAlias']);

        return $params;
    }

    /**
     * Returns parameters for JOIN index hints.
     *
     * @return array<string, mixed>
     */
    protected function getJoinIndexHintParams(AST\Join $join): array
    {
        $joinDeclaration = $join->joinAssociationDeclaration;
        assert($joinDeclaration instanceof AST\RangeVariableDeclaration);

        $params = [];
        $params['sqlKey'] = self::PREG_KEY_JOIN;
        $params['dqlAlias'] = $joinDeclaration->aliasIdentificationVariable;
        $params['sqlTableAlias'] = $this->getSqlTableAliasForJoin($join, $params['dqlAlias']);

        return $params;
    }

    /**
     * Returns table alias for JOIN index hints.
     */
    protected function getSqlTableAliasForJoin(AST\Join $join, string $dqlAlias): string
    {
        if ($join->joinAssociationDeclaration instanceof AST\RangeVariableDeclaration) {
            $className = $join->joinAssociationDeclaration->abstractSchemaName;
            assert(class_exists($className));

            $class = $this->em->getClassMetadata($className);

            return $this->getSQLTableAlias($class->table['name'], $dqlAlias);
        } elseif ($join->joinAssociationDeclaration instanceof AST\JoinAssociationDeclaration) {
            $queryComponent = $this->getQueryComponents()[$dqlAlias];
            assert(isset($queryComponent['relation']));

            $className = $queryComponent['relation']['targetEntity'];
            assert(class_exists($className));

            $targetClass = $this->em->getClassMetadata($className);

            return $this->getSQLTableAlias($targetClass->getTableName(), $dqlAlias);
        }

        return '';
    }

    /**
     * Inserts index hints into sql query string.
     */
    protected function insertIndex(
        string $sqlKey,
        string $sqlTableAlias,
        string $indexExp,
        string $sqlQueryString,
    ): string {
        return preg_replace(
            '#(\b(?i)' . $sqlKey . '\s*([\w\.]+\s+(?i)(as\s+)?' . $sqlTableAlias . ')\s*)#',
            '\1 ' . $indexExp . ' ',
            $sqlQueryString
        );
    }
}
