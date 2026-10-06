<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle\Exception;

final class InvalidPublishDefinition extends \RuntimeException
{
    public static function forPackage(string $package, string $reason): self
    {
        return new self(\sprintf('Invalid "extra.letkode.publish" in %s: %s', $package, $reason));
    }
}
