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

namespace EliasHaeussler\VersionBumper\Version;

use CzProject\GitPhp;
use EliasHaeussler\VersionBumper\Config;
use EliasHaeussler\VersionBumper\Enum;
use EliasHaeussler\VersionBumper\Exception;
use EliasHaeussler\VersionBumper\Helper;
use EliasHaeussler\VersionBumper\Result;

use function in_array;

/**
 * VersionReleaser.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class VersionReleaser
{
    private readonly GitPhp\Git $git;

    public function __construct(?GitPhp\IRunner $runner = null)
    {
        $this->git = new GitPhp\Git($runner);
    }

    /**
     * @param list<Result\VersionBumpResult> $results
     *
     * @throws Exception\AmbiguousVersionsDetected
     * @throws Exception\NoModifiedFilesFound
     * @throws Exception\TagAlreadyExists
     * @throws Exception\TargetVersionIsMissing
     * @throws GitPhp\GitException
     */
    public function release(
        array $results,
        string $rootPath,
        Config\ReleaseOptions $options = new Config\ReleaseOptions(),
        bool $dryRun = false,
    ): Result\VersionReleaseResult {
        $version = $this->extractVersionFromResults($results);

        if (null === $version) {
            throw new Exception\TargetVersionIsMissing();
        }

        $modifiedFiles = $this->extractModifiedFilesFromResults($results);

        if ([] === $modifiedFiles) {
            throw new Exception\NoModifiedFilesFound();
        }

        $repository = $this->git->open($rootPath);
        $commitMessage = Helper\VersionHelper::replaceVersionInPattern($options->commitMessage(), $version);
        $tagName = Helper\VersionHelper::replaceVersionInPattern($options->tagName(), $version);
        $commitId = null;

        // Check if tag already exists
        if (in_array($tagName, $repository->getTags() ?? [], true)) {
            if (!$options->overwriteExistingTag()) {
                throw new Exception\TagAlreadyExists($tagName);
            }

            if (!$dryRun) {
                $repository->removeTag($tagName);
            }
        }

        if (!$dryRun) {
            // Commit modified files
            $repository->addFile(
                array_map(static fn (Config\FileToModify $file) => $file->path(), $modifiedFiles),
            );
            $repository->commit($commitMessage);

            // Create tag
            $tagOptions = [
                '-m' => $tagName,
            ];

            if ($options->signTag()) {
                $tagOptions[] = '-s';
            }

            $repository->createTag($tagName, $tagOptions);
            $commitId = $repository->getLastCommitId()->toString();
        }

        return new Result\VersionReleaseResult($modifiedFiles, $commitMessage, $tagName, $commitId);
    }

    /**
     * @param list<Result\VersionBumpResult> $results
     *
     * @throws Exception\AmbiguousVersionsDetected
     */
    private function extractVersionFromResults(array $results): ?Version
    {
        $version = null;

        foreach ($results as $result) {
            foreach ($result->operations() as $operation) {
                $targetVersion = $operation->target();

                if (null === $targetVersion) {
                    continue;
                }

                if (null === $version) {
                    $version = $targetVersion;
                }

                if ($targetVersion->full() !== $version->full()) {
                    throw new Exception\AmbiguousVersionsDetected();
                }
            }
        }

        return $version;
    }

    /**
     * @param list<Result\VersionBumpResult> $results
     *
     * @return list<Config\FileToModify>
     */
    private function extractModifiedFilesFromResults(array $results): array
    {
        $modifiedFiles = [];

        foreach ($results as $result) {
            foreach ($result->operations() as $operation) {
                if (Enum\OperationState::Modified === $operation->state()
                    && !in_array($result->file(), $modifiedFiles, true)
                ) {
                    $modifiedFiles[] = $result->file();
                }
            }
        }

        return $modifiedFiles;
    }
}
