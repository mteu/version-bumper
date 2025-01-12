<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/version-bumper".
 *
 * Copyright (C) 2024-2025 Elias Häußler <elias@haeussler.dev>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace EliasHaeussler\VersionBumper\Config\Preset;

use EliasHaeussler\VersionBumper\Config;
use Symfony\Component\OptionsResolver;

/**
 * Typo3ExtensionPreset.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 *
 * @extends BasePreset<array{documentation: 'auto'|bool}>
 */
final class Typo3ExtensionPreset extends BasePreset
{
    private const AUTO_KEYWORD = 'auto';

    public function __construct(array $options = [])
    {
        $this->options = $this->resolveOptions($options);
    }

    public function getConfig(): Config\VersionBumperConfig
    {
        $filesToModify = [
            new Config\FileToModify(
                'ext_emconf.php',
                [
                    new Config\FilePattern("'version' => '{%version%}'"),
                ],
                true,
            ),
        ];

        if (false !== $this->options['documentation']) {
            $filesToModify[] = new Config\FileToModify(
                'Documentation/guides.xml',
                [
                    new Config\FilePattern('release="{%version%}"'),
                ],
                true,
                self::AUTO_KEYWORD !== $this->options['documentation'],
            );
        }

        return new Config\VersionBumperConfig(filesToModify: $filesToModify);
    }

    public static function getIdentifier(): string
    {
        return 'typo3-extension';
    }

    public static function getDescription(): string
    {
        return 'TYPO3 extension, managed by ext_emconf.php';
    }

    protected function createOptionsResolver(): OptionsResolver\OptionsResolver
    {
        $optionsResolver = new OptionsResolver\OptionsResolver();
        $optionsResolver->define('documentation')
            ->allowedValues(self::AUTO_KEYWORD, true, false)
            ->default(self::AUTO_KEYWORD)
        ;

        return $optionsResolver;
    }
}
