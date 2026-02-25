<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

use ShafiMsp\Actions\Attributes\HandledBy;
use ShafiMsp\Actions\Contracts\Action;

/** @implements Action<string> */
#[HandledBy(NoHandleMethodHandler::class)]
final class NoHandleMethodAction implements Action {}
