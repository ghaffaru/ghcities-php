<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DuplicateParam;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\UndefinedVariable;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use function strpos;
use function is_string;
use function in_array;
use function strtolower;
use function array_map;

/**
 * @internal
 */
class ClosureAnalyzer extends FunctionLikeAnalyzer
{
    /**
     * @var PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction
     */
    protected $function;

    /**
     * @param PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction $function
     */
    public function __construct(PhpParser\Node\FunctionLike $function, SourceAnalyzer $source)
    {
        $codebase = $source->getCodebase();

        $function_id = \strtolower($source->getFilePath())
            . ':' . $function->getLine()
            . ':' . (int)$function->getAttribute('startFilePos')
            . ':-:closure';

        $storage = $codebase->getClosureStorage($source->getFilePath(), $function_id);

        parent::__construct($function, $source, $storage);
    }

    public function getTemplateTypeMap(): ?array
    {
        return $this->source->getTemplateTypeMap();
    }

    /**
     * @return non-empty-lowercase-string
     */
    public function getClosureId(): string
    {
        return strtolower($this->getFilePath())
            . ':' . $this->function->getLine()
            . ':' . (int)$this->function->getAttribute('startFilePos')
            . ':-:closure';
    }

    /**
     * @param PhpParser\Node\Expr\Closure|PhpParser\Node\Expr\ArrowFunction $stmt
     */
    public static function analyzeExpression(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\FunctionLike $stmt,
        Context $context
    ) : bool {
        $closure_analyzer = new ClosureAnalyzer($stmt, $statements_analyzer);

        if ($stmt instanceof PhpParser\Node\Expr\Closure
            && self::analyzeClosureUses($statements_analyzer, $stmt, $context) === false
        ) {
            return false;
        }

        $use_context = new Context($context->self);

        $codebase = $statements_analyzer->getCodebase();

        if (!$statements_analyzer->isStatic()) {
            if ($context->collect_mutations &&
                $context->self &&
                $codebase->classExtends(
                    $context->self,
                    (string)$statements_analyzer->getFQCLN()
                )
            ) {
                /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
                $use_context->vars_in_scope['$this'] = clone $context->vars_in_scope['$this'];
            } elseif ($context->self) {
                $this_atomic = new TNamedObject($context->self);
                $this_atomic->was_static = true;

                $use_context->vars_in_scope['$this'] = new Type\Union([$this_atomic]);
            }
        }

        foreach ($context->vars_in_scope as $var => $type) {
            if (strpos($var, '$this->') === 0) {
                $use_context->vars_in_scope[$var] = clone $type;
            }
        }

        if ($context->self) {
            $self_class_storage = $codebase->classlike_storage_provider->get($context->self);

            ClassAnalyzer::addContextProperties(
                $statements_analyzer,
                $self_class_storage,
                $use_context,
                $context->self,
                $statements_analyzer->getParentFQCLN()
            );
        }

        foreach ($context->vars_possibly_in_scope as $var => $_) {
            if (strpos($var, '$this->') === 0) {
                $use_context->vars_possibly_in_scope[$var] = true;
            }
        }

        $byref_uses = [];

        if ($stmt instanceof PhpParser\Node\Expr\Closure) {
            foreach ($stmt->uses as $use) {
                if (!is_string($use->var->name)) {
                    continue;
                }

                $use_var_id = '$' . $use->var->name;

                if ($use->byRef) {
                    $byref_uses[$use_var_id] = true;
                }

                // insert the ref into the current context if passed by ref, as whatever we're passing
                // the closure to could execute it straight away.
                if (!$context->hasVariable($use_var_id, $statements_analyzer) && $use->byRef) {
                    $context->vars_in_scope[$use_var_id] = Type::getMixed();
                }

                $use_context->vars_in_scope[$use_var_id] =
                    $context->hasVariable($use_var_id, $statements_analyzer) && !$use->byRef
                    ? clone $context->vars_in_scope[$use_var_id]
                    : Type::getMixed();

                $use_context->vars_possibly_in_scope[$use_var_id] = true;
            }
        } else {
            $traverser = new PhpParser\NodeTraverser;

            $short_closure_visitor = new \Psalm\Internal\PhpVisitor\ShortClosureVisitor();

            $traverser->addVisitor($short_closure_visitor);
            $traverser->traverse($stmt->getStmts());

            foreach ($short_closure_visitor->getUsedVariables() as $use_var_id => $_) {
                $use_context->vars_in_scope[$use_var_id] =
                    $context->hasVariable($use_var_id, $statements_analyzer)
                    ? clone $context->vars_in_scope[$use_var_id]
                    : Type::getMixed();

                $use_context->vars_possibly_in_scope[$use_var_id] = true;
            }
        }

        $use_context->calling_method_id = $context->calling_method_id;

        $closure_analyzer->analyze($use_context, $statements_analyzer->node_data, $context, false, $byref_uses);

        if ($closure_analyzer->inferred_impure
            && $statements_analyzer->getSource() instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
        ) {
            $statements_analyzer->getSource()->inferred_impure = true;
        }

        if ($closure_analyzer->inferred_has_mutation
            && $statements_analyzer->getSource() instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer
        ) {
            $statements_analyzer->getSource()->inferred_has_mutation = true;
        }

        if (!$statements_analyzer->node_data->getType($stmt)) {
            $statements_analyzer->node_data->setType($stmt, Type::getClosure());
        }

        return true;
    }

