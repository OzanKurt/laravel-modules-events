<?php

declare(strict_types=1);

use Kurt\Modules\Events\Ticketing\Support\QrTokenSigner;

it('round-trips ticket id and event id', function () {
    $signer = new QrTokenSigner('test-key-for-hmac');
    $token = $signer->sign(42, 7);
    $payload = $signer->verify($token);

    expect($payload['ticket_id'])->toBe(42);
    expect($payload['event_id'])->toBe(7);
    expect($payload['nonce'])->toHaveLength(16);
});

it('rejects tampered payload', function () {
    $signer = new QrTokenSigner('test-key');
    $token = $signer->sign(1, 1);
    [, $sig] = explode('.', $token, 2);
    $tampered = rtrim(strtr(base64_encode('{"ticket_id":99,"event_id":1,"issued_at":0,"nonce":"x"}'), '+/', '-_'), '=').'.'.$sig;
    expect(fn () => $signer->verify($tampered))->toThrow(InvalidArgumentException::class);
});

it('rejects malformed tokens', function () {
    $signer = new QrTokenSigner('k');
    expect(fn () => $signer->verify('not-a-dot-separated-string'))->toThrow(InvalidArgumentException::class);
});

it('rejects token signed with different key', function () {
    $a = new QrTokenSigner('key-a');
    $b = new QrTokenSigner('key-b');
    expect(fn () => $b->verify($a->sign(1, 1)))->toThrow(InvalidArgumentException::class);
});

it('uses a fresh nonce on each call so two signs of the same ticket differ', function () {
    $signer = new QrTokenSigner('rotating-nonce-key');

    $a = $signer->sign(10, 20);
    $b = $signer->sign(10, 20);

    expect($a)->not->toBe($b);
});

it('records the supplied issued_at timestamp in the payload', function () {
    $signer = new QrTokenSigner('time-key');
    $fixedTs = 1_700_000_000;

    $payload = $signer->verify($signer->sign(5, 6, $fixedTs));

    expect($payload['issued_at'])->toBe($fixedTs);
});
