<?php

namespace Lordjancso\TranslationBundle\Extractor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class ConstraintMessageExtractor implements ExtractorInterface
{
    private Parser $parser;
    private string $prefix = '';

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForHostVersion();
    }

    public function extract(string|iterable $resource, MessageCatalogue $catalogue): void
    {
        if (is_iterable($resource)) {
            foreach ($resource as $path) {
                $this->extractFromPath((string) $path, $catalogue);
            }

            return;
        }

        $this->extractFromPath($resource, $catalogue);
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    private function extractFromPath(string $path, MessageCatalogue $catalogue): void
    {
        if (is_file($path)) {
            $this->extractFromFile($path, $catalogue);

            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $finder = (new Finder())->files()->name('*.php')->in($path);

        foreach ($finder as $file) {
            $this->extractFromFile($file->getRealPath(), $catalogue);
        }
    }

    private function extractFromFile(string $file, MessageCatalogue $catalogue): void
    {
        $content = file_get_contents($file);

        if (!str_contains($content, 'Constraints') && !str_contains($content, 'Constraint')) {
            return;
        }

        $nodes = $this->parser->parse($content);

        if (null === $nodes) {
            return;
        }

        $messages = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($messages) extends NodeVisitorAbstract {
            public function __construct(
                private array &$messages,
            ) {
            }

            public function leaveNode(Node $node): ?int
            {
                // Match: new SomeConstraint([...'someMessage' => '...', ...])
                // and #[SomeConstraint(someMessage: '...')]
                if (!$node instanceof Node\Expr\New_ && !$node instanceof Node\Attribute) {
                    return null;
                }

                $className = $node instanceof Node\Attribute ? $node->name : $node->class;

                if (!$className instanceof Node\Name) {
                    return null;
                }

                $name = $className->getLast();

                // Skip non-constraint classes
                if (!str_contains($name, 'Constraint') && !$this->looksLikeConstraint($name)) {
                    return null;
                }

                $this->extractFromArgs($node);

                return null;
            }

            private function looksLikeConstraint(string $name): bool
            {
                // Common Symfony constraint class names
                return \in_array($name, [
                    'NotBlank', 'NotNull', 'Length', 'Range', 'Email', 'Url', 'Regex',
                    'Type', 'Choice', 'Count', 'UniqueEntity', 'Unique', 'Valid',
                    'File', 'Image', 'Callback', 'Expression', 'Luhn', 'Iban', 'Bic',
                    'CardScheme', 'Currency', 'Isbn', 'Issn', 'EqualTo', 'NotEqualTo',
                    'IdenticalTo', 'NotIdenticalTo', 'LessThan', 'LessThanOrEqual',
                    'GreaterThan', 'GreaterThanOrEqual', 'Positive', 'PositiveOrZero',
                    'Negative', 'NegativeOrZero', 'Date', 'DateTime', 'Time', 'Timezone',
                    'Locale', 'Language', 'Country', 'Json', 'Ulid', 'Uuid',
                    'UserPassword', 'IsTrue', 'IsFalse', 'Blank',
                ], true);
            }

            private function extractFromArgs(Node\Expr\New_|Node\Attribute $node): void
            {
                foreach ($node->args as $arg) {
                    // Named argument: SomeConstraint(someMessage: '...')
                    if ($arg instanceof Node\Arg
                        && $arg->name instanceof Node\Identifier
                        && str_contains(strtolower($arg->name->name), 'message')
                        && $arg->value instanceof Node\Scalar\String_
                    ) {
                        $this->messages[] = $arg->value->value;
                    }

                    // Array argument: new SomeConstraint(['someMessage' => '...'])
                    if ($arg instanceof Node\Arg && $arg->value instanceof Node\Expr\Array_) {
                        foreach ($arg->value->items as $item) {
                            if ($item instanceof Node\ArrayItem
                                && $item->key instanceof Node\Scalar\String_
                                && str_contains(strtolower($item->key->value), 'message')
                                && $item->value instanceof Node\Scalar\String_
                            ) {
                                $this->messages[] = $item->value->value;
                            }
                        }
                    }
                }
            }
        });

        $traverser->traverse($nodes);

        foreach ($messages as $message) {
            $catalogue->set($this->prefix.$message, $this->prefix.$message, 'validators');
        }
    }
}
