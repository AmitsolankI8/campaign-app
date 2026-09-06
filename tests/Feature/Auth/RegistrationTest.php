<?php

test('self registration routes are disabled', function () {
    $this->get('/register')->assertNotFound();

    $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});
