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

namespace EliasHaeussler\VersionBumper\Config;

use EliasHaeussler\VersionBumper\Enum;

/**
 * VersionRangeIndicator.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final readonly class VersionRangeIndicator
{
    /**
     * @param list<VersionRangePattern> $patterns
     */
    public function __construct(
        private Enum\VersionRange $range,
        private array $patterns,
        private Enum\VersionRangeIndicatorStrategy $strategy = Enum\VersionRangeIndicatorStrategy::MatchAny,
    ) {}

    public function range(): Enum\VersionRange
    {
        return $this->range;
    }

    /**
     * @return list<VersionRangePattern>
     */
    public function patterns(): array
    {
        return $this->patterns;
    }

    public function strategy(): Enum\VersionRangeIndicatorStrategy
    {
        return $this->strategy;
    }
}
