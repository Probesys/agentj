<?php

namespace App\Doctrine\Extensions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

use function implode;
use function sprintf;
use function strtolower;

class MatchFunction extends FunctionNode
{
    /** @var PathExpression[] */
    private array $fields = [];

    private Node $against;

    private bool $booleanMode = false;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->fields[] = $parser->StateFieldPathExpression();
        $lexer = $parser->getLexer();
        while ($lexer->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);
            $this->fields[] = $parser->StateFieldPathExpression();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);

        if (strtolower($lexer->lookahead->value) !== 'against') {
            $parser->syntaxError('against');
        }

        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->against = $parser->StringPrimary();

        if (strtolower($lexer->lookahead->value) === 'boolean') {
            $parser->match(TokenType::T_IDENTIFIER);
            $this->booleanMode = true;
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $walker): string
    {
        $sqlFields = [];
        foreach ($this->fields as $field) {
            $sqlFields[] = $field->dispatch($walker);
        }

        $againstSql = $walker->walkStringPrimary($this->against);
        if ($this->booleanMode) {
            $againstSql .= ' IN BOOLEAN MODE';
        }

        return sprintf(
            'MATCH (%s) AGAINST (%s)',
            implode(', ', $sqlFields),
            $againstSql
        );
    }
}
