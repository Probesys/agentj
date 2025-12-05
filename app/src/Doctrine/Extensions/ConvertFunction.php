<?php

namespace App\Doctrine\Extensions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

class ConvertFunction extends FunctionNode
{
    public Node $expr;
    public Node $charset;


    public function parse(Parser $parser)
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->expr = $parser->StringPrimary();
        $parser->match(TokenType::T_IDENTIFIER); // USING
        $this->charset = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'CONVERT(' .
            $this->expr->dispatch($sqlWalker) . ' USING ' .
            $this->charset->dispatch($sqlWalker) .
        ')';
    }
}
