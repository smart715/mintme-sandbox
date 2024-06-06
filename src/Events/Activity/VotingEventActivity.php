<?php declare(strict_types = 1);

namespace App\Events\Activity;

use App\Entity\Voting\TokenVoting;

/** @codeCoverageIgnore */
class VotingEventActivity extends TokenEventActivity
{
    public const NAME = 'token.voting.activity';
    private TokenVoting $voting;

    public function __construct(TokenVoting $voting, int $type)
    {
        $this->voting = $voting;

        parent::__construct($voting->getToken(), $type);
    }

    public function getVoting(): TokenVoting
    {
        return $this->voting;
    }
}
