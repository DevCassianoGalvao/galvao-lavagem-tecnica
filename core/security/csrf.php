<?php

function csrf_token(): string
{
    return CsrfService::token();
}

function csrf_field(): string
{
    return CsrfService::field();
}

function csrf_validate(?string $token): bool
{
    return CsrfService::validate($token);
}
