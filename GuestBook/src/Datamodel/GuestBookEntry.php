<?php

namespace App\Datamodel;

use App\Entity\User;
use App\Entity\GuestBook;

class GuestBookEntry {
    private User $user;
    private string $checkIn;

    public function __construct(User $user, GuestBook $guestBookEntry) {
        $this->user = $user;
        $this->checkIn = $guestBookEntry->getCreatedAt()->format('d.m.Y H:i:s');
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCheckIn(): string
    {
        return $this->checkIn;
    }
}