<?php

namespace Cinderwatch\Errors;

use Cinderwatch\Support\TelemetryAttributeKeys;
use Throwable;

/**
 * Tags reports with the checked out commit, read once per process from the `git` binary.
 */
class GitMetadataEnricher implements ReportEnricher
{
    /** @var array<string, array<string, string>> */
    private static array $resolved = [];

    public function __construct(
        private readonly ?string $checkoutRoot = null,
    ) {}

    public function attributesFor(Throwable $throwable): array
    {
        $root = $this->checkoutRoot ?? getcwd();

        if (! is_string($root) || $root === '') {
            return [];
        }

        return self::$resolved[$root] ??= $this->resolve($root);
    }

    /**
     * @return array<string, string>
     */
    private function resolve(string $root): array
    {
        if (! is_dir($root.DIRECTORY_SEPARATOR.'.git') || ! function_exists('shell_exec')) {
            return [];
        }

        $output = @shell_exec(sprintf(
            'git -C %s log -1 --format=%%H%%n%%D%%n%%s 2>/dev/null',
            escapeshellarg($root),
        ));

        if (! is_string($output) || trim($output) === '') {
            return [];
        }

        [$hash, $refs, $subject] = array_pad(explode("\n", trim($output), 3), 3, '');

        return array_filter([
            TelemetryAttributeKeys::VcsRevision => trim($hash),
            TelemetryAttributeKeys::VcsBranch => $this->branchFromRefs($refs),
            TelemetryAttributeKeys::VcsMessage => trim($subject),
        ], static fn (string $value): bool => $value !== '');
    }

    /**
     * `%D` prints decorations such as "HEAD -> main, origin/main, tag: v1".
     */
    private function branchFromRefs(string $refs): string
    {
        foreach (explode(',', $refs) as $ref) {
            $ref = trim($ref);

            if (str_starts_with($ref, 'HEAD -> ')) {
                return substr($ref, strlen('HEAD -> '));
            }
        }

        return '';
    }
}
