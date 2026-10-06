<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisher;

enum PublishResult
{
    case Created;
    case Overwritten;
    case Skipped;
}
