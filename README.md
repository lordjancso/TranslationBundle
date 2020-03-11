[![Build Status](https://travis-ci.com/lordjancso/TranslationBundle.svg?branch=develop)](https://travis-ci.com/lordjancso/TranslationBundle)
[![Coverage Status](https://coveralls.io/repos/github/lordjancso/TranslationBundle/badge.svg?branch=develop)](https://coveralls.io/github/lordjancso/TranslationBundle?branch=develop)

[![Latest Stable Version](https://poser.pugx.org/lordjancso/translation-bundle/v/stable?format=flat)](https://packagist.org/packages/lordjancso/translation-bundle)
[![Latest Unstable Version](https://poser.pugx.org/lordjancso/translation-bundle/v/unstable?format=flat)](https://packagist.org/packages/lordjancso/translation-bundle)

[![Total Downloads](https://poser.pugx.org/lordjancso/translation-bundle/downloads?format=flat)](https://packagist.org/packages/lordjancso/translation-bundle)
[![Monthly Downloads](https://poser.pugx.org/lordjancso/translation-bundle/d/monthly?format=flat)](https://packagist.org/packages/lordjancso/translation-bundle)
[![Daily Downloads](https://poser.pugx.org/lordjancso/translation-bundle/d/daily?format=flat)](https://packagist.org/packages/lordjancso/translation-bundle)

[![License](https://poser.pugx.org/lordjancso/translation-bundle/license?format=flat)](https://packagist.org/packages/lordjancso/translation-bundle)

## Installation

```
composer require lordjancso/translation-bundle dev-master
```

## Configuration

```
# config/packages/lordjancso_translation.yaml

lordjancso_translation:
    managed_locales: ['en', 'fr', 'de']
```

Optionally you can add a route to check your translation progress.

```
# config/routes/dev/lordjancso_translation.yaml

lordjancso_translation:
    resource: "@LordjancsoTranslationBundle/Resources/config/routes.xml"
    prefix: /lordjancso-translation
```

Create the database tables with Doctrine or add them manually.

```
CREATE TABLE lj_translation_keys (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE `utf8_bin`, domain VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_5D812D955E237E06A7A91E0B (name, domain), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE lj_translation_values (id INT AUTO_INCREMENT NOT NULL, domain_id INT NOT NULL, key_id INT NOT NULL, content VARCHAR(255) NOT NULL, locale VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_F034BEAA115F0EE5 (domain_id), INDEX IDX_F034BEAAD145533 (key_id), UNIQUE INDEX UNIQ_F034BEAAD1455334180C698 (key_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE lj_translation_domains (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, locale VARCHAR(255) NOT NULL, path VARCHAR(255) DEFAULT NULL, hash VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_F3DB21D45E237E064180C698 (name, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE lj_translation_values ADD CONSTRAINT FK_F034BEAA115F0EE5 FOREIGN KEY (domain_id) REFERENCES lj_translation_domains (id);
ALTER TABLE lj_translation_values ADD CONSTRAINT FK_F034BEAAD145533 FOREIGN KEY (key_id) REFERENCES lj_translation_keys (id);
```

## Usage

### Import

Create your translation files into your translations folder and run the import command.

```
php bin/console lordjancso:import-translations
```

### Export

If your translations changed in the database, you should export them.

```
php bin/console lordjancso:export-translations
```

## Limitations

- Only supports Doctrine ORM with MySQL

## TODO

- Invalidate and update translation cache if it changes in the database

## Plans

- Symfony Flex configuration recipe
  - https://symfony.com/doc/current/bundles/best_practices.html#installation

- Add new options to export command
  - locales to select locales to export
  - domains to select domains to export
  - format to set the export file format
  - override to delete current translation files

- Add new options to import command
  - locales to select locales to import
  - domains to select domains to import
  - merge to keep database data

- Support as many Symfony versions as possible

- Google Translate api integration
