<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Entity\User;
use App\Entity\Voting\TokenVoting;
use App\Events\UserEventInterface;

/** @codeCoverageIgnore */
class UserVotingEventActivity extends VotingEventActivity implements UserEventInterface
{
    public const NAME = 'user.voting.activity';
    private User $user;

    public function __construct(User $user, TokenVoting $voting, int $type)
    {
        $this->user = $user;

        parent::__construct($voting, $type);
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
