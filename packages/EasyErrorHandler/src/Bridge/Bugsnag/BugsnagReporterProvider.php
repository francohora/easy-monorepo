<?php

declare(strict_types=1);

namespace EonX\EasyErrorHandler\Bridge\Bugsnag;

use Bugsnag\Client;
use EonX\EasyErrorHandler\Interfaces\ErrorReporterProviderInterface;

final class BugsnagReporterProvider implements ErrorReporterProviderInterface
{
    /**
     * @var \Bugsnag\Client
     */
    private $bugsnag;

    /**
     * @var null|string[]
     */
    private $ignoredExceptions;

    /**
     * @var null|int
     */
    private $threshold;

    /**
     * @param null|string[] $ignoredExceptions
     */
    public function __construct(Client $bugsnag, ?int $threshold = null, ?array $ignoredExceptions = null)
    {
        $this->bugsnag = $bugsnag;
        $this->threshold = $threshold;
        $this->ignoredExceptions = $ignoredExceptions;
    }

    /**
     * @return iterable<\EonX\EasyErrorHandler\Interfaces\ErrorReporterInterface>
     */
    public function getReporters(): iterable
    {
        yield new BugsnagReporter($this->bugsnag, $this->threshold, $this->ignoredExceptions);
    }
}
