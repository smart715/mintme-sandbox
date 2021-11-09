<?php declare(strict_types = 1);

namespace App\Utils;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

// SQL code for user-defined function change_percentage can be found on migration Version20200511143157
class ChangePercentage extends FunctionNode
{
    /** @var PathExpression|null */
    public $lastPrice = null;

    /** @var PathExpression|null */
    public $openPrice = null;

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->lastPrice = $parser->StateFieldPathExpression();
        $parser->match(Lexer::T_COMMA);
        $this->openPrice = $parser->StateFieldPathExpression();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'change_percentage(' .
            $this->lastPrice->dispatch($sqlWalker) . ', ' .
            $this->openPrice->dispatch($sqlWalker) .
        ')';
    }
}
