<?php

use Laravel\Reverb\Tests\ReverbTestCase;

use function React\Async\await;

uses(ReverbTestCase::class);

it('can receive and event trigger', function () {
    $response = await($this->signedPostRequest('events', [
        'name' => 'NewEvent',
        'channel' => 'test-channel',
        'data' => ['some' => 'data'],
    ]));

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('{}', $response->getBody()->getContents());
});

it('can receive and event trigger for multiple channels', function () {
    $response = await($this->signedPostRequest('events', [
        'name' => 'NewEvent',
        'channels' => ['test-channel-one', 'test-channel-two'],
        'data' => ['some' => 'data'],
    ]));

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('{}', $response->getBody()->getContents());
});

it('can return user counts when requested', function () {
    $this->subscribe('test-channel-one');

    $response = await($this->signedPostRequest('events', [
        'name' => 'NewEvent',
        'channels' => ['test-channel-one', 'test-channel-two'],
        'data' => ['some' => 'data'],
        'info' => 'user_count',
    ]));

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('{"channels":{"test-channel-one":{"user_count":1},"test-channel-two":{"user_count":0}}}', $response->getBody()->getContents());
});

it('can return subscription counts when requested', function () {
    $this->subscribe('test-channel-two');

    $response = await($this->signedPostRequest('events', [
        'name' => 'NewEvent',
        'channels' => ['test-channel-one', 'test-channel-two'],
        'data' => ['some' => 'data'],
        'info' => 'subscription_count',
    ]));

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('{"channels":{"test-channel-one":{"subscription_count":0},"test-channel-two":{"subscription_count":1}}}', $response->getBody()->getContents());
});

it('can return user and subscription counts when requested', function () {
    $this->subscribe('test-channel-two');

    $response = await($this->signedPostRequest('events', [
        'name' => 'NewEvent',
        'channels' => ['test-channel-one', 'test-channel-two'],
        'data' => ['some' => 'data'],
        'info' => 'subscription_count,user_count',
    ]));

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('{"channels":{"test-channel-one":{"user_count":0,"subscription_count":0},"test-channel-two":{"user_count":1,"subscription_count":1}}}', $response->getBody()->getContents());
});

it('can ignore a subscriber', function () {
    $connection = $this->connect();
    $this->subscribe('test-channel-two', connection: $connection);
    $promiseOne = $this->messagePromise($connection);
    $response = await($this->signedPostRequest('events', [
        'name' => 'NewEvent',
        'channels' => ['test-channel-one', 'test-channel-two'],
        'data' => ['some' => 'data'],
    ]));
    expect(await($promiseOne))->toBe('{"event":"NewEvent","data":{"some":"data"},"channel":"test-channel-two"}');

    $promiseTwo = $this->messagePromise($connection);
    $response = await($this->signedPostRequest('events', [
        'name' => 'NewEvent',
        'channels' => ['test-channel-one', 'test-channel-two'],
        'data' => ['some' => 'data'],
        'socket_id' => $this->connectionId,
    ]));

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('{}', $response->getBody()->getContents());
    expect(await($promiseTwo))->toBeFalse();
});