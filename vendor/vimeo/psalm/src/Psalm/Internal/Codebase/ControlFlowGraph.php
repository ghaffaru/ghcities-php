<?php

namespace Psalm\Internal\Codebase;

use Psalm\CodeLocation;
use Psalm\Internal\ControlFlow\Path;
use Psalm\Internal\ControlFlow\TaintSink;
use Psalm\Internal\ControlFlow\TaintSource;
use Psalm\Internal\ControlFlow\ControlFlowNode;
use Psalm\IssueBuffer;
use Psalm\Issue\TaintedInput;
use function array_merge;
use function count;
use function implode;
use function substr;
use function strlen;
use function array_intersect;
use function array_reverse;

class ControlFlowGraph
{
    /** @var array<string, array<string, Path>> */
    protected $forward_edges = [];

    public function addNode(ControlFlowNode $node) : void
    {
    }

    /**
     * @param array<string> $added_taints
     * @param array<string> $removed_taints
     */
    public function addPath(
        ControlFlowNode $from,
        ControlFlowNode $to,
        string $path_type,
        ?array $added_taints = null,
        ?array $removed_taints = null
    ) : void {
        $from_id = $from->id;
        $to_id = $to->id;

        if ($from_id === $to_id) {
            return;
        }

        $this->forward_edges[$from_id][$to_id] = new Path($path_type, $added_taints, $removed_taints);
    }

    /**
     * @param array<string> $previous_path_types
     *
     * @psalm-pure
     */
    protected static function shouldIgnoreFetch(
        string $path_type,
        string $expression_type,
        array $previous_path_types
    ) : bool {
        $el = \strlen($expression_type);

        if (substr($path_type, 0, $el + 7) === $expression_type . '-fetch-') {
            $fetch_nesting = 0;

            $previous_path_types = array_reverse($previous_path_types);

            foreach ($previous_path_types as $previous_path_type) {
                if ($previous_path_type === $expression_type . '-assignment') {
                    if ($fetch_nesting === 0) {
                        return false;
                    }

                    $fetch_nesting--;
                }

                if (substr($previous_path_type, 0, $el + 6) === $expression_type . '-fetch') {
                    $fetch_nesting++;
                }

                if (substr($previous_path_type, 0, $el + 12) === $expression_type . '-assignment-') {
                    if ($fetch_nesting > 0) {
                        $fetch_nesting--;
                        continue;
                    }

                    if (substr($previous_path_type, $el + 12) === substr($path_type, $el + 7)) {
                        return false;
                    }

                    return true;
                }
            }
        }

        return false;
    }
}
