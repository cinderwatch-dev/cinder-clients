<?php

use Cinderwatch\Errors\GitMetadataEnricher;

function git(string $directory, string $arguments): string
{
    return trim((string) shell_exec(sprintf(
        'git -C %s -c user.name=Cinder -c user.email=cinder@example.test -c commit.gpgsign=false %s 2>&1',
        escapeshellarg($directory),
        $arguments,
    )));
}

function gitCheckout(bool $withCommit = true): string
{
    $directory = sys_get_temp_dir().'/cinder-git-'.bin2hex(random_bytes(6));
    mkdir($directory);
    git($directory, 'init -q');
    git($directory, 'checkout -q -b main');

    if ($withCommit) {
        git($directory, "commit -q --allow-empty -m 'Ship checkout'");
    }

    return $directory;
}

function removeCheckout(string $directory): void
{
    shell_exec('rm -rf '.escapeshellarg($directory));
}

it('tags reports with the checked out commit, branch and subject', function () {
    $checkout = gitCheckout();

    $attributes = (new GitMetadataEnricher($checkout))->attributesFor(new RuntimeException);
    $revision = git($checkout, 'rev-parse HEAD');
    removeCheckout($checkout);

    expect($attributes)->toBe([
        'vcs.ref.head.revision' => $revision,
        'vcs.ref.head.name' => 'main',
        'cinder.vcs.message' => 'Ship checkout',
    ]);
})->group('errors');

it('leaves out the branch on a detached head', function () {
    $checkout = gitCheckout();
    git($checkout, 'checkout -q --detach');

    $attributes = (new GitMetadataEnricher($checkout))->attributesFor(new RuntimeException);
    removeCheckout($checkout);

    expect($attributes)->toHaveKeys(['vcs.ref.head.revision', 'cinder.vcs.message'])
        ->and($attributes)->not->toHaveKey('vcs.ref.head.name');
})->group('errors');

it('resolves each checkout once per process', function () {
    $checkout = gitCheckout();

    $first = (new GitMetadataEnricher($checkout))->attributesFor(new RuntimeException);
    git($checkout, "commit -q --allow-empty -m 'Later commit'");
    $second = (new GitMetadataEnricher($checkout))->attributesFor(new RuntimeException);
    removeCheckout($checkout);

    expect($second)->toBe($first)
        ->and($second['cinder.vcs.message'])->toBe('Ship checkout');
})->group('errors');

it('uses the working directory when no root is given', function () {
    $checkout = gitCheckout();
    $previous = (string) getcwd();
    chdir($checkout);

    try {
        $attributes = (new GitMetadataEnricher)->attributesFor(new RuntimeException);
    } finally {
        chdir($previous);
        removeCheckout($checkout);
    }

    expect($attributes['cinder.vcs.message'])->toBe('Ship checkout');
})->group('errors');

it('adds nothing outside a checkout or before the first commit', function () {
    $plain = sys_get_temp_dir().'/cinder-plain-'.bin2hex(random_bytes(6));
    mkdir($plain);
    $empty = gitCheckout(withCommit: false);

    expect((new GitMetadataEnricher($plain))->attributesFor(new RuntimeException))->toBe([])
        ->and((new GitMetadataEnricher($empty))->attributesFor(new RuntimeException))->toBe([])
        ->and((new GitMetadataEnricher(''))->attributesFor(new RuntimeException))->toBe([]);

    rmdir($plain);
    removeCheckout($empty);
})->group('errors');
