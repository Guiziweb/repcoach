<?php

declare(strict_types=1);

namespace App\Session;

enum SessionTransition: string
{
    case Pause = 'pause';
    case Resume = 'resume';
    case Finish = 'finish';
    case Abort = 'abort';
}