    /**
     * @return  false|null
     */
    public static function analyzeClosureUses(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Closure $stmt,
        Context $context
    ): ?bool {
        $param_names = array_map(
            function (PhpParser\Node\Param $p) : string {
                if (!$p->var instanceof PhpParser\Node\Expr\Variable
                    || !is_string($p->var->name)
                ) {
                    return '';
                }
                return $p->var->name;
            },
            $stmt->params
        );

        foreach ($stmt->uses as $use) {
            if (!is_string($use->var->name)) {
                continue;
            }

            $use_var_id = '$' . $use->var->name;

            if (in_array($use->var->name, $param_names)) {
                if (IssueBuffer::accepts(
                    new DuplicateParam(
                        'Closure use duplicates param name ' . $use_var_id,
                        new CodeLocation($statements_analyzer->getSource(), $use->var)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            if (!$context->hasVariable($use_var_id, $statements_analyzer)) {
                if ($use_var_id === '$argv' || $use_var_id === '$argc') {
                    continue;
                }

                if ($use->byRef) {
                    $context->vars_in_scope[$use_var_id] = Type::getMixed();
                    $context->vars_possibly_in_scope[$use_var_id] = true;

                    if (!$statements_analyzer->hasVariable($use_var_id)) {
                        $statements_analyzer->registerVariable(
                            $use_var_id,
                            new CodeLocation($statements_analyzer, $use->var),
                            null
                        );
                    }

                    return null;
                }

                if (!isset($context->vars_possibly_in_scope[$use_var_id])) {
                    if ($context->check_variables) {
                        if (IssueBuffer::accepts(
                            new UndefinedVariable(
                                'Cannot find referenced variable ' . $use_var_id,
                                new CodeLocation($statements_analyzer->getSource(), $use->var)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return null;
                    }
                }

                $first_appearance = $statements_analyzer->getFirstAppearance($use_var_id);

                if ($first_appearance) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $use_var_id . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $use->var)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                if ($context->check_variables) {
                    if (IssueBuffer::accepts(
                        new UndefinedVariable(
                            'Cannot find referenced variable ' . $use_var_id,
                            new CodeLocation($statements_analyzer->getSource(), $use->var)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }
            } elseif ($use->byRef) {
                $context->remove($use_var_id);

                $context->vars_in_scope[$use_var_id] = Type::getMixed();
            }
        }

        return null;
    }
}
