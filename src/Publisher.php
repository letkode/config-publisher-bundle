<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle;

use Symfony\Component\Filesystem\Filesystem;

final readonly class Publisher
{
    public function __construct(
        private string $projectDir,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * Copies the file into the project. An existing file is kept unless $force is set.
     * With $dryRun nothing is written, but the result is the one a real run would give.
     */
    public function publish(Publishable $publishable, bool $force = false, bool $dryRun = false): PublishResult
    {
        $target = rtrim($this->projectDir, '/') . '/' . $publishable->destination;

        if (is_dir($target)) {
            throw new \RuntimeException(\sprintf('Cannot publish "%s": it is a directory.', $publishable->destination));
        }

        $exists = file_exists($target);

        if ($exists && !$force) {
            return PublishResult::Skipped;
        }

        if (!$dryRun) {
            $this->filesystem->copy($publishable->source, $target, true);
        }

        return $exists ? PublishResult::Overwritten : PublishResult::Created;
    }
}
