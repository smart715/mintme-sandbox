<?php declare(strict_types = 1);

namespace App\Utils;

abstract class ActionTypes
{
    public const CREATE_POST = 'create_post';
    public const CREATE_COMMENT = 'create_comment';
    public const CREATE_VOTING = 'create_voting';
    public const LIKE = 'create_like';
}
