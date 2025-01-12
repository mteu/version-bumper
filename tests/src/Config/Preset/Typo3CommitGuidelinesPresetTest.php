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

namespace EliasHaeussler\VersionBumper\Tests\Config\Preset;

use EliasHaeussler\VersionBumper as Src;
use PHPUnit\Framework;

/**
 * Typo3CommitGuidelinesPresetTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\Preset\Typo3CommitGuidelinesPreset::class)]
final class Typo3CommitGuidelinesPresetTest extends Framework\TestCase
{
    private Src\Config\Preset\Typo3CommitGuidelinesPreset $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Config\Preset\Typo3CommitGuidelinesPreset();
    }

    #[Framework\Attributes\Test]
    public function getConfigReturnsResolvedConfig(): void
    {
        $expected = new Src\Config\VersionBumperConfig(
            versionRangeIndicators: [
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Major,
                    [
                        new Src\Config\VersionRangePattern(
                            Src\Enum\VersionRangeIndicatorType::CommitMessage,
                            '/^\[!!!]/',
                        ),
                    ],
                ),
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Minor,
                    [
                        new Src\Config\VersionRangePattern(
                            Src\Enum\VersionRangeIndicatorType::CommitMessage,
                            '/^\[FEATURE]/',
                        ),
                    ],
                ),
                new Src\Config\VersionRangeIndicator(
                    Src\Enum\VersionRange::Patch,
                    [
                        new Src\Config\VersionRangePattern(
                            Src\Enum\VersionRangeIndicatorType::CommitMessage,
                            '/^\[(BUGFIX|DOCS|TASK)]/',
                        ),
                    ],
                ),
            ],
        );

        self::assertEquals($expected, $this->subject->getConfig());
    }
}
