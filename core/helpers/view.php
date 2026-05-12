<?php

function partial(string $file, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require $file;
}
