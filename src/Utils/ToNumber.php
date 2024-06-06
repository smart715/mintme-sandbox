<?php declare(strict_types = 1);

namespace App\Utils;

use App\Wallet\Money\MoneyWrapper;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

// SQL code for user-defined
// function to_number can be found on migration Version20200511143157
class ToNumber extends FunctionNode
{
    public ?Node $number = null; // phpcs:ignore
    public ?Node $subunit = null; // phpcs:ignore
    public ?Node $showSubunit = null; // phpcs:ignore


    public function parseInputParameters(Parser $parser): void
    {
        $lexer = $parser->getLexer();

        if ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);

            $this->subunit = $lexer->isNextToken(Lexer::T_INPUT_PARAMETER)
                ? $parser->InputParameter()
                : $parser->StateFieldPathExpression();
        }

        if ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);

            $this->showSubunit = $lexer->isNextToken(Lexer::T_INPUT_PARAMETER)
                ? $parser->InputParameter()
                : $parser->StateFieldPathExpression();
        }
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->number = $parser->getLexer()->isNextToken(Lexer::T_INPUT_PARAMETER)
            ? $parser->InputParameter()
            : $parser->StateFieldPathExpression();

        $this->parseInputParameters($parser);

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getParametersSql(SqlWalker $sqlWalker): string
    {
        $sql = '';
        $sql .= $this->subunit
            ? $this->subunit->dispatch($sqlWalker)
            : MoneyWrapper::MINTME_SUBUNIT;

        $sql .= ', ' . ($this->showSubunit
            ? $this->showSubunit->dispatch($sqlWalker)
            : MoneyWrapper::MINTME_SHOW_SUBUNIT);

        return $sql;
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $sql = 'to_number(' . $this->number->dispatch($sqlWalker) . ', ';
        $sql .= $this->getParametersSql($sqlWalker);

        return $sql.')';
    }
}
