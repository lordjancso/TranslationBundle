# TranslationBundle

Translation bundle for Symfony to manage your translations in the database.

[![CI](https://github.com/lordjancso/TranslationBundle/actions/workflows/ci.yml/badge.svg)](https://github.com/lordjancso/TranslationBundle/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/lordjancso/translation-bundle/v/stable?format=flat)](https://packagist.org/packages/lordjancso/translation-bundle)
[![Total Downloads](https://poser.pugx.org/lordjancso/translation-bundle/downloads?format=flat)](https://packagist.org/packages/lordjancso/translation-bundle)
[![License](https://poser.pugx.org/lordjancso/translation-bundle/license?format=flat)](https://packagist.org/packages/lordjancso/translation-bundle)

## Requirements

- PHP 8.2+
- Symfony 6.4 / 7.4 / 8.0
- Doctrine ORM

## Installation

```bash
composer require lordjancso/translation-bundle
```

## Configuration

```yaml
# config/packages/lordjancso_translation.yaml

lordjancso_translation:
    managed_locales: ['en', 'fr', 'de']
```

Optionally you can add a route to check your translation progress.

```yaml
# config/routes/dev/lordjancso_translation.yaml

lordjancso_translation:
    resource: "@LordjancsoTranslationBundle/Resources/config/routes.xml"
    prefix: /lordjancso-translation
```

Create the database tables with Doctrine:

```bash
php bin/console doctrine:schema:update --force
```

## Usage

### Import

Create your translation files in the `translations/` folder and run the import command.

```bash
php bin/console lordjancso:import-translations
```

### Export

If your translations changed in the database, export them back to files.

```bash
php bin/console lordjancso:export-translations
```

## Testing

```bash
./vendor/bin/simple-phpunit
```

## Limitations

- Only supports Doctrine ORM with MySQL
