<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "eliashaeussler/version-bumper".
 *
 * Copyright (C) 2024 Elias Häußler <elias@haeussler.dev>
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

namespace EliasHaeussler\VersionBumper\Tests\Config;

use CuyZ\Valinor\Mapper\MappingError;
use EliasHaeussler\VersionBumper as Src;
use PHPUnit\Framework;
use Symfony\Component\Filesystem\Path;

use function dirname;

/**
 * ConfigReaderTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Config\ConfigReader::class)]
final class ConfigReaderTest extends Framework\TestCase
{
    private Src\Config\ConfigReader $subject;

    public function setUp(): void
    {
        $this->subject = new Src\Config\ConfigReader();
    }

    #[Framework\Attributes\Test]
    public function readFromFileThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectExceptionObject(
            new Src\Exception\FileDoesNotExist('foo'),
        );

        $this->subject->readFromFile('foo');
    }

    #[Framework\Attributes\Test]
    public function readFromFileThrowsExceptionOnUnsupportedConfigFile(): void
    {
        $file = __FILE__;

        $this->expectExceptionObject(
            new Src\Exception\ConfigFileIsNotSupported($file),
        );

        $this->subject->readFromFile($file);
    }

    #[Framework\Attributes\Test]
    public function readFromFileThrowsExceptionOnInvalidConfigFile(): void
    {
        $file = dirname(__DIR__).'/Fixtures/ConfigFiles/invalid-config.json';

        $this->expectException(MappingError::class);

        $this->subject->readFromFile($file);
    }

    #[Framework\Attributes\Test]
    public function readFromFileReturnsMappedConfigFromJsonFile(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures';
        $file = $rootPath.'/ConfigFiles/valid-config.json';

        $expected = new Src\Config\VersionBumperConfig(
            [
                new Src\Config\FileToModify(
                    'foo',
                    [
                        'baz: {%version%}',
                    ],
                ),
            ],
            $rootPath,
        );

        self::assertEquals($expected, $this->subject->readFromFile($file));
    }

    #[Framework\Attributes\Test]
    public function readFromFileThrowsExceptionOnInvalidYamlFile(): void
    {
        $file = dirname(__DIR__).'/Fixtures/ConfigFiles/invalid-config.yaml';

        $this->expectExceptionObject(
            new Src\Exception\ConfigFileIsInvalid($file),
        );

        $this->subject->readFromFile($file);
    }

    #[Framework\Attributes\Test]
    public function readFromFileReturnsMappedConfigFromYamlFile(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures';
        $file = $rootPath.'/ConfigFiles/valid-config.yaml';

        $expected = new Src\Config\VersionBumperConfig(
            [
                new Src\Config\FileToModify(
                    'foo',
                    [
                        'baz: {%version%}',
                    ],
                ),
            ],
            $rootPath,
        );

        self::assertEquals($expected, $this->subject->readFromFile($file));
    }

    #[Framework\Attributes\Test]
    public function readFromFileCalculatesRootPathBasedOnConfigFileLocation(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures/ConfigFiles';
        $file = $rootPath.'/valid-config-without-root-path.json';

        $expected = new Src\Config\VersionBumperConfig(
            [
                new Src\Config\FileToModify(
                    'foo',
                    [
                        'baz: {%version%}',
                    ],
                ),
            ],
            $rootPath,
        );

        self::assertEquals($expected, $this->subject->readFromFile($file));
    }

    #[Framework\Attributes\Test]
    public function detectFileReturnsNullIfNoConfigFilesAreAvailableInGivenRootPath(): void
    {
        self::assertNull($this->subject->detectFile(__DIR__));
    }

    #[Framework\Attributes\Test]
    public function detectFileReturnsAutoDetectedFileWithinGivenRootPath(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures/ConfigFiles';
        $expected = Path::join($rootPath, 'version-bumper.yaml');

        self::assertSame($expected, $this->subject->detectFile($rootPath));
    }
}
