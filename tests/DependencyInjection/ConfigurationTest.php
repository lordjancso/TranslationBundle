<?php

namespace Lordjancso\TranslationBundle\Tests\DependencyInjection;

use Lordjancso\TranslationBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testProcessConfigurationException(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('Invalid type for path "lordjancso_translation.managed_locales". Expected string, but got integer at array position #3');

        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, [
            [
                'managed_locales' => ['dk', 'de', 'en', 1],
            ],
        ]);
    }

    /**
     * @dataProvider processConfigurationProvider
     */
    public function testProcessConfiguration(array $configs, array $expected): void
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $this->assertEquals($expected, $config);
    }

    public function processConfigurationProvider(): array
    {
        return [
            [
                [
                    [
                        'managed_locales' => ['dk', 'de', 'en'],
                    ],
                ],
                [
                    'managed_locales' => ['dk', 'de', 'en'],
                ],
            ],
        ];
    }
}
