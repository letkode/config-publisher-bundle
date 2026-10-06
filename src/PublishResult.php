<?php

declare(strict_types=1);

namespace Letkode\ConfigPublisherBundle;

enum PublishResult
{
    case Created;
    case Overwritten;
    case Skipped;
}
