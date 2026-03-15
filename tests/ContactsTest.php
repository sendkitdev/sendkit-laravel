<?php

declare(strict_types=1);

use SendKit\Client;
use SendKit\ContactProperties;
use SendKit\Contacts;
use SendKit\Laravel\Facades\SendKit;

it('returns a contacts instance via the facade', function () {
    $client = new Client('test-key');

    $this->app->instance(Client::class, $client);

    $contacts = SendKit::contacts();

    expect($contacts)->toBeInstanceOf(Contacts::class);
});

it('returns a contact properties instance via the facade', function () {
    $client = new Client('test-key');

    $this->app->instance(Client::class, $client);

    $properties = SendKit::contactProperties();

    expect($properties)->toBeInstanceOf(ContactProperties::class);
});
