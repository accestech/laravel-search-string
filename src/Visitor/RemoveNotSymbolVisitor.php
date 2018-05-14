<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

class RemoveNotSymbolVisitor implements Visitor
{
    protected $negate = false;

    public function visitOr(OrSymbol $or)
    {
        $leaves = $or->expressions->map->accept($this);
        return $this->negate ? new AndSymbol($leaves) : new OrSymbol($leaves);
    }

    public function visitAnd(AndSymbol $and)
    {
        $leaves = $and->expressions->map->accept($this);
        return $this->negate ? new OrSymbol($leaves) : new AndSymbol($leaves);
    }

    public function visitNot(NotSymbol $not)
    {
        if ($this->negate) {
            $this->negate = false;
            return $not->expression;
        }

        $this->negate = true;
        $newExpression = $not->expression->accept($this);
        $this->negate = false;
        return $newExpression;
    }

    public function visitQuery(QuerySymbol $query)
    {
        if (! $this->negate) {
            return $query;
        }

        if (is_bool($query->value)) {
            return new QuerySymbol($query->key, $query->operator, ! $query->value);
        }

        $reverseOperator = $this->reverseOperator($query->operator);
        return new QuerySymbol($query->key, $reverseOperator, $query->value);
    }

    private function reverseOperator($operator)
    {
        switch ($operator) {
            case '=': return '!=';
            case '!=': return '=';
            case '>': return '<=';
            case '>=': return '<';
            case '<': return '>=';
            case '<=': return '>';
            default: return $operator;
        }
    }
}