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

namespace EliasHaeussler\VersionBumper\Tests\Version;

use CzProject\GitPhp;
use EliasHaeussler\VersionBumper as Src;
use Generator;
use PHPUnit\Framework;

use function dirname;
use function getcwd;

/**
 * VersionReleaserTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Version\VersionReleaser::class)]
final class VersionReleaserTest extends Framework\TestCase
{
    private GitPhp\Runners\MemoryRunner $runner;
    private Src\Version\VersionReleaser $subject;

    /**
     * @var list<Src\Result\VersionBumpResult>
     */
    private array $results;

    public function setUp(): void
    {
        $cwd = getcwd();

        self::assertIsString($cwd);

        $this->runner = new GitPhp\Runners\MemoryRunner($cwd);
        $this->subject = new Src\Version\VersionReleaser($this->runner);
        $this->results = [
            new Src\Result\VersionBumpResult(
                new Src\Config\FileToModify('composer.json'),
                [
                    new Src\Result\WriteOperation(
                        new Src\Version\Version(1, 0, 0),
                        new Src\Version\Version(2, 0, 0),
                        '',
                        new Src\Config\FilePattern('foo: {%version%}'),
                        Src\Enum\OperationState::Modified,
                    ),
                    new Src\Result\WriteOperation(
                        new Src\Version\Version(1, 0, 0),
                        new Src\Version\Version(2, 0, 0),
                        '',
                        new Src\Config\FilePattern('foo: {%version%}'),
                        Src\Enum\OperationState::Modified,
                    ),
                ],
            ),
        ];
    }

    /**
     * @param list<Src\Result\VersionBumpResult> $results
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('releaseThrowsExceptionIfTargetVersionIsMissingDataProvider')]
    public function releaseThrowsExceptionIfTargetVersionIsMissing(array $results): void
    {
        $this->expectExceptionObject(
            new Src\Exception\TargetVersionIsMissing(),
        );

        $this->subject->release($results, '/foo');
    }

    #[Framework\Attributes\Test]
    public function releaseThrowsExceptionIfAmbiguousVersionsAreDetected(): void
    {
        $results = [
            new Src\Result\VersionBumpResult(
                new Src\Config\FileToModify('foo'),
                [
                    new Src\Result\WriteOperation(
                        new Src\Version\Version(1, 0, 0),
                        new Src\Version\Version(1, 1, 0),
                        '',
                        new Src\Config\FilePattern('foo: {%version%}'),
                        Src\Enum\OperationState::Modified,
                    ),
                    new Src\Result\WriteOperation(
                        new Src\Version\Version(1, 1, 0),
                        new Src\Version\Version(1, 2, 0),
                        '',
                        new Src\Config\FilePattern('foo: {%version%}'),
                        Src\Enum\OperationState::Modified,
                    ),
                ],
            ),
        ];

        $this->expectExceptionObject(
            new Src\Exception\AmbiguousVersionsDetected(),
        );

        $this->subject->release($results, '/foo');
    }

    #[Framework\Attributes\Test]
    public function releaseThrowsExceptionIfNoModifiedFilesAreFound(): void
    {
        $results = [
            new Src\Result\VersionBumpResult(
                new Src\Config\FileToModify('foo'),
                [
                    new Src\Result\WriteOperation(
                        new Src\Version\Version(1, 0, 0),
                        new Src\Version\Version(1, 0, 0),
                        '',
                        new Src\Config\FilePattern('foo: {%version%}'),
                        Src\Enum\OperationState::Skipped,
                    ),
                    new Src\Result\WriteOperation(
                        new Src\Version\Version(1, 0, 0),
                        new Src\Version\Version(1, 0, 0),
                        '',
                        new Src\Config\FilePattern('foo: {%version%}'),
                        Src\Enum\OperationState::Skipped,
                    ),
                ],
            ),
        ];

        $this->expectExceptionObject(
            new Src\Exception\NoModifiedFilesFound(),
        );

        $this->subject->release($results, '/foo');
    }

    #[Framework\Attributes\Test]
    public function releaseThrowsExceptionIfTagAlreadyExists(): void
    {
        $this->runner->setResult(
            ['add', '--end-of-options', 'composer.json'],
            [],
            '',
        );
        $this->runner->setResult(
            ['commit', '-m', 'Release 2.0.0'],
            [],
            '',
        );
        $this->runner->setResult(
            ['tag'],
            [],
            '2.0.0',
        );

        $this->expectExceptionObject(
            new Src\Exception\TagAlreadyExists('2.0.0'),
        );

        $this->subject->release($this->results, dirname(__DIR__, 3));
    }

    #[Framework\Attributes\Test]
    public function releaseOverwritesExistingTag(): void
    {
        $this->runner->setResult(
            ['add', '--end-of-options', 'composer.json'],
            [],
            '',
        );
        $this->runner->setResult(
            ['commit', '-m', 'Release 2.0.0'],
            [],
            '',
        );
        $this->runner->setResult(
            ['tag'],
            [],
            '2.0.0',
        );
        $this->runner->setResult(
            ['tag', '-d', '2.0.0'],
            [],
            '',
        );
        $this->runner->setResult(
            ['tag', '-m', '2.0.0', '--end-of-options', '2.0.0'],
            [],
            '',
        );
        $this->runner->setResult(
            ['log', '--pretty=format:%H', '-n', '1'],
            [],
            'cf79760440d4a34c85cf9ceeefbf2140fad04eb1',
        );

        $expected = new Src\Result\VersionReleaseResult(
            [
                $this->results[0]->file(),
            ],
            'Release 2.0.0',
            '2.0.0',
            'cf79760440d4a34c85cf9ceeefbf2140fad04eb1',
        );

        self::assertEquals(
            $expected,
            $this->subject->release(
                $this->results,
                dirname(__DIR__, 3),
                new Src\Config\ReleaseOptions(overwriteExistingTag: true),
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function releaseSignsTag(): void
    {
        $this->runner->setResult(
            ['add', '--end-of-options', 'composer.json'],
            [],
            '',
        );
        $this->runner->setResult(
            ['commit', '-m', 'Release 2.0.0'],
            [],
            '',
        );
        $this->runner->setResult(
            ['tag'],
            [],
            '',
        );
        $this->runner->setResult(
            ['tag', '-m', '2.0.0', '-s', '--end-of-options', '2.0.0'],
            [],
            '',
        );
        $this->runner->setResult(
            ['log', '--pretty=format:%H', '-n', '1'],
            [],
            'cf79760440d4a34c85cf9ceeefbf2140fad04eb1',
        );

        $expected = new Src\Result\VersionReleaseResult(
            [
                $this->results[0]->file(),
            ],
            'Release 2.0.0',
            '2.0.0',
            'cf79760440d4a34c85cf9ceeefbf2140fad04eb1',
        );

        self::assertEquals(
            $expected,
            $this->subject->release(
                $this->results,
                dirname(__DIR__, 3),
                new Src\Config\ReleaseOptions(signTag: true),
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function releaseRespectsCustomCommitMessageAndTagName(): void
    {
        $this->runner->setResult(
            ['add', '--end-of-options', 'composer.json'],
            [],
            '',
        );
        $this->runner->setResult(
            ['commit', '-m', '[RELEASE] Release of xyz 2.0.0'],
            [],
            '',
        );
        $this->runner->setResult(
            ['tag'],
            [],
            '',
        );
        $this->runner->setResult(
            ['tag', '-m', 'v2.0.0', '--end-of-options', 'v2.0.0'],
            [],
            '',
        );
        $this->runner->setResult(
            ['log', '--pretty=format:%H', '-n', '1'],
            [],
            'cf79760440d4a34c85cf9ceeefbf2140fad04eb1',
        );

        $expected = new Src\Result\VersionReleaseResult(
            [
                $this->results[0]->file(),
            ],
            '[RELEASE] Release of xyz 2.0.0',
            'v2.0.0',
            'cf79760440d4a34c85cf9ceeefbf2140fad04eb1',
        );

        self::assertEquals(
            $expected,
            $this->subject->release(
                $this->results,
                dirname(__DIR__, 3),
                new Src\Config\ReleaseOptions(
                    '[RELEASE] Release of xyz {%version%}',
                    'v{%version%}',
                ),
            ),
        );
    }

    #[Framework\Attributes\Test]
    public function releaseDoesNotPerformAnyWriteOperationsInDryRunMode(): void
    {
        $this->runner->setResult(
            ['tag'],
            [],
            '',
        );

        $expected = new Src\Result\VersionReleaseResult(
            [
                $this->results[0]->file(),
            ],
            'Release 2.0.0',
            '2.0.0',
            null,
        );

        self::assertEquals(
            $expected,
            $this->subject->release(
                $this->results,
                dirname(__DIR__, 3),
                new Src\Config\ReleaseOptions(),
                true,
            ),
        );
    }

    /**
     * @return Generator<string, array{list<Src\Result\VersionBumpResult>}>
     */
    public static function releaseThrowsExceptionIfTargetVersionIsMissingDataProvider(): Generator
    {
        yield 'no results' => [[]];
        yield 'no write operations' => [
            [
                new Src\Result\VersionBumpResult(
                    new Src\Config\FileToModify('foo'),
                    [],
                ),
            ],
        ];
        yield 'missing target version' => [
            [
                new Src\Result\VersionBumpResult(
                    new Src\Config\FileToModify('foo'),
                    [
                        Src\Result\WriteOperation::unmatched(
                            new Src\Config\FilePattern('foo: {%version%}'),
                        ),
                    ],
                ),
            ],
        ];
    }
}
