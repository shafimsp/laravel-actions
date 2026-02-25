<?php

declare(strict_types=1);

namespace ShafiMsp\Actions\Tests\Fixtures;

use ShafiMsp\Actions\Contracts\Action;

/** @implements Action<string> */
final class MissingHandlerAction implements Action {}
