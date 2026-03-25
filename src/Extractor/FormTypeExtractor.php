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

class FormTypeExtractor implements ExtractorInterface
{
    public const TRANSLATABLE_OPTIONS = ['label', 'help', 'placeholder', 'empty_value'];
    public const TRANSLATABLE_ATTR_KEYS = ['placeholder', 'title'];

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

        if (!str_contains($content, 'AbstractType')) {
            return;
        }

        $nodes = $this->parser->parse($content);

        if (null === $nodes) {
            return;
        }

        $translationDomain = null;
        $messages = [];
        $validatorMessages = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class($translationDomain, $messages, $validatorMessages) extends NodeVisitorAbstract {
            public function __construct(
                private ?string &$translationDomain,
                private array &$messages,
                private array &$validatorMessages,
            ) {
            }

            public function enterNode(Node $node): ?int
            {
                // Look for: $resolver->setDefaults(['translation_domain' => '...'])
                if ($node instanceof Node\Expr\MethodCall
                    && $node->name instanceof Node\Identifier
                    && 'setDefaults' === $node->name->name
                    && isset($node->args[0])
                    && $node->args[0]->value instanceof Node\Expr\Array_
                ) {
                    foreach ($node->args[0]->value->items as $item) {
                        if ($item instanceof Node\ArrayItem
                            && $item->key instanceof Node\Scalar\String_
                            && 'translation_domain' === $item->key->value
                            && $item->value instanceof Node\Scalar\String_
                        ) {
                            $this->translationDomain = $item->value->value;
                        }
                    }
                }

                // Look for: $builder->add('name', Type::class, [...options...])
                if ($node instanceof Node\Expr\MethodCall
                    && $node->name instanceof Node\Identifier
                    && 'add' === $node->name->name
                    && isset($node->args[2])
                    && $node->args[2]->value instanceof Node\Expr\Array_
                ) {
                    $this->extractOptionsFromArray($node->args[2]->value);
                }

                return null;
            }

            private function extractOptionsFromArray(Node\Expr\Array_ $array): void
            {
                foreach ($array->items as $item) {
                    if (!$item instanceof Node\ArrayItem || !$item->key instanceof Node\Scalar\String_) {
                        continue;
                    }

                    $key = $item->key->value;

                    // Translatable top-level options: label, help, placeholder, empty_value
                    if (\in_array($key, FormTypeExtractor::TRANSLATABLE_OPTIONS, true)
                        && $item->value instanceof Node\Scalar\String_
                    ) {
                        $this->messages[] = $item->value->value;
                    }

                    // invalid_message → validators domain
                    if ('invalid_message' === $key && $item->value instanceof Node\Scalar\String_) {
                        $this->validatorMessages[] = $item->value->value;
                    }

                    // choices => ['Label' => value, ...] — keys are translatable
                    if ('choices' === $key && $item->value instanceof Node\Expr\Array_) {
                        $this->extractChoiceKeys($item->value);
                    }

                    // attr => ['placeholder' => '...', 'title' => '...']
                    if ('attr' === $key && $item->value instanceof Node\Expr\Array_) {
                        $this->extractAttrStrings($item->value);
                    }
                }
            }

            private function extractChoiceKeys(Node\Expr\Array_ $choicesArray): void
            {
                foreach ($choicesArray->items as $choiceItem) {
                    if ($choiceItem instanceof Node\ArrayItem
                        && $choiceItem->key instanceof Node\Scalar\String_
                    ) {
                        $this->messages[] = $choiceItem->key->value;
                    }
                }
            }

            private function extractAttrStrings(Node\Expr\Array_ $attrArray): void
            {
                foreach ($attrArray->items as $attrItem) {
                    if ($attrItem instanceof Node\ArrayItem
                        && $attrItem->key instanceof Node\Scalar\String_
                        && \in_array($attrItem->key->value, FormTypeExtractor::TRANSLATABLE_ATTR_KEYS, true)
                        && $attrItem->value instanceof Node\Scalar\String_
                    ) {
                        $this->messages[] = $attrItem->value->value;
                    }
                }
            }
        });

        $traverser->traverse($nodes);

        if (null === $translationDomain || (empty($messages) && empty($validatorMessages))) {
            return;
        }

        foreach ($messages as $message) {
            $catalogue->set($this->prefix.$message, $this->prefix.$message, $translationDomain);
        }

        foreach ($validatorMessages as $message) {
            $catalogue->set($this->prefix.$message, $this->prefix.$message, 'validators');
        }
    }
}
