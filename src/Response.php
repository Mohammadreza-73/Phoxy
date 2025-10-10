<?php

namespace Phoxy;

class Response
{
    public const HTTP_OK = 200;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * @var array<int, string>
     */
    public static array $statusTexts = [
        200 => 'OK',
        400 => 'Bad Request',
        403 => 'Forbidden',
        413 => 'Content Too Large',
        500 => 'Internal Server Error',
    ];
}
