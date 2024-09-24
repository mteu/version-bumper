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

namespace EliasHaeussler\VersionBumper\Tests\Command;

use Composer\Composer;
use Composer\Console\Application;
use Composer\Factory;
use Composer\IO\NullIO;
use EliasHaeussler\VersionBumper as Src;
use PHPUnit\Framework;
use Symfony\Component\Console;

use function chdir;
use function dirname;
use function getcwd;

/**
 * BumpVersionCommandTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Command\BumpVersionCommand::class)]
final class BumpVersionCommandTest extends Framework\TestCase
{
    private Console\Tester\CommandTester $commandTester;

    public function setUp(): void
    {
        $this->commandTester = $this->createCommandTester();
    }

    #[Framework\Attributes\Test]
    public function executeFailsIfConfigFileIsNotConfigured(): void
    {
        $this->commandTester->execute([
            'range' => 'next',
        ]);

        self::assertSame(Console\Command\Command::INVALID, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            'Please provide a config file path using the --config option.',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeUsesConfigFileFromComposerManifest(): void
    {
        $rootPath = dirname(__DIR__).'/Fixtures';

        $composer = Factory::create(new NullIO(), dirname(__DIR__, 3).'/composer.json');
        $composer->getPackage()->setExtra([
            'version-bumper' => [
                'config-file' => $rootPath.'/ConfigFiles/valid-config.json',
            ],
        ]);

        $commandTester = $this->createCommandTester($composer);
        $commandTester->execute([
            'range' => 'next',
            '--dry-run' => true,
        ]);

        self::assertSame(Console\Command\Command::FAILURE, $commandTester->getStatusCode());
        self::assertStringContainsString(
            'File "'.$rootPath.'/foo" does not exist.',
            $commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeUsesAutoDetectedConfigFile(): void
    {
        $cwd = getcwd();
        $rootPath = dirname(__DIR__).'/Fixtures';

        self::assertIsString($cwd);

        chdir($rootPath.'/ConfigFiles');

        try {
            $this->commandTester->execute([
                'range' => 'next',
            ]);

            self::assertSame(Console\Command\Command::FAILURE, $this->commandTester->getStatusCode());
            self::assertStringContainsString(
                'File "'.$rootPath.'/foo" does not exist.',
                $this->commandTester->getDisplay(),
            );
        } finally {
            chdir($cwd);
        }
    }

    #[Framework\Attributes\Test]
    public function executeConvertsRelativeConfigFilePathToAbsolutePath(): void
    {
        $cwd = getcwd();
        $rootPath = dirname(__DIR__).'/Fixtures';

        self::assertIsString($cwd);

        chdir($rootPath.'/ConfigFiles');

        try {
            $this->commandTester->execute([
                'range' => 'next',
                '--config' => 'valid-config.json',
            ]);

            self::assertSame(Console\Command\Command::FAILURE, $this->commandTester->getStatusCode());
            self::assertStringContainsString(
                'File "'.$rootPath.'/foo" does not exist.',
                $this->commandTester->getDisplay(),
            );
        } finally {
            chdir($cwd);
        }
    }

    #[Framework\Attributes\Test]
    public function executeFailsIfInvalidConfigFileIsGiven(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/invalid-config.json';

        $this->commandTester->execute([
            'range' => 'next',
            '--config' => $configFile,
        ]);

        self::assertSame(Console\Command\Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            '*root*: Unexpected key(s) `foo`, expected `filesToModify`, `rootPath`.',
            $this->commandTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function executeDecoratesVersionBumpResult(): void
    {
        $configFile = dirname(__DIR__).'/Fixtures/ConfigFiles/valid-config-with-root-path.json';

        $this->commandTester->execute([
            'range' => '2.0.0',
            '--config' => $configFile,
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        self::assertSame(Console\Command\Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('Bumped version from "1.0.0" to "2.0.0" (2x)', $output);
        self::assertStringContainsString('Unmatched file pattern: foo: {%version%}', $output);
        self::assertStringContainsString('Skipped file due to unmodified contents', $output);
        self::assertStringContainsString('No write operations were performed (dry-run mode).', $output);
    }

    private function createCommandTester(?Composer $composer = null): Console\Tester\CommandTester
    {
        $application = new Application();
        $command = new Src\Command\BumpVersionCommand($composer);
        $command->setApplication($application);

        return new Console\Tester\CommandTester($command);
    }
}
